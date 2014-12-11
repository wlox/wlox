ALTER TABLE requests ADD `fee` DOUBLE( 16, 8 ) NOT NULL;
ALTER TABLE requests ADD `net_amount` DOUBLE( 16, 8 ) NOT NULL;
ALTER TABLE site_users ADD `trusted` ENUM('Y','N') NOT NULL DEFAULT 'N';
ALTER TABLE `site_users` ADD INDEX ( `trusted` ) ;
ALTER TABLE `change_settings` ADD `site_user` INT( 10 ) UNSIGNED NOT NULL ,
ADD INDEX ( `site_user` );

INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(2329, 'textInput', 'a:13:{s:4:"name";s:3:"fee";s:7:"caption";s:3:"Fee";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:7:"decimal";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 6, 225, 0),
(2330, 'textInput', 'a:13:{s:4:"name";s:10:"net_amount";s:7:"caption";s:10:"Net Amount";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:7:"decimal";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 7, 225, 0),
(2331, 'field', 'a:14:{s:4:"name";s:3:"fee";s:7:"caption";s:3:"Fee";s:8:"subtable";s:0:"";s:15:"subtable_fields";s:0:"";s:8:"link_url";s:0:"";s:11:"concat_char";s:0:"";s:7:"in_form";s:0:"";s:10:"f_id_field";s:0:"";s:8:"order_by";s:0:"";s:9:"order_asc";s:0:"";s:11:"link_is_tab";s:0:"";s:16:"limit_is_curdate";s:0:"";s:14:"override_value";s:0:"";s:13:"link_id_field";s:0:"";}', 6, 232, 0),
(2332, 'field', 'a:14:{s:4:"name";s:10:"net_amount";s:7:"caption";s:10:"Net Amount";s:8:"subtable";s:0:"";s:15:"subtable_fields";s:0:"";s:8:"link_url";s:0:"";s:11:"concat_char";s:0:"";s:7:"in_form";s:0:"";s:10:"f_id_field";s:0:"";s:8:"order_by";s:0:"";s:9:"order_asc";s:0:"";s:11:"link_is_tab";s:0:"";s:16:"limit_is_curdate";s:0:"";s:14:"override_value";s:0:"";s:13:"link_id_field";s:0:"";}', 7, 232, 0),
(2333, 'field', 'a:18:{s:4:"name";s:10:"net_amount";s:8:"subtable";s:0:"";s:14:"header_caption";s:7:"Net Am.";s:6:"filter";s:1:"Y";s:8:"link_url";s:0:"";s:15:"subtable_fields";s:0:"";s:22:"subtable_fields_concat";s:0:"";s:5:"class";s:0:"";s:18:"aggregate_function";s:0:"";s:12:"thumb_amount";s:0:"";s:12:"media_amount";s:0:"";s:10:"media_size";s:0:"";s:10:"f_id_field";s:0:"";s:8:"order_by";s:0:"";s:9:"order_asc";s:0:"";s:11:"link_is_tab";s:0:"";s:16:"limit_is_curdate";s:0:"";s:8:"is_media";s:0:"";}', 11, 233, 0);

INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(2336, 'textInput', 'a:13:{s:4:"name";s:14:"transaction_id";s:7:"caption";s:14:"Transaction ID";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 12, 225, 0),
(2338, 'field', 'a:14:{s:4:"name";s:14:"transaction_id";s:7:"caption";s:14:"Transaction ID";s:8:"subtable";s:0:"";s:15:"subtable_fields";s:0:"";s:8:"link_url";s:0:"";s:11:"concat_char";s:0:"";s:7:"in_form";s:0:"";s:10:"f_id_field";s:0:"";s:8:"order_by";s:0:"";s:9:"order_asc";s:0:"";s:11:"link_is_tab";s:0:"";s:16:"limit_is_curdate";s:0:"";s:14:"override_value";s:0:"";s:13:"link_id_field";s:0:"";}', 11, 232, 0);


INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(2334, 'checkBox', 'a:9:{s:4:"name";s:7:"trusted";s:7:"caption";s:11:"Is Trusted?";s:8:"required";s:0:"";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:11:"label_class";s:0:"";s:7:"checked";s:0:"";}', 32, 130, 0);

INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(2335, 'filterAutocomplete', 'a:6:{s:10:"field_name";s:9:"site_user";s:7:"caption";s:11:"Filter User";s:13:"options_array";s:0:"";s:8:"subtable";s:10:"site_users";s:15:"subtable_fields";a:4:{s:4:"user";s:4:"user";s:10:"first_name";s:10:"first_name";s:9:"last_name";s:9:"last_name";s:5:"email";s:5:"email";}s:5:"class";s:0:"";}', 6, 243, 0);


UPDATE `admin_controls_methods` SET `id` = 2041,`method` = 'passiveField',`arguments` = 'a:14:{s:4:"name";s:4:"p_id";s:7:"caption";s:11:"Edited From";s:8:"subtable";s:0:"";s:15:"subtable_fields";s:0:"";s:8:"link_url";s:11:"order-log&1";s:11:"concat_char";s:0:"";s:10:"f_id_field";s:0:"";s:8:"order_by";s:0:"";s:9:"order_asc";s:0:"";s:11:"link_is_tab";s:0:"";s:16:"limit_is_curdate";s:0:"";s:15:"create_db_field";s:3:"int";s:13:"default_value";s:0:"";s:23:"dont_fill_automatically";s:0:"";}',`order` = 12,`control_id` = 252,`p_id` = 0 WHERE `admin_controls_methods`.`id` = 2041;
UPDATE `admin_controls_methods` SET `id` = 2055,`method` = 'field',`arguments` = 'a:14:{s:4:"name";s:4:"p_id";s:7:"caption";s:11:"Edited From";s:8:"subtable";s:0:"";s:15:"subtable_fields";s:0:"";s:8:"link_url";s:11:"order-log&1";s:11:"concat_char";s:0:"";s:7:"in_form";s:0:"";s:10:"f_id_field";s:0:"";s:8:"order_by";s:0:"";s:9:"order_asc";s:0:"";s:11:"link_is_tab";s:0:"";s:16:"limit_is_curdate";s:0:"";s:14:"override_value";s:0:"";s:13:"link_id_field";s:0:"";}',`order` = 11,`control_id` = 253,`p_id` = 0 WHERE `admin_controls_methods`.`id` = 2055;


INSERT INTO `lang` (`id`, `key`, `esp`, `eng`, `order`, `p_id`) VALUES
(438, 'withdraw-network-fee', 'Cuota del Blockchain', 'Blockchain Fee', '', 23),
(439, 'withdraw-network-fee-explain', 'La cuota que cobra la red por transacciones.', 'The fee the network charges for transactions.', '', 23),
(440, 'withdraw-btc-total', 'BTC a recibir', 'BTC to Receive', '', 23),
(441, 'withdraw-total', '[currency] a recibir', '[currency] to Receive', '', 23),
(442, 'withdraw-net-amount', 'Monto Neto', 'Net Amount', '', 23);

UPDATE`status` SET `db_version` = '1.02' WHERE `status`.`id` =1;


