
ALTER TABLE app_configuration ADD `crypto_capital_pk` VARCHAR( 255 ) NOT NULL;
ALTER TABLE `orders` ADD INDEX (`market_price`);
ALTER TABLE `requests` ADD INDEX (`notified`);
ALTER TABLE app_configuration ADD `deposit_fiat_desc` VARCHAR( 255 ) NOT NULL;
UPDATE `app_configuration` SET `deposit_fiat_desc` = '3' WHERE `id` = 1;


INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(2504, 'textInput', 'a:13:{s:4:"name";s:17:"crypto_capital_pk";s:7:"caption";s:26:"Crypto Capital Private Key";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 45, 269, 0),
(2503, 'textInput', 'a:13:{s:4:"name";s:17:"deposit_fiat_desc";s:7:"caption";s:21:"Deposit Fiat Desc. ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 76, 269, 0);


UPDATE`status` SET `db_version` = '1.09' WHERE `status`.`id` =1;
