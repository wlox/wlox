ALTER TABLE `change_settings` ADD `email_token` VARCHAR( 255 ) NOT NULL;
ALTER TABLE `requests` ADD `email_token` VARCHAR( 255 ) NOT NULL;
ALTER TABLE requests ADD `approved` ENUM('Y','N') NOT NULL DEFAULT 'N';
ALTER TABLE app_configuration ADD `withdrawals_btc_manual_approval` ENUM('Y','N') NOT NULL DEFAULT 'N';
ALTER TABLE app_configuration ADD `withdrawals_fiat_manual_approval` ENUM('Y','N') NOT NULL DEFAULT 'N';
ALTER TABLE `status` CHANGE `db_version` `db_version` VARCHAR( 255 ) NOT NULL;

INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(2561, 'checkBox', 'a:9:{s:4:"name";s:8:"approved";s:7:"caption";s:18:"Manually Approved?";s:8:"required";s:0:"";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:11:"label_class";s:0:"";s:7:"checked";s:0:"";}', 15, 225, 0),
(2562, 'hiddenInput', 'a:8:{s:4:"name";s:11:"email_token";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:7:"jscript";s:0:"";s:20:"is_current_timestamp";s:0:"";s:15:"on_every_update";s:0:"";}', 17, 225, 0),
(2563, 'checkBox', 'a:9:{s:4:"name";s:31:"withdrawals_btc_manual_approval";s:7:"caption";s:28:"Withdrw. BTC Manual Approval";s:8:"required";s:0:"";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:11:"label_class";s:0:"";s:7:"checked";s:0:"";}', 5, 269, 0),
(2564, 'checkBox', 'a:9:{s:4:"name";s:32:"withdrawals_fiat_manual_approval";s:7:"caption";s:29:"Withdrw. Fiat Manual Approval";s:8:"required";s:0:"";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:11:"label_class";s:0:"";s:7:"checked";s:0:"";}', 6, 269, 0);

UPDATE`status` SET `db_version` = '1.13.5' WHERE `status`.`id` =1;
