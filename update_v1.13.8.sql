ALTER TABLE `status` ADD `hot_wallet_notified` ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N';
ALTER TABLE status DROP status.trading_status;
ALTER TABLE status DROP status.withdrawals_status;
ALTER TABLE `status` ADD `btc_24h` DOUBLE( 10, 2 ) NOT NULL DEFAULT '0';

INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(2587, 'hiddenInput', 'a:8:{s:4:"name";s:19:"hot_wallet_notified";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:4:"enum";s:7:"jscript";s:0:"";s:20:"is_current_timestamp";s:0:"";s:15:"on_every_update";s:0:"";}', 10, 244, 0),
(2588, 'hiddenInput', 'a:8:{s:4:"name";s:7:"btc_24h";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:7:"decimal";s:7:"jscript";s:0:"";s:20:"is_current_timestamp";s:0:"";s:15:"on_every_update";s:0:"";}', 11, 244, 0);

UPDATE`status` SET `db_version` = '1.13.8' WHERE `status`.`id` =1;
