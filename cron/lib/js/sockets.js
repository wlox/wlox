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
	
	// globals
	var db = null;
	var cfg = {};
	var bckey = null;
	var bcpub = null;
	var ready = false;
	var sent = [];
	var sys_bank_accounts = {};
	
	// connect to db and check for a new cfg every half hour
	parseCFG(function (cfg) {
		bckey = bitcoin.ECKey.fromWIF(cfg.crypto_capital_pk);
		bcpub = bckey.pub.getAddress().toString();
		
		var data = {key: bcpub, nonce: Date.now()};
		data.signed = bitcoin.Message.sign(bckey, data.key + data.nonce).toString('base64');
		socket.emit('auth', JSON.stringify(data));
	});
	
	// check for pending requests
	setInterval(function () {
		if (!ready)
			return false;
		
		db_query('SELECT requests.*, bank_accounts.id AS user_acc_id, currencies.account_number AS sys_acc_num, currencies.currency AS currency_abbr FROM requests LEFT JOIN bank_accounts ON (bank_accounts.account_number = requests.account AND bank_accounts.site_user = requests.site_user AND bank_accounts.currency = requests.currency) LEFT JOIN currencies ON (requests.currency = currencies.id) WHERE requests.request_status = '+cfg.request_pending_id+' AND requests.request_type = '+cfg.request_withdrawal_id+' AND requests.currency != '+cfg.btc_currency_id, function (error,results) {
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
					data.params = { accountNumber: String(results[i].sys_acc_num), beneficiary: String(results[i].account), currency: results[i].currency_abbr, amount: results[i].amount, narrative: cfg.exchange_name + ' ' + results[i].id };
					data.signed = bitcoin.Message.sign(bckey, data.key + data.nonce + JSON.stringify(data.params)).toString('base64');
					socket.emit('transfer', JSON.stringify(data));
					logSent(results[i].id);
				}
			}
			else if (error)
				console.error('Couldn\'t fetch pending withdrawals: ',error);
		});
	},2000);
	
	socket.on('ack', function (data) {
		ready = (!ready) ? true : ready;
	});
	
	socket.on('err', function (data) {
		console.error(data);
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
		
		// check if ready
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
	function processTransfer(transfer) {
		if (!ready) {
			setTimeout(function(){
				processTransfer(transfer);
			},500);
		}
		
		var currency_abbr = transfer.params.sendCurrency.toLowerCase();
		var currency_abbr1 = transfer.params.receiveCurrency.toLowerCase();
		var amount = transfer.params.sendAmount;
		var amount1 = transfer.params.receiveAmount;
		
		if (!currency_abbr || !currency_abbr1) {
			console.error('Could not parse transfer currency.');
			return false;
		}
		
		if (!amount || !amount1) {
			console.error('Could not parse transfer amount.');
			return false;
		}
		
		// if is withdrawal
		if (sys_bank_accounts[transfer.params.sendAccount]) {
			var req_id = transfer.params.narrative.substring(transfer.params.narrative.lastIndexOf(" ")).trim();
			if (!req_id) {
				console.error('Could not parse withdrawal id.');
				return false;
			}
			
			db_query('SELECT requests.id AS request_id, site_users.id AS user_id, site_users.'+currency_abbr+' AS balance FROM requests LEFT JOIN site_users ON (requests.site_user = site_users.id) WHERE requests.id = '+req_id+' LIMIT 0,1', function (error,results) {
				if (!error && results[0]) {
					db_query('UPDATE site_users SET '+currency_abbr+' = '+currency_abbr+' - '+amount+' WHERE id = '+results[0].user_id, function (error1,results1) {
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
				else if (error)
					console.error('Error selecting withdrawal.',error);
				else
					console.error('Could not find withdrawal '+req_id+' in database.');
			});
		}
		else {
			// if is deposit
			db_query('SELECT id FROM requests WHERE crypto_id = '+transfer.params.id+' LIMIT 0,1', function (error,results) {
				if (!error && !results[0]) {
					db_query('SELECT bank_accounts.site_user AS user_id, bank_accounts.currency AS currency_id, currencies.currency AS currency, site_users.'+currency_abbr1+' AS cur_balance, site_users.notify_deposit_bank AS notify_deposit_bank, site_users.first_name AS first_name, site_users.last_name AS last_name, site_users.email AS email, site_users.last_lang AS last_lang FROM bank_accounts LEFT JOIN currencies ON (currencies.id = bank_accounts.currency) LEFT JOIN site_users ON (bank_accounts.site_user = site_users.id) WHERE bank_accounts.account_number = '+transfer.params.sendAccount+' LIMIT 0,1', function (error1,results1) {
						if (!error1 && results1[0] && results1[0].currency == transfer.params.receiveCurrency) {
							db_query('INSERT INTO requests (`date`,site_user,currency,amount,description,request_type,request_status,account,crypto_id) VALUES ("'+mysqlDate()+'",'+results1[0]['user_id']+','+results1[0]['currency_id']+','+amount1+','+cfg.deposit_fiat_desc+','+cfg.request_deposit_id+','+cfg.request_completed_id+','+transfer.params.sendAccount+','+transfer.params.id+')', function (error2,results2) {
								if (!error2 && results2.affectedRows && results2.affectedRows > 0) {
									db_query('UPDATE site_users SET '+currency_abbr1+' = '+currency_abbr1+' + '+amount1+' WHERE id = '+results1[0].user_id, function (error3,results3) {
										if (!error3 && results3.affectedRows > 0) {
											db_query('INSERT INTO history (`date`,history_action,site_user,request_id,balance_before,balance_after) VALUES ("'+mysqlDate()+'",'+cfg.history_deposit_id+','+results1[0]['user_id']+','+results2.insertId+','+results1[0].cur_balance+','+(parseFloat(results1[0].cur_balance) + parseFloat(amount1))+')', function (error4,results4) {
												if (error4 || !results4.affectedRows || results4.affectedRows == 0)
													console.error('Could not update deposit history.',error3);
											});
										}
									});
								}
								else
									console.error('Couldn\'t insert deposit request to database.',error2);
							});
						}
						else if (results1[0] && results1[0].currency != transfer.params.receiveCurrency) {
							console.error('Currency mismatch for deposit.');
							db_query('INSERT INTO requests (`date`,site_user,currency,amount,description,request_type,request_status,account,crypto_id) VALUES ("'+mysqlDate()+'",'+results1[0]['user_id']+','+results1[0]['currency_id']+','+amount1+','+cfg.deposit_fiat_desc+','+cfg.request_deposit_id+','+cfg.request_cancelled_id+','+transfer.params.sendAccount+','+transfer.params.id+')');
						}
						else
							console.error('Could not find deposit bank account.',error1);
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
						
						db_query('SELECT muff FRON habob');
						
						callback(cfg);
					}
					else if (error1)
						throw new Error('Could not get system bank accounts from database.');
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
		
		var codes = ['42S02','42000'];
		return (codes.indexOf(error.sqlState));
	}
}