ALTER TABLE orders DROP INDEX btc_price_2;
ALTER TABLE orders DROP INDEX `date`;
ALTER TABLE orders DROP INDEX order_type;
ALTER TABLE orders DROP INDEX market_price;
ALTER TABLE transactions DROP INDEX transaction_type;
ALTER TABLE transactions DROP INDEX transaction_type1;
ALTER TABLE transactions DROP INDEX convert_from_currency;
ALTER TABLE transactions DROP INDEX convert_to_currency;
ALTER TABLE transactions DROP INDEX conversion;
ALTER TABLE transactions DROP INDEX factored;
ALTER TABLE site_users DROP INDEX deactivated;
ALTER TABLE site_users DROP INDEX trusted;
ALTER TABLE `currencies` CHANGE `fa_symbol` `fa_symbol` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

CREATE TABLE app_configuration (id INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ) ENGINE = MYISAM;
ALTER TABLE app_configuration ADD `default_timezone` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `orders_under_market_percent` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `orders_min_usd` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `bitcoin_sending_fee` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `frontend_baseurl` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `frontend_dirroot` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `fiat_withdraw_fee` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `api_db_debug` ENUM('Y','N') NOT NULL DEFAULT 'N';
ALTER TABLE app_configuration ADD `api_dirroot` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `support_email` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `email_smtp_host` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `email_smtp_port` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `email_smtp_security` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `email_smtp_username` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `email_smtp_password` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `email_smtp_send_from` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `bitcoin_username` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE app_configuration ADD `bitcoin_accountname` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE app_configuration ADD `bitcoin_passphrase` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `bitcoin_host` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `bitcoin_port` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `bitcoin_protocol` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `authy_api_key` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `helpdesk_key` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `exchange_name` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `mcrypt_key` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `currency_conversion_fee` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `cross_currency_trades` ENUM('Y','N') NOT NULL DEFAULT 'N';
ALTER TABLE app_configuration ADD `btc_currency_id` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `deposit_bitcoin_desc` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `default_fee_schedule_id` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `history_buy_id` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `history_deposit_id` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `history_login_id` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `history_sell_id` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `history_withdraw_id` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `order_type_ask` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `request_awaiting_id` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `request_cancelled_id` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `request_completed_id` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `order_type_bid` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `request_deposit_id` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `request_pending_id` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `request_withdrawal_id` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `transactions_buy_id` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `transactions_sell_id` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `withdraw_fiat_desc` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `withdraw_btc_desc` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `form_email_from` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `email_notify_new_users` ENUM('Y','N') NOT NULL DEFAULT 'N';
ALTER TABLE app_configuration ADD `pass_regex` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `pass_min_chars` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `auth_db_debug` ENUM('Y','N') NOT NULL DEFAULT 'N';
ALTER TABLE app_configuration ADD `bitcoin_reserve_min` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `bitcoin_reserve_ratio` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `bitcoin_warm_wallet_address` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `cron_db_debug` ENUM('Y','N') NOT NULL DEFAULT 'N';
ALTER TABLE app_configuration ADD `quandl_api_key` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `cron_dirroot` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `backstage_db_debug` ENUM('Y','N') NOT NULL DEFAULT 'N';
ALTER TABLE app_configuration ADD `backstage_dirroot` VARCHAR( 255 ) NOT NULL;

INSERT INTO `lang` (`id`, `key`, `esp`, `eng`, `order`, `p_id`) VALUES
(443, 'last-page', 'Último', 'Last', '', 17),
(444, 'first-page', 'Primero', 'First', '', 17);

INSERT INTO `admin_controls` (`id`, `page_id`, `tab_id`, `action`, `class`, `arguments`, `order`, `is_static`) VALUES
(269, 94, 0, 'form', 'Form', 'a:10:{s:4:"name";s:17:"app_configuration";s:6:"method";s:0:"";s:5:"class";s:0:"";s:5:"table";s:17:"app_configuration";s:18:"start_on_construct";s:0:"";s:9:"go_to_url";s:0:"";s:12:"go_to_action";s:0:"";s:12:"go_to_is_tab";s:0:"";s:6:"target";s:0:"";s:14:"return_to_self";s:0:"";}', 0, 'N');

INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(2397, 'startArea', 'a:3:{s:6:"legend";s:19:"Global App Settings";s:5:"class";s:3:"box";s:6:"height";s:0:"";}', 2, 269, 0),
(2398, 'endArea', '', 15, 269, 0),
(2399, 'startArea', 'a:3:{s:6:"legend";s:10:"API Config";s:5:"class";s:3:"box";s:6:"height";s:0:"";}', 46, 269, 0),
(2400, 'endArea', '', 49, 269, 0),
(2401, 'startArea', 'a:3:{s:6:"legend";s:11:"Auth Config";s:5:"class";s:3:"box";s:6:"height";s:0:"";}', 50, 269, 0),
(2402, 'endArea', '', 52, 269, 0),
(2403, 'startArea', 'a:3:{s:6:"legend";s:11:"Cron Config";s:5:"class";s:3:"box";s:6:"height";s:0:"";}', 53, 269, 0),
(2404, 'endArea', '', 56, 269, 0),
(2405, 'startArea', 'a:3:{s:6:"legend";s:23:"Bitcoin Server Settings";s:5:"class";s:3:"box";s:6:"height";s:0:"";}', 26, 269, 0),
(2406, 'endArea', '', 45, 269, 0),
(2407, 'startArea', 'a:3:{s:6:"legend";s:18:"Database Constants";s:5:"class";s:3:"box";s:6:"height";s:0:"";}', 61, 269, 0),
(2408, 'endArea', '', 82, 269, 0),
(2409, 'textInput', 'a:13:{s:4:"name";s:16:"frontend_baseurl";s:7:"caption";s:8:"Base URL";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 43, 269, 0),
(2410, 'textInput', 'a:13:{s:4:"name";s:16:"frontend_dirroot";s:7:"caption";s:8:"Dir Root";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 44, 269, 0),
(2412, 'textInput', 'a:13:{s:4:"name";s:16:"default_timezone";s:7:"caption";s:20:"Application Timezone";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 3, 269, 0),
(2413, 'textInput', 'a:13:{s:4:"name";s:27:"orders_under_market_percent";s:7:"caption";s:31:"Min. Order Price (% under Mkt.)";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 9, 269, 0),
(2414, 'textInput', 'a:13:{s:4:"name";s:14:"orders_min_usd";s:7:"caption";s:22:"Min. Order Amnt. (USD)";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 8, 269, 0),
(2415, 'textInput', 'a:13:{s:4:"name";s:19:"bitcoin_sending_fee";s:7:"caption";s:15:"BTC Miner''s Fee";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 4, 269, 0),
(2416, 'textInput', 'a:13:{s:4:"name";s:17:"fiat_withdraw_fee";s:7:"caption";s:19:"Fiat Withdrawal Fee";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 7, 269, 0),
(2417, 'submitButton', 'a:6:{s:4:"name";s:4:"save";s:5:"value";s:18:"Save Configuration";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";}', 83, 269, 0),
(2418, 'cancelButton', 'a:4:{s:5:"value";s:4:"Back";s:2:"id";s:0:"";s:5:"class";s:0:"";s:5:"style";s:0:"";}', 1, 269, 0),
(2422, 'cancelButton', 'a:4:{s:5:"value";s:4:"Back";s:2:"id";s:0:"";s:5:"class";s:0:"";s:5:"style";s:0:"";}', 84, 269, 0),
(2421, 'submitButton', 'a:6:{s:4:"name";s:4:"save";s:5:"value";s:18:"Save Configuration";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";}', 0, 269, 0),
(2423, 'checkBox', 'a:9:{s:4:"name";s:12:"api_db_debug";s:7:"caption";s:16:"DB Debug On Fail";s:8:"required";s:0:"";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:11:"label_class";s:0:"";s:7:"checked";s:0:"";}', 47, 269, 0),
(2425, 'textInput', 'a:13:{s:4:"name";s:11:"api_dirroot";s:7:"caption";s:8:"Dir Root";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 48, 269, 0),
(2450, 'textInput', 'a:13:{s:4:"name";s:15:"btc_currency_id";s:7:"caption";s:15:"BTC Currency ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 62, 269, 0),
(2427, 'startArea', 'a:3:{s:6:"legend";s:14:"Email Settings";s:5:"class";s:3:"box";s:6:"height";s:0:"";}', 16, 269, 0),
(2428, 'endArea', '', 25, 269, 0),
(2429, 'textInput', 'a:13:{s:4:"name";s:13:"support_email";s:7:"caption";s:13:"Support Email";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 17, 269, 0),
(2430, 'textInput', 'a:13:{s:4:"name";s:15:"email_smtp_host";s:7:"caption";s:9:"SMTP Host";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 19, 269, 0),
(2431, 'textInput', 'a:13:{s:4:"name";s:15:"email_smtp_port";s:7:"caption";s:9:"SMTP Port";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 20, 269, 0),
(2432, 'textInput', 'a:13:{s:4:"name";s:19:"email_smtp_security";s:7:"caption";s:18:"SMTP Security Type";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 21, 269, 0),
(2433, 'textInput', 'a:13:{s:4:"name";s:19:"email_smtp_username";s:7:"caption";s:13:"SMTP Username";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 22, 269, 0),
(2434, 'textInput', 'a:13:{s:4:"name";s:19:"email_smtp_password";s:7:"caption";s:13:"SMTP Password";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 23, 269, 0),
(2435, 'textInput', 'a:13:{s:4:"name";s:20:"email_smtp_send_from";s:7:"caption";s:17:"SMTP Sender Email";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 24, 269, 0),
(2436, 'startArea', 'a:3:{s:6:"legend";s:20:"Third Party API Keys";s:5:"class";s:3:"box";s:6:"height";s:0:"";}', 37, 269, 0),
(2437, 'endArea', '', 36, 269, 0),
(2438, 'textInput', 'a:13:{s:4:"name";s:16:"bitcoin_username";s:7:"caption";s:8:"Username";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 27, 269, 0),
(2439, 'textInput', 'a:13:{s:4:"name";s:19:"bitcoin_accountname";s:7:"caption";s:12:"Account Name";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 28, 269, 0),
(2440, 'textInput', 'a:13:{s:4:"name";s:18:"bitcoin_passphrase";s:7:"caption";s:10:"Passphrase";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 29, 269, 0),
(2441, 'textInput', 'a:13:{s:4:"name";s:12:"bitcoin_host";s:7:"caption";s:4:"Host";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 30, 269, 0),
(2442, 'textInput', 'a:13:{s:4:"name";s:12:"bitcoin_port";s:7:"caption";s:4:"Port";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 31, 269, 0),
(2443, 'textInput', 'a:13:{s:4:"name";s:16:"bitcoin_protocol";s:7:"caption";s:8:"Protocol";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 32, 269, 0),
(2444, 'startArea', 'a:3:{s:6:"legend";s:15:"Frontend Config";s:5:"class";s:3:"box";s:6:"height";s:0:"";}', 42, 269, 0),
(2445, 'endArea', '', 41, 269, 0),
(2446, 'textInput', 'a:13:{s:4:"name";s:13:"authy_api_key";s:7:"caption";s:13:"Authy API Key";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 38, 269, 0),
(2447, 'textInput', 'a:13:{s:4:"name";s:12:"helpdesk_key";s:7:"caption";s:17:"Help Desk API Key";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 40, 269, 0),
(2448, 'textInput', 'a:13:{s:4:"name";s:13:"exchange_name";s:7:"caption";s:15:"Exchange''s Name";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 6, 269, 0),
(2449, 'textInput', 'a:13:{s:4:"name";s:10:"mcrypt_key";s:7:"caption";s:17:"Password Hash Key";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 10, 269, 0),
(2452, 'checkBox', 'a:9:{s:4:"name";s:21:"cross_currency_trades";s:7:"caption";s:28:"Enable Cross-Currency Trades";s:8:"required";s:0:"";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:11:"label_class";s:0:"";s:7:"checked";s:0:"";}', 13, 269, 0),
(2453, 'textInput', 'a:13:{s:4:"name";s:23:"currency_conversion_fee";s:7:"caption";s:27:"Currency Conversion Fee (%)";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 5, 269, 0),
(2454, 'textInput', 'a:13:{s:4:"name";s:20:"deposit_bitcoin_desc";s:7:"caption";s:24:"Deposit Bitcoin Desc. ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 63, 269, 0),
(2455, 'textInput', 'a:13:{s:4:"name";s:23:"default_fee_schedule_id";s:7:"caption";s:23:"Fee Schedule Default ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 64, 269, 0),
(2456, 'textInput', 'a:13:{s:4:"name";s:14:"history_buy_id";s:7:"caption";s:14:"History Buy ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 65, 269, 0),
(2457, 'textInput', 'a:13:{s:4:"name";s:18:"history_deposit_id";s:7:"caption";s:19:"History Deposity ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 66, 269, 0),
(2458, 'textInput', 'a:13:{s:4:"name";s:16:"history_login_id";s:7:"caption";s:16:"History Login ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 67, 269, 0),
(2459, 'textInput', 'a:13:{s:4:"name";s:15:"history_sell_id";s:7:"caption";s:15:"History Sell ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 68, 269, 0),
(2460, 'textInput', 'a:13:{s:4:"name";s:19:"history_withdraw_id";s:7:"caption";s:19:"History Withdraw ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 69, 269, 0),
(2461, 'textInput', 'a:13:{s:4:"name";s:14:"order_type_ask";s:7:"caption";s:17:"Order-Type Ask ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 70, 269, 0),
(2462, 'textInput', 'a:13:{s:4:"name";s:19:"request_awaiting_id";s:7:"caption";s:25:"Request Awaiting Auth. ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 72, 269, 0),
(2463, 'textInput', 'a:13:{s:4:"name";s:20:"request_cancelled_id";s:7:"caption";s:20:"Request Cancelled ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 73, 269, 0),
(2464, 'textInput', 'a:13:{s:4:"name";s:20:"request_completed_id";s:7:"caption";s:20:"Request Completed ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 74, 269, 0),
(2465, 'textInput', 'a:13:{s:4:"name";s:14:"order_type_bid";s:7:"caption";s:17:"Order-Type Bid ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 71, 269, 0),
(2466, 'textInput', 'a:13:{s:4:"name";s:18:"request_deposit_id";s:7:"caption";s:18:"Request Deposit ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 75, 269, 0),
(2467, 'textInput', 'a:13:{s:4:"name";s:18:"request_pending_id";s:7:"caption";s:18:"Request Pending ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 76, 269, 0),
(2468, 'textInput', 'a:13:{s:4:"name";s:21:"request_withdrawal_id";s:7:"caption";s:21:"Request Withdrawal ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 77, 269, 0),
(2469, 'textInput', 'a:13:{s:4:"name";s:19:"transactions_buy_id";s:7:"caption";s:19:"Transactions Buy ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 78, 269, 0),
(2470, 'textInput', 'a:13:{s:4:"name";s:20:"transactions_sell_id";s:7:"caption";s:20:"Transactions Sell ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 79, 269, 0),
(2471, 'textInput', 'a:13:{s:4:"name";s:18:"withdraw_fiat_desc";s:7:"caption";s:19:"Withdraw Fiat Desc.";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 80, 269, 0),
(2472, 'textInput', 'a:13:{s:4:"name";s:17:"withdraw_btc_desc";s:7:"caption";s:18:"Withdraw BTC Desc.";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 81, 269, 0),
(2473, 'textInput', 'a:13:{s:4:"name";s:15:"form_email_from";s:7:"caption";s:13:"Sender''s Name";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 18, 269, 0),
(2474, 'checkBox', 'a:9:{s:4:"name";s:22:"email_notify_new_users";s:7:"caption";s:32:"Notify when a new user registers";s:8:"required";s:0:"";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:11:"label_class";s:0:"";s:7:"checked";s:0:"";}', 14, 269, 0),
(2475, 'textInput', 'a:13:{s:4:"name";s:10:"pass_regex";s:7:"caption";s:24:"Password Permitted Regex";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 11, 269, 0),
(2476, 'textInput', 'a:13:{s:4:"name";s:14:"pass_min_chars";s:7:"caption";s:20:"Password Min. Chars.";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 12, 269, 0),
(2477, 'checkBox', 'a:9:{s:4:"name";s:13:"auth_db_debug";s:7:"caption";s:16:"DB Debug On Fail";s:8:"required";s:0:"";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:11:"label_class";s:0:"";s:7:"checked";s:0:"";}', 51, 269, 0),
(2478, 'textInput', 'a:13:{s:4:"name";s:19:"bitcoin_reserve_min";s:7:"caption";s:40:"Reserve Min. BTC (for Send to Warm Wal.)";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 33, 269, 0),
(2479, 'textInput', 'a:13:{s:4:"name";s:21:"bitcoin_reserve_ratio";s:7:"caption";s:31:"Reserve Ratio (% in Hot Wallet)";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 34, 269, 0),
(2480, 'textInput', 'a:13:{s:4:"name";s:27:"bitcoin_warm_wallet_address";s:7:"caption";s:21:"Warm Wallet BTC Addr.";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 35, 269, 0),
(2481, 'checkBox', 'a:9:{s:4:"name";s:13:"cron_db_debug";s:7:"caption";s:16:"DB Debug On Fail";s:8:"required";s:0:"";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:11:"label_class";s:0:"";s:7:"checked";s:0:"";}', 54, 269, 0),
(2482, 'textInput', 'a:13:{s:4:"name";s:14:"quandl_api_key";s:7:"caption";s:14:"Quandl API Key";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 39, 269, 0),
(2483, 'textInput', 'a:13:{s:4:"name";s:12:"cron_dirroot";s:7:"caption";s:8:"Dir Root";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 55, 269, 0),
(2484, 'startArea', 'a:3:{s:6:"legend";s:16:"Backstage Config";s:5:"class";s:3:"box";s:6:"height";s:0:"";}', 57, 269, 0),
(2485, 'checkBox', 'a:9:{s:4:"name";s:18:"backstage_db_debug";s:7:"caption";s:16:"DB Debug On Fail";s:8:"required";s:0:"";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:11:"label_class";s:0:"";s:7:"checked";s:0:"";}', 58, 269, 0),
(2486, 'textInput', 'a:13:{s:4:"name";s:17:"backstage_dirroot";s:7:"caption";s:8:"Dir Root";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 59, 269, 0),
(2487, 'endArea', '', 60, 269, 0);

UPDATE`status` SET `db_version` = '1.04' WHERE `status`.`id` =1;
