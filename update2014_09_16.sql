CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `secret` varchar(255) NOT NULL,
  `view` enum('Y','N') NOT NULL DEFAULT 'Y',
  `orders` enum('Y','N') NOT NULL DEFAULT 'N',
  `withdraw` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `api_keys` ADD `site_user` INT( 10 ) UNSIGNED NOT NULL ,
ADD INDEX ( `site_user` ) ;
ALTER TABLE `api_keys` ADD `nonce` INT( 10 ) UNSIGNED NOT NULL;

ALTER TABLE transactions ADD `bid_at_transaction` DOUBLE( 10, 2 ) NOT NULL;
ALTER TABLE transactions ADD `ask_at_transaction` DOUBLE( 10, 2 ) NOT NULL;
INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(2270, 'hiddenInput', 'a:8:{s:4:"name";s:18:"bid_at_transaction";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:7:"decimal";s:7:"jscript";s:0:"";s:20:"is_current_timestamp";s:0:"";s:15:"on_every_update";s:0:"";}', 0, 216, 0),
(2271, 'hiddenInput', 'a:8:{s:4:"name";s:18:"ask_at_transaction";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:7:"decimal";s:7:"jscript";s:0:"";s:20:"is_current_timestamp";s:0:"";s:15:"on_every_update";s:0:"";}', 0, 216, 0);





