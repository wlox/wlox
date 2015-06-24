// TODO: What happens if insufficient balance on withdraw?
var cluster = require('cluster');
if (cluster.isMaster) {
	cluster.fork();

	cluster.on('exit', function(worker){
		console.log('Uncaught error for on process ' + worker.id + '. Respawning.');
		cluster.fork();
	});
}
else {
	// log start
	console.log('NodeJS: sockets process started.');
	
	// modules
	var socket = require('socket.io-client')('https://api.cryptocapital.co');
	var bitcoin = require('bitcoinjs-lib');
	var deleteKey = require('key-del');
	var mysql = require('mysql');
	var fs = require('fs');
	var nodemailer = require('nodemailer');
	
	// globals
	var db = null;
	var cfg = {};
	var bckey = null;
	var bcpub = null;
	var ready = false;
	var sent = [];
	var sys_bank_accounts = {};
	var email_text = null;
	var email_text_fail = null;
	var smtp = null;
	var statements = {};
	var received = [];
	
	// check for pending requests
	setInterval(function () {
		if (!ready)
			return false;
		
		db_query('SELECT requests.*, bank_accounts.id AS user_acc_id, currencies.account_number AS sys_acc_num, currencies.currency AS currency_abbr FROM requests LEFT JOIN bank_accounts ON (bank_accounts.account_number = requests.account AND bank_accounts.site_user = requests.site_user AND bank_accounts.currency = requests.currency) LEFT JOIN currencies ON (requests.currency = currencies.id) WHERE requests.request_status = '+cfg.request_pending_id+' AND requests.request_type = '+cfg.request_withdrawal_id+' AND requests.currency != '+cfg.btc_currency_id+' AND crypto_id != 1 AND requests.done != "Y"', function (error,results) {
			if (!error && results.length > 0) {
				for (i in results) {
					if (sent.indexOf(results[i].id) >= 0)
						continue;
					
					// cancel request if info mismatch
					if (!results[i].user_acc_id || !results[i].sys_acc_num) {
						db_query('UPDATE requests SET request_status = '+cfg.request_cancelled_id+' WHERE requests.id = '+results[i].id, function (error1,results1) {
							if (error1)
								console.error('Couldn\'t cancel mismatched withdrawal: ',error1);
						});
						continue;
					}

					var data = {key: bcpub, nonce: Date.now()};
					data.params = { accountNumber: String(results[i].sys_acc_num), beneficiary: String(results[i].account), currency: results[i].currency_abbr, amount: results[i].amount, narrative: '**'+cfg.exchange_name + '** ' + results[i].id };
					data.signed = bitcoin.Message.sign(bckey, data.key + data.nonce + JSON.stringify(data.params)).toString('base64');
					socket.emit('transfer', JSON.stringify(data));
					logSent(results[i].id);
					
					db_query('UPDATE requests SET crypto_id = 1 WHERE requests.id = '+results[i].id);
				}
			}
			else if (error)
				console.error('Couldn\'t fetch pending withdrawals: ',error);
		});
	},20000);
	
	socket.on('connect', function (data) {
		// connect to db and check for a new cfg every half hour
		parseCFG(function (cfg) {
			bckey = bitcoin.ECKey.fromWIF(cfg.crypto_capital_pk);
			bcpub = bckey.pub.getAddress().toString();
			
			var data = {key: bcpub, nonce: Date.now()};
			data.signed = bitcoin.Message.sign(bckey, data.key + data.nonce).toString('base64');
			socket.emit('auth', JSON.stringify(data));
			
			var offset = (new Date()).getTimezoneOffset() * 60;
			for (acc_num in sys_bank_accounts) {
				data.nonce = Date.now();
				data.params = { accountNumber: String(acc_num), fromTime: String(Math.ceil(Date.now() / 1000) - 86400) };
				data.signed = bitcoin.Message.sign(bckey, data.key + data.nonce + JSON.stringify(data.params)).toString('base64');
				socket.emit('statement', JSON.stringify(data));
			}
		});
	});
	
	socket.on('disconnect', function (data) {
		ready = false;
	});
	
	socket.on('ack', function (data) {
		ready = (!ready) ? true : ready;
	});
	
	socket.on('err', function (data) {
		console.error(data);
	});
	
	socket.on('statement', function (data) {
		processStatement(data);
	});
	
	socket.on('transfer', function (data) {
		// check if valid JSON
		try {
			var transfer = JSON.parse(data);
		}
		catch (e) {
			console.error(e);
			return false;
		}
		
		// check if params
		if (!transfer.params || Object.keys(transfer.params) <= 0)
			return false;
		
		// check signature
		if (bitcoin.Message.verify(transfer.key, transfer.signed, transfer.key + transfer.nonce + transfer.rcpt + JSON.stringify(transfer.params))) {
			processTransfer(transfer);
		}
        else
        	console.log('Incoming transaction invalid signature.');
	});
	
	// Catch exceptions
	process.on('uncaughtException', function(err){
		if (db)
			db.end();
		
		console.error(err);
		process.exit(1);
	});
	
	// function for processing transfers
	function processTransfer(transfer,statement) {
		if (!ready) {
			setTimeout(function(){
				processTransfer(transfer);
			},500);
		}
		
		var currency_abbr = transfer.params.sendCurrency.toLowerCase();
		var currency_abbr1 = transfer.params.receiveCurrency.toLowerCase();
		var amount = transfer.params.sendAmount;
		var amount1 = transfer.params.receiveAmount;
		
		// check for double hooks
		if (received.indexOf(transfer.params.id) >= 0)
			return false;
		else
			logReceived(transfer.params.id);
		
		if (!currency_abbr || !currency_abbr1) {
			if (!statement)
				console.error('Could not parse transfer currency.');
			return false;
		}
		
		if (!amount || !amount1) {
			if (!statement)
				console.error('Could not parse transfer amount.');
			return false;
		}
		
		if (transfer.params.narrative && transfer.params.narrative.search('please link') >= 0) {
			if (!statement)
				console.log('Rejection not processed');
			return false;
		}
		
		// if is withdrawal
		if (sys_bank_accounts[transfer.params.sendAccount]) {
			var req_id = transfer.params.narrative.substring(transfer.params.narrative.lastIndexOf(" ")).trim();
			if (transfer.params.narrative.search('\\*\\*'+cfg.exchange_name+'\\*\\*') < 0 || !req_id) {
				if (!statement)
					console.error('Could not parse withdrawal id.');
				return false;
			}

			db_query('SELECT requests.id AS request_id, requests.site_user AS user_id, site_users_balances.balance AS balance, site_users_balances.id AS balance_id FROM requests LEFT JOIN site_users_balances ON (requests.site_user = site_users_balances.site_user AND requests.currency = site_users_balances.currency) WHERE requests.id = '+req_id+' AND requests.request_status != '+cfg.request_completed_id+' LIMIT 0,1', function (error,results) {
				if (!error && results[0]) {
					db_query('UPDATE site_users_balances SET balance = balance - '+amount+' WHERE id = '+results[0].balance_id, function (error1,results1) {
						if (!error1 && results1.affectedRows > 0) {
							db_query('UPDATE requests SET request_status = '+cfg.request_completed_id+', done = "Y" WHERE id = '+req_id, function (error2,results2) {
								if (!error2 && results2.affectedRows > 0) {
									db_query('UPDATE history SET balance_before = '+results[0].balance+', balance_after = '+(parseFloat(results[0].balance) - parseFloat(amount))+' WHERE request_id = '+req_id, function (error3,results3) {
										if (error3 || !results3.affectedRows || results3.affectedRows == 0)
											console.error('Could not update withdrawal history.',error3);
									});
								}
								else
									console.error('Could not update withdrawal request.',error2);
							});
						}
						else
							console.error('Could not update user balance on withdrawal.',error1);
					});
				}
				else if (error) {
					if (!statement)
						console.error('Error selecting withdrawal.',error);
				}
				else {
					if (!statement)
					console.error('Could not find withdrawal '+req_id+' in database, or withdrawal already complete.');
				}
			});
		}
		else if (sys_bank_accounts[transfer.params.receiveAccount]) {
			// if is deposit
			db_query('SELECT id FROM requests WHERE crypto_id = '+transfer.params.id+' LIMIT 0,1', function (error,results) {
				if (!error && !results[0]) {
					db_query('SELECT bank_accounts.site_user AS user_id, bank_accounts.currency AS currency_id, currencies1.id AS currency_id1, bank_accounts1.account_number AS account_number1, currencies.currency AS currency, site_users.notify_deposit_bank AS notify_deposit_bank, site_users.first_name AS first_name, site_users.last_name AS last_name, site_users.email AS email, site_users.last_lang AS last_lang, site_users.notify_deposit_bank AS notify_deposit_bank, site_users_balances.balance AS cur_balance, site_users_balances.id AS balance_id FROM bank_accounts LEFT JOIN currencies ON (currencies.id = bank_accounts.currency) LEFT JOIN site_users ON (bank_accounts.site_user = site_users.id) LEFT JOIN currencies currencies1 ON (currencies1.currency = "'+transfer.params.receiveCurrency+'") LEFT JOIN bank_accounts bank_accounts1 ON (site_users.id = bank_accounts1.site_user AND bank_accounts1.currency = currencies1.id) LEFT JOIN site_users_balances ON (site_users.id = site_users_balances.site_user AND site_users_balances.currency = currencies1.id) WHERE bank_accounts.account_number = '+transfer.params.sendAccount+' LIMIT 0,1', function (error1,results1) {
						if (!error1 && results1 && results1[0] && results1[0].currency_id1 && (results1[0].currency == transfer.params.receiveCurrency || (results1 && results1[0].currency_id1 && results1[0].account_number1))) {
							if (results1[0].currency != transfer.params.receiveCurrency) {
								results1[0].currency_id = results1[0].currency_id1;
							}
							var fee = parseFloat(amount) - parseFloat(amount1);
							db_query('INSERT INTO requests (`date`,site_user,currency,amount,net_amount,fee,description,request_type,request_status,account,crypto_id) VALUES ("'+mysqlDate()+'",'+results1[0]['user_id']+','+results1[0]['currency_id']+','+amount1+','+amount1+','+fee+','+cfg.deposit_fiat_desc+','+cfg.request_deposit_id+','+cfg.request_completed_id+','+transfer.params.sendAccount+','+transfer.params.id+')', function (error2,results2) {
								if (!error2 && results2.affectedRows && results2.affectedRows > 0) {
									db_query('UPDATE site_users_balances SET balance = balance + '+amount1+' WHERE id = '+results1[0].balance_id, function (error3,results3) {
										if (!results3.affectedRows || results3.affectedRows == 0) {
											db_query('INSERT INTO site_users_balances (balance,site_user,currency) VALUES ('+amount1+','+results1[0].user_id+','+results1[0].currency_id1+')', function (error,result) {
												if (error)
													console.error('Could\'t create user balance record.',error);
											});
										}

										db_query('INSERT INTO history (`date`,history_action,site_user,request_id,balance_before,balance_after) VALUES ("'+mysqlDate()+'",'+cfg.history_deposit_id+','+results1[0]['user_id']+','+results2.insertId+','+results1[0].cur_balance+','+(parseFloat(results1[0].cur_balance) + parseFloat(amount1))+')', function (error4,results4) {
											if (error4 || !results4.affectedRows || results4.affectedRows == 0)
												console.error('Could not update deposit history.',error3);
										});
										
										if (results1[0].notify_deposit_bank && results1[0].notify_deposit_bank == 'Y')
											sendMail(cfg.form_email_from+' <'+cfg.email_smtp_send_from+'>',results1[0].email,email_text,{ exchange_name: cfg.exchange_name, amount:amount1, first_name:results1[0].first_name, last_name: results1[0].last_name, currency:transfer.params.receiveCurrency, id:results2.insertId },results1[0].last_lang);
									});
								}
								else
									console.error('Couldn\'t insert deposit request to database.',error2);
							});
						}
						else {
							db_query('INSERT INTO requests (`date`,amount,net_amount,description,request_type,request_status,account,crypto_id) VALUES ("'+mysqlDate()+'",'+amount1+','+amount1+','+cfg.deposit_fiat_desc+','+cfg.request_deposit_id+','+cfg.request_cancelled_id+','+transfer.params.sendAccount+','+transfer.params.id+')', function (error,result) {
								if (!error && result.insertId) {
									var data = {key: bcpub, nonce: Date.now()};
									data.params = { accountNumber: String(transfer.params.receiveAccount), beneficiary: String(transfer.params.sendAccount), currency: transfer.params.receiveCurrency, amount: transfer.params.receiveAmount, narrative: cfg.exchange_name + ' TID #'+transfer.params.id+' please link account'};
									data.signed = bitcoin.Message.sign(bckey, data.key + data.nonce + JSON.stringify(data.params)).toString('base64');
									socket.emit('transfer', JSON.stringify(data));
									
									notifyFailure(transfer);
									console.error('Could not find deposit bank account.',error1);
								}
							});
						}
					});
				}
			});
		}
	}
	
	// function for getting CFG
	function parseCFG(callback) {
		ready = false;
		
		var cfg_php = fs.readFileSync(__dirname.replace('lib/js','')+'cfg.php', "utf8");
		var cfg_php_lines = cfg_php.split('\n');
		
		for (i in cfg_php_lines) {
			if (cfg_php_lines[i].search('CFG->') >= 0) {
				var line = cfg_php_lines[i].replace('$CFG->','').split('=');
				var key = line[0].trim();
				cfg[key] = line[1].trim().replace(/"/g,'').replace(/;/g,'');
			}
		}
		
		if (!db) {
			db = mysql.createConnection({host: cfg.dbhost, user: cfg.dbuser, password: cfg.dbpass, database: cfg.dbname});
		}
		
		db_query('SELECT * FROM app_configuration WHERE id = 1', function (error,results) {
			if (!error && results[0]) {
				cfg = {};
				for (i in results[0]) {
					cfg[i] = results[0][i];
				}
				
				process.env.TZ = cfg.default_timezone;

				db_query('SELECT * FROM currencies', function (error1,results1) {
					if (!error1 && results1.length > 0) {
						sys_bank_accounts = {};
						for (j in results1) {
							sys_bank_accounts[results1[j].account_number] = results1[j];
						}

						setTimeout(function () {
							parseCFG(function (cfg) {
								bckey = bitcoin.ECKey.fromWIF(cfg.crypto_capital_pk);
								bcpub = bckey.pub.getAddress().toString();
								ready = true;
							});
						},1800000);
						
						callback(cfg);
					}
					else if (error1)
						throw new Error('Could not get system bank accounts from database.');
				});
				
				db_query('SELECT * FROM emails WHERE `key` = "new-deposit" LIMIT 0,1', function (error2,results2) {
					email_text = (results2 && results2[0]) ? results2[0] : null;
				});
				
				db_query('SELECT * FROM emails WHERE `key` = "fiat-deposit-failure" LIMIT 0,1', function (error2,results2) {
					email_text_fail = (results2 && results2[0]) ? results2[0] : null;
				});
				
				smtp = nodemailer.createTransport({
				    host: cfg.email_smtp_host,
				    port: cfg.email_smtp_port,
				    secure: (cfg.email_smtp_security == 'ssl'),
				    auth: { user: cfg.email_smtp_username, pass: cfg.email_smtp_password }
				});
			}
			else if (error)
				throw new Error('Could not get CFG from database.');
		});
	}
	
	// keep log of sent transactions
	function logSent(id) {
		sent.push(id);
		
		if (sent.length > 10000)
			sent.pop();
	}
	
	// keep log of received transactions
	function logReceived(id) {
		received.push(id);
		
		if (received.length > 10000)
			received.pop();
	}
	
	// date in mysql format
	function mysqlDate() {
		var mysql_date = new Date(Date.now() - ((new Date()).getTimezoneOffset() * 60000)).toISOString().replace('T',' ');
		return mysql_date.substring(0,mysql_date.lastIndexOf("."));
	}
	
	// retry failed transactions
	function db_query(sql,callback,t) {
		if (!t) t = 0;
		
		db.query(sql, function (error,results) {
			if (!error) {
				if (callback)
					callback(null,results);
			}
			else if (error && db_is_syntax_error(error)) {
				if (callback)
					callback(error,results);
			}
			else if (error && !db_is_syntax_error(error)) {
				if (t > 4) {
					if (callback)
						callback(error,results);
				}
				else {
					console.error('Query failed. Retrying...');
					setTimeout(function() {
						db_query(sql,callback,t+1);
					},400);
				}
				
			}
		});
	}
	
	function db_is_syntax_error(error) {
		if (!error.sqlState)
			return false;
		
		var codes = ['42S02','42000','42S22'];
		return (codes.indexOf(error.sqlState));
	}
	
	// replace email template vars
	function emailVars(text,vars) {
		if (!vars || !text || Object.keys(vars) <= 0)
			return false;
		
		if (Buffer.isBuffer(text))
			text = text.toString('utf8');
		
		for (i in vars) {
			text = text.replace(eval('/\\['+i+'\\]/g'),vars[i]);
		}
		
		return text;
	}
	
	// send mail
	function sendMail(from,to,html,vars,lang) {
		if (!html || !html['title_'+lang] || !html['content_'+lang]) {
			console.error('Could not get email template.');
			return false;
		}
		
		lang = (!lang) ? 'en' : lang;
		var subject = emailVars(html['title_'+lang],vars);
		var content_html = emailVars(html['content_'+lang],vars);
		var content_plain = content_html.replace(/(<([^>]+)>)/ig,"");
			
		smtp.sendMail({
			from: from,
			to: to,
		    subject: subject,
		    text: content_plain, 
		    html: content_html 
		}, function(error, info){
			if(error) {
				console.error(error);
				return false;
			}
			else
				return true;
		});
	}
	
	// notify failed deposits
	function notifyFailure(transfer) {
		if (!cfg.email_notify_fiat_failed || cfg.email_notify_fiat_failed != 'Y')
			return false;
			
		sendMail(cfg.form_email_from+' <'+cfg.email_smtp_send_from+'>',cfg.support_email,email_text_fail,{id: transfer.params.id, exchange_name: cfg.exchange_name  },'en');
		sendMail(cfg.form_email_from+' <'+cfg.email_smtp_send_from+'>','accounts@cryptocapital.co',email_text_fail,{id: transfer.params.id, exchange_name: cfg.exchange_name  },'en');
	}
	
	// get statments on reconnect
	function processStatement(data_raw) {
		// check if valid JSON
		try {
			var data = JSON.parse(data_raw);
		}
		catch (e) {
			console.error(e);
			return false;
		}
		
		// check signature
		if (!bitcoin.Message.verify(data.key, data.signed, data.key + data.nonce + data.rcpt + JSON.stringify(data.params))) {
			console.error('Incoming statement invalid signature.');
			return false;
		}
		
		if (!data.params || !data.params.transactions)
			return false;
		
		for (i in data.params.transactions) {
			processTransfer({params: data.params.transactions[i]},1);
		}
	}
}