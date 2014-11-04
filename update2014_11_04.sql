ALTER TABLE admin_tabs ADD one_record ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N';
ALTER TABLE admin_pages ADD one_record ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N';
ALTER TABLE status ADD `trading_status` VARCHAR( 255 ) NOT NULL DEFAULT 'enabled';
ALTER TABLE status ADD `withdrawals_status` VARCHAR( 255 ) NOT NULL DEFAULT 'enabled';

INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(2299, 'startArea', 'a:3:{s:6:"legend";s:20:"Emergency Operations";s:5:"class";s:8:"box_left";s:6:"height";s:0:"";}', 45, 244, 0),
(2300, 'endArea', '', 48, 244, 0),
(2303, 'selectInput', 'a:20:{s:4:"name";s:14:"trading_status";s:7:"caption";s:21:"Global Trading Status";s:8:"required";s:0:"";s:5:"value";s:0:"";s:13:"options_array";a:2:{s:7:"enabled";s:7:"Enabled";s:9:"suspended";s:9:"Suspended";}s:8:"subtable";s:0:"";s:15:"subtable_fields";s:0:"";s:13:"subtable_f_id";s:0:"";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:10:"f_id_field";s:0:"";s:12:"default_text";s:0:"";s:10:"depends_on";s:0:"";s:20:"function_to_elements";s:0:"";s:16:"first_is_default";s:1:"1";s:5:"level";s:0:"";s:11:"concat_char";s:0:"";s:9:"is_catsel";s:0:"";}', 46, 244, 0),
(2304, 'selectInput', 'a:20:{s:4:"name";s:18:"withdrawals_status";s:7:"caption";s:25:"Global Withdrawals Status";s:8:"required";s:0:"";s:5:"value";s:0:"";s:13:"options_array";a:2:{s:7:"enabled";s:7:"Enabled";s:9:"suspended";s:9:"Suspended";}s:8:"subtable";s:0:"";s:15:"subtable_fields";s:0:"";s:13:"subtable_f_id";s:0:"";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:10:"f_id_field";s:0:"";s:12:"default_text";s:0:"";s:10:"depends_on";s:0:"";s:20:"function_to_elements";s:0:"";s:16:"first_is_default";s:1:"1";s:5:"level";s:0:"";s:11:"concat_char";s:0:"";s:9:"is_catsel";s:0:"";}', 47, 244, 0);

INSERT INTO `lang` (`id`, `key`, `esp`, `eng`, `order`, `p_id`) VALUES
(430, 'buy-trading-disabled', 'Trading ha sido suspendido temporalmente por motivos de mantenimiento. Se reanudará en breve.', 'Trading has been suspended temporarily for maintenance purposes. It will resume shortly.', '', 17),
(431, 'withdrawal-suspended', 'Los retiros han sido suspendidos temporalmente por motivos de mantenimiento. Volverán a funcionar en breve.', 'Withdrawals are temporarily suspended for maintenance purposes. They will function again shortly.', '', 23);


