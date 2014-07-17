
INSERT INTO `lang` (`id`, `key`, `esp`, `eng`, `order`, `p_id`) VALUES
(335, 'buy-stop-lower-ask', 'El precio del stop debe mayor al precio de demanda actual.', 'The stop price must be higher than the current ask.', '', 17),
(336, 'sell-stop-higher-bid', 'El precio del stop debe ser menor al de la mejor oferta actual.', 'The stop price must be lower than the current bid.', '', 17),
(337, 'buy-stop-price', 'Precio de Stop', 'Stop Price', '', 17),
(338, 'buy-stop', 'Órden stop', 'Stop Order', '', 17);

INSERT INTO `lang` (`id`, `key`, `esp`, `eng`, `order`, `p_id`) VALUES
(339, 'buy-limit', 'Órden limitada', 'Limit Order', '', 17),
(340, 'buy-limit-price', 'Precio límite', 'Limit Price', '', 17),
(341, 'buy-stop-lower-price', 'El precio del stop debe ser mayor al precio límite.', 'The stop price must be higher than the limit price.', '', 17),
(342, 'sell-stop-lower-price', 'El precio del stop debe ser menor al precio límite.', 'The stop price must be lower than the limit price.', '', 17),
(343, 'buy-errors-no-stop', 'Debe ingresar un precio para el stop.', 'You must enter a stop price.', '', 17),
(344, 'buy-notify-two-orders', 'Usted está creando dos órdenes ligadas. La ejecución de uno cancelará el otro.', 'You are placing two linked orders. Execution of one will cancel the other (OCO).', '', 17);


ALTER TABLE orders ADD `stop_price` DOUBLE( 10, 2 ) NOT NULL;
ALTER TABLE order_log ADD `stop_price` VARCHAR( 255 ) NOT NULL;

INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(4190, 'textInput', 'a:13:{s:4:"name";s:10:"stop_price";s:7:"caption";s:10:"Stop Price";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:7:"decimal";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 9, 210, 0),
(4191, 'field', 'a:14:{s:4:"name";s:10:"stop_price";s:7:"caption";s:10:"Stop Price";s:8:"subtable";s:0:"";s:15:"subtable_fields";s:0:"";s:8:"link_url";s:0:"";s:11:"concat_char";s:0:"";s:7:"in_form";s:0:"";s:10:"f_id_field";s:0:"";s:8:"order_by";s:0:"";s:9:"order_asc";s:0:"";s:11:"link_is_tab";s:0:"";s:16:"limit_is_curdate";s:0:"";s:14:"override_value";s:0:"";s:13:"link_id_field";s:0:"";}', 9, 214, 0),
(4192, 'field', 'a:18:{s:4:"name";s:10:"stop_price";s:8:"subtable";s:0:"";s:14:"header_caption";s:4:"Stop";s:6:"filter";s:1:"Y";s:8:"link_url";s:0:"";s:15:"subtable_fields";s:0:"";s:22:"subtable_fields_concat";s:0:"";s:5:"class";s:0:"";s:18:"aggregate_function";s:0:"";s:12:"thumb_amount";s:0:"";s:12:"media_amount";s:0:"";s:10:"media_size";s:0:"";s:10:"f_id_field";s:0:"";s:8:"order_by";s:0:"";s:9:"order_asc";s:0:"";s:11:"link_is_tab";s:0:"";s:16:"limit_is_curdate";s:0:"";s:8:"is_media";s:0:"";}', 9, 215, 0),
(4193, 'textInput', 'a:13:{s:4:"name";s:10:"stop_price";s:7:"caption";s:10:"Stop Price";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 9, 252, 0),
(4194, 'field', 'a:14:{s:4:"name";s:10:"stop_price";s:7:"caption";s:10:"Stop Price";s:8:"subtable";s:0:"";s:15:"subtable_fields";s:0:"";s:8:"link_url";s:0:"";s:11:"concat_char";s:0:"";s:7:"in_form";s:0:"";s:10:"f_id_field";s:0:"";s:8:"order_by";s:0:"";s:9:"order_asc";s:0:"";s:11:"link_is_tab";s:0:"";s:16:"limit_is_curdate";s:0:"";s:14:"override_value";s:0:"";s:13:"link_id_field";s:0:"";}', 9, 253, 0),
(4195, 'field', 'a:18:{s:4:"name";s:10:"stop_price";s:8:"subtable";s:0:"";s:14:"header_caption";s:4:"Stop";s:6:"filter";s:1:"Y";s:8:"link_url";s:0:"";s:15:"subtable_fields";s:0:"";s:22:"subtable_fields_concat";s:0:"";s:5:"class";s:0:"";s:18:"aggregate_function";s:0:"";s:12:"thumb_amount";s:0:"";s:12:"media_amount";s:0:"";s:10:"media_size";s:0:"";s:10:"f_id_field";s:0:"";s:8:"order_by";s:0:"";s:9:"order_asc";s:0:"";s:11:"link_is_tab";s:0:"";s:16:"limit_is_curdate";s:0:"";s:8:"is_media";s:0:"";}', 10, 254, 0);

