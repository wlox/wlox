ALTER TABLE app_configuration ADD `cloudflare_api_key` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `google_recaptch_api_key` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `google_recaptch_api_secret` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `cloudflare_blacklist` ENUM('Y','N') NOT NULL DEFAULT 'N';
ALTER TABLE app_configuration ADD `cloudflare_email` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `cloudflare_blacklist_attempts` INT( 10 ) NOT NULL;
ALTER TABLE app_configuration ADD `cloudflare_blacklist_timeframe` DOUBLE( 10, 2 ) NOT NULL;
ALTER TABLE app_configuration ADD `email_notify_fiat_withdrawals` ENUM('Y','N') NOT NULL DEFAULT 'N';
ALTER TABLE app_configuration ADD `contact_email` VARCHAR( 255 ) NOT NULL;

INSERT INTO `admin_pages` (`id`, `f_id`, `name`, `url`, `icon`, `order`, `page_map_reorders`, `one_record`) VALUES (94, 64, 'App Configuration', 'app-configuration', '', 0, 0, 'Y');

INSERT INTO `emails` (`id`, `key`, `title_en`, `title_es`, `content_en`, `content_es`) VALUES
(24, 'bruteforce-notify', 'Multiple Failed Login Attempts', 'Múltiples Intentos de Login Incorrectos', 0x3c703e44656172205b66697273745f6e616d655d205b6c6173745f6e616d655d2c3c2f703e0d0a0d0a3c703e57652068617665206465746563746564206d756c7469706c65206661696c656420617474656d70747320746f206c6f6720696e746f20796f7572206163636f756e742e205b65786368616e67655f6e616d655d262333393b73207465616d20697320616c72656164792074616b696e672074686520617070726f707269617465206d6561737572657320746f2070726f7465637420796f7572206163636f756e742066726f6d2074686520706f74656e7469616c2061747461636b65722e205468657365206d65617375726573206d617920636175736520796f7572206163636f756e7420746f2062652074656d706f726172696c7920696e61636365737369626c652e3c2f703e0d0a0d0a3c703e496620796f7572206163636f756e742072656d61696e7320696e61636365737369626c6520666f72206d6f7265207468616e20616e20686f75722c20706c6561736520636f6e74616374206f757220737570706f7274207465616d20736f2077652063616e2074616b65206164646974696f6e616c206d656173757265732e3c2f703e0d0a0d0a3c703e4265737420726567617264732c3c2f703e0d0a0d0a3c703e5b65786368616e67655f6e616d655d20537570706f7274205465616d3c2f703e0d0a, 0x3c703e457374696d61646f205b66697273745f6e616d655d205b6c6173745f6e616d655d2c3c2f703e0d0a0d0a3c703e48656d6f732064657465637461646f206d267561637574653b6c7469706c657320696e74656e746f7320696e636f72726563746f73206465206c6f67696e2061207375206375656e74612e20456c2065717569706f206465205b65786368616e67655f6e616d655d20796120657374266161637574653b20746f6d616e646f206d656469646173206170726f70696164617320706172612070726f7465676572207375206375656e74612064656c2061677265736f722e204573746173206d65646964617320706f6472266961637574653b616e2063617573617220717565207375206375656e7461207365612074656d706f72616c6d656e746520696e616363657369626c652e3c2f703e0d0a0d0a3c703e5369207375206375656e7461207065726d616e65636520696e616363657369626c6520706f72206d266161637574653b7320646520756e6120686f72612c20706f72206661766f7220636f6e74616374652061206e75657374726f20736f706f7274652074266561637574653b636e69636f207061726120746f6d6172206d6564696461732061646963696f6e616c65732e3c2f703e0d0a0d0a3c703e4174656e74616d656e74652c3c2f703e0d0a0d0a3c703e536f706f7274652054266561637574653b636e69636f206465205b65786368616e67655f6e616d655d3c2f703e0d0a);

INSERT INTO `lang` (`id`, `key`, `esp`, `eng`, `order`, `p_id`) VALUES
(445, 'google-recaptcha-error', 'Por favor seleccione la casilla en el Google ReCaptcha!', 'Please check the Google ReCaptcha!', '', 26, '', ''),
(446, 'google-recaptcha-connection', 'No hay conección al servicio de reCaptcha.', 'Could not connect to the reCaptcha service.', '', 26, '', ''),
(447, 'login-timeout', 'Por favor espere [timeout] antes de intentar hacer login.', 'Please wait [timeout] until your next login attempt.', '', 27, '', '');

INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES (2488, 'checkBox', 'a:9:{s:4:"name";s:29:"email_notify_fiat_withdrawals";s:7:"caption";s:28:"Notify user fiat withdrawals";s:8:"required";s:0:"";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:11:"label_class";s:0:"";s:7:"checked";s:0:"";}', 14, 269, 0);

INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES (2489, 'textInput', 'a:13:{s:4:"name";s:13:"contact_email";s:7:"caption";s:18:"Contact Form Email";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 19, 269, 0);

INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(2491, 'field', 'a:18:{s:4:"name";s:14:"request_status";s:8:"subtable";s:14:"request_status";s:14:"header_caption";s:11:"Req. Status";s:6:"filter";s:1:"Y";s:8:"link_url";s:0:"";s:15:"subtable_fields";a:1:{s:7:"name_en";s:7:"name_en";}s:22:"subtable_fields_concat";s:0:"";s:5:"class";s:0:"";s:18:"aggregate_function";s:0:"";s:12:"thumb_amount";s:0:"";s:12:"media_amount";s:0:"";s:10:"media_size";s:0:"";s:10:"f_id_field";s:72:"history.request_id,requests.id,requests.request_status,request_status.id";s:8:"order_by";s:0:"";s:9:"order_asc";s:0:"";s:11:"link_is_tab";s:0:"";s:16:"limit_is_curdate";s:0:"";s:8:"is_media";s:0:"";}', 8, 263, 0);

UPDATE `admin_controls` SET `id` = 263,`page_id` = 91,`tab_id` = 0,`action` = '',`class` = 'Grid',`arguments` = 'a:24:{s:5:"table";s:7:"history";s:4:"mode";s:0:"";s:13:"rows_per_page";s:0:"";s:5:"class";s:0:"";s:12:"show_buttons";s:1:"Y";s:14:"target_elem_id";s:8:"edit_box";s:9:"max_pages";s:0:"";s:16:"pagination_label";s:0:"";s:18:"show_list_captions";s:0:"";s:8:"order_by";s:4:"date";s:9:"order_asc";s:0:"";s:10:"save_order";s:0:"";s:12:"enable_graph";s:0:"";s:12:"enable_table";s:0:"";s:11:"enable_list";s:0:"";s:17:"enable_graph_line";s:0:"";s:16:"enable_graph_pie";s:0:"";s:15:"button_link_url";s:0:"";s:18:"button_link_is_tab";s:0:"";s:10:"sql_filter";s:0:"";s:16:"alert_condition1";s:51:"[request_id] > 0 && [request_status] != ''Completed''";s:16:"alert_condition2";s:0:"";s:8:"group_by";s:0:"";s:11:"no_group_by";s:0:"";}',`order` = 0,`is_static` = 'N' WHERE `admin_controls`.`id` = 263;

INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(2492, 'textInput', 'a:13:{s:4:"name";s:18:"cloudflare_api_key";s:7:"caption";s:18:"CloudFlare API Key";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 43, 269, 0),
(2493, 'textInput', 'a:13:{s:4:"name";s:23:"google_recaptch_api_key";s:7:"caption";s:24:"Google ReCaptcha API Key";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 44, 269, 0),
(2494, 'checkBox', 'a:9:{s:4:"name";s:20:"cloudflare_blacklist";s:7:"caption";s:47:"Blacklist Attacker''s IP''s (Req. CloudFlare API)";s:8:"required";s:0:"";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:11:"label_class";s:0:"";s:7:"checked";s:0:"";}', 15, 269, 0),
(2495, 'textInput', 'a:13:{s:4:"name";s:16:"cloudflare_email";s:7:"caption";s:24:"CloudFlare Email Address";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 43, 269, 0);

INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(2496, 'textInput', 'a:13:{s:4:"name";s:26:"google_recaptch_api_secret";s:7:"caption";s:27:"Google ReCaptcha API Secret";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 47, 269, 0);

INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(2497, 'startArea', 'a:3:{s:6:"legend";s:13:"IP Throttling";s:5:"class";s:3:"box";s:6:"height";s:0:"";}', 17, 269, 0),
(2498, 'endArea', '', 21, 269, 0),
(2499, 'textInput', 'a:13:{s:4:"name";s:30:"cloudflare_blacklist_timeframe";s:7:"caption";s:29:"Blacklist Timeframe (minutes)";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:7:"decimal";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 19, 269, 0);

INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(2500, 'textInput', 'a:13:{s:4:"name";s:29:"cloudflare_blacklist_attempts";s:7:"caption";s:40:"Allowed Attempts per IP per Minute (int)";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:3:"int";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 20, 269, 0);

UPDATE `admin_controls_methods` SET `id` = 2397,`order` = 2 WHERE `admin_controls_methods`.`id` = 2397;
UPDATE `admin_controls_methods` SET `id` = 2398,`order` = 16 WHERE `admin_controls_methods`.`id` = 2398;
UPDATE `admin_controls_methods` SET `id` = 2399,`order` = 57 WHERE `admin_controls_methods`.`id` = 2399;
UPDATE `admin_controls_methods` SET `id` = 2400,`order` = 60 WHERE `admin_controls_methods`.`id` = 2400;
UPDATE `admin_controls_methods` SET `id` = 2401,`order` = 61 WHERE `admin_controls_methods`.`id` = 2401;
UPDATE `admin_controls_methods` SET `id` = 2402,`order` = 63 WHERE `admin_controls_methods`.`id` = 2402;
UPDATE `admin_controls_methods` SET `id` = 2403,`order` = 64 WHERE `admin_controls_methods`.`id` = 2403;
UPDATE `admin_controls_methods` SET `id` = 2404,`order` = 67 WHERE `admin_controls_methods`.`id` = 2404;
UPDATE `admin_controls_methods` SET `id` = 2405,`order` = 33 WHERE `admin_controls_methods`.`id` = 2405;
UPDATE `admin_controls_methods` SET `id` = 2406,`order` = 56 WHERE `admin_controls_methods`.`id` = 2406;
UPDATE `admin_controls_methods` SET `id` = 2407,`order` = 72 WHERE `admin_controls_methods`.`id` = 2407;
UPDATE `admin_controls_methods` SET `id` = 2408,`order` = 93 WHERE `admin_controls_methods`.`id` = 2408;
UPDATE `admin_controls_methods` SET `id` = 2409,`order` = 54 WHERE `admin_controls_methods`.`id` = 2409;
UPDATE `admin_controls_methods` SET `id` = 2410,`order` = 55 WHERE `admin_controls_methods`.`id` = 2410;
UPDATE `admin_controls_methods` SET `id` = 2412,`order` = 3 WHERE `admin_controls_methods`.`id` = 2412;
UPDATE `admin_controls_methods` SET `id` = 2413,`order` = 9 WHERE `admin_controls_methods`.`id` = 2413;
UPDATE `admin_controls_methods` SET `id` = 2414,`order` = 8 WHERE `admin_controls_methods`.`id` = 2414;
UPDATE `admin_controls_methods` SET `id` = 2415,`order` = 4 WHERE `admin_controls_methods`.`id` = 2415;
UPDATE `admin_controls_methods` SET `id` = 2416,`order` = 7 WHERE `admin_controls_methods`.`id` = 2416;
UPDATE `admin_controls_methods` SET `id` = 2417,`order` = 94 WHERE `admin_controls_methods`.`id` = 2417;
UPDATE `admin_controls_methods` SET `id` = 2418,`order` = 1 WHERE `admin_controls_methods`.`id` = 2418;
UPDATE `admin_controls_methods` SET `id` = 2422,`order` = 95 WHERE `admin_controls_methods`.`id` = 2422;
UPDATE `admin_controls_methods` SET `id` = 2421,`order` = 0 WHERE `admin_controls_methods`.`id` = 2421;
UPDATE `admin_controls_methods` SET `id` = 2423,`order` = 58 WHERE `admin_controls_methods`.`id` = 2423;
UPDATE `admin_controls_methods` SET `id` = 2425,`order` = 59 WHERE `admin_controls_methods`.`id` = 2425;
UPDATE `admin_controls_methods` SET `id` = 2450,`order` = 73 WHERE `admin_controls_methods`.`id` = 2450;
UPDATE `admin_controls_methods` SET `id` = 2427,`order` = 22 WHERE `admin_controls_methods`.`id` = 2427;
UPDATE `admin_controls_methods` SET `id` = 2428,`order` = 32 WHERE `admin_controls_methods`.`id` = 2428;
UPDATE `admin_controls_methods` SET `id` = 2429,`order` = 23 WHERE `admin_controls_methods`.`id` = 2429;
UPDATE `admin_controls_methods` SET `id` = 2430,`order` = 26 WHERE `admin_controls_methods`.`id` = 2430;
UPDATE `admin_controls_methods` SET `id` = 2431,`order` = 27 WHERE `admin_controls_methods`.`id` = 2431;
UPDATE `admin_controls_methods` SET `id` = 2432,`order` = 28 WHERE `admin_controls_methods`.`id` = 2432;
UPDATE `admin_controls_methods` SET `id` = 2433,`order` = 29 WHERE `admin_controls_methods`.`id` = 2433;
UPDATE `admin_controls_methods` SET `id` = 2434,`order` = 30 WHERE `admin_controls_methods`.`id` = 2434;
UPDATE `admin_controls_methods` SET `id` = 2435,`order` = 31 WHERE `admin_controls_methods`.`id` = 2435;
UPDATE `admin_controls_methods` SET `id` = 2436,`order` = 44 WHERE `admin_controls_methods`.`id` = 2436;
UPDATE `admin_controls_methods` SET `id` = 2437,`order` = 43 WHERE `admin_controls_methods`.`id` = 2437;
UPDATE `admin_controls_methods` SET `id` = 2438,`order` = 34 WHERE `admin_controls_methods`.`id` = 2438;
UPDATE `admin_controls_methods` SET `id` = 2439,`order` = 35 WHERE `admin_controls_methods`.`id` = 2439;
UPDATE `admin_controls_methods` SET `id` = 2440,`order` = 36 WHERE `admin_controls_methods`.`id` = 2440;
UPDATE `admin_controls_methods` SET `id` = 2441,`order` = 37 WHERE `admin_controls_methods`.`id` = 2441;
UPDATE `admin_controls_methods` SET `id` = 2442,`order` = 38 WHERE `admin_controls_methods`.`id` = 2442;
UPDATE `admin_controls_methods` SET `id` = 2443,`order` = 39 WHERE `admin_controls_methods`.`id` = 2443;
UPDATE `admin_controls_methods` SET `id` = 2444,`order` = 53 WHERE `admin_controls_methods`.`id` = 2444;
UPDATE `admin_controls_methods` SET `id` = 2445,`order` = 52 WHERE `admin_controls_methods`.`id` = 2445;
UPDATE `admin_controls_methods` SET `id` = 2446,`order` = 45 WHERE `admin_controls_methods`.`id` = 2446;
UPDATE `admin_controls_methods` SET `id` = 2447,`order` = 47 WHERE `admin_controls_methods`.`id` = 2447;
UPDATE `admin_controls_methods` SET `id` = 2448,`order` = 6 WHERE `admin_controls_methods`.`id` = 2448;
UPDATE `admin_controls_methods` SET `id` = 2449,`order` = 10 WHERE `admin_controls_methods`.`id` = 2449;
UPDATE `admin_controls_methods` SET `id` = 2452,`order` = 13 WHERE `admin_controls_methods`.`id` = 2452;
UPDATE `admin_controls_methods` SET `id` = 2453,`order` = 5 WHERE `admin_controls_methods`.`id` = 2453;
UPDATE `admin_controls_methods` SET `id` = 2454,`order` = 74 WHERE `admin_controls_methods`.`id` = 2454;
UPDATE `admin_controls_methods` SET `id` = 2455,`order` = 75 WHERE `admin_controls_methods`.`id` = 2455;
UPDATE `admin_controls_methods` SET `id` = 2456,`order` = 76 WHERE `admin_controls_methods`.`id` = 2456;
UPDATE `admin_controls_methods` SET `id` = 2457,`order` = 77 WHERE `admin_controls_methods`.`id` = 2457;
UPDATE `admin_controls_methods` SET `id` = 2458,`order` = 78 WHERE `admin_controls_methods`.`id` = 2458;
UPDATE `admin_controls_methods` SET `id` = 2459,`order` = 79 WHERE `admin_controls_methods`.`id` = 2459;
UPDATE `admin_controls_methods` SET `id` = 2460,`order` = 80 WHERE `admin_controls_methods`.`id` = 2460;
UPDATE `admin_controls_methods` SET `id` = 2461,`order` = 81 WHERE `admin_controls_methods`.`id` = 2461;
UPDATE `admin_controls_methods` SET `id` = 2462,`order` = 83 WHERE `admin_controls_methods`.`id` = 2462;
UPDATE `admin_controls_methods` SET `id` = 2463,`order` = 84 WHERE `admin_controls_methods`.`id` = 2463;
UPDATE `admin_controls_methods` SET `id` = 2464,`order` = 85 WHERE `admin_controls_methods`.`id` = 2464;
UPDATE `admin_controls_methods` SET `id` = 2465,`order` = 82 WHERE `admin_controls_methods`.`id` = 2465;
UPDATE `admin_controls_methods` SET `id` = 2466,`order` = 86 WHERE `admin_controls_methods`.`id` = 2466;
UPDATE `admin_controls_methods` SET `id` = 2467,`order` = 87 WHERE `admin_controls_methods`.`id` = 2467;
UPDATE `admin_controls_methods` SET `id` = 2468,`order` = 88 WHERE `admin_controls_methods`.`id` = 2468;
UPDATE `admin_controls_methods` SET `id` = 2469,`order` = 89 WHERE `admin_controls_methods`.`id` = 2469;
UPDATE `admin_controls_methods` SET `id` = 2470,`order` = 90 WHERE `admin_controls_methods`.`id` = 2470;
UPDATE `admin_controls_methods` SET `id` = 2471,`order` = 91 WHERE `admin_controls_methods`.`id` = 2471;
UPDATE `admin_controls_methods` SET `id` = 2472,`order` = 92 WHERE `admin_controls_methods`.`id` = 2472;
UPDATE `admin_controls_methods` SET `id` = 2473,`order` = 25 WHERE `admin_controls_methods`.`id` = 2473;
UPDATE `admin_controls_methods` SET `id` = 2474,`order` = 14 WHERE `admin_controls_methods`.`id` = 2474;
UPDATE `admin_controls_methods` SET `id` = 2475,`order` = 11 WHERE `admin_controls_methods`.`id` = 2475;
UPDATE `admin_controls_methods` SET `id` = 2476,`order` = 12 WHERE `admin_controls_methods`.`id` = 2476;
UPDATE `admin_controls_methods` SET `id` = 2477,`order` = 62 WHERE `admin_controls_methods`.`id` = 2477;
UPDATE `admin_controls_methods` SET `id` = 2478,`order` = 40 WHERE `admin_controls_methods`.`id` = 2478;
UPDATE `admin_controls_methods` SET `id` = 2479,`order` = 41 WHERE `admin_controls_methods`.`id` = 2479;
UPDATE `admin_controls_methods` SET `id` = 2480,`order` = 42 WHERE `admin_controls_methods`.`id` = 2480;
UPDATE `admin_controls_methods` SET `id` = 2481,`order` = 65 WHERE `admin_controls_methods`.`id` = 2481;
UPDATE `admin_controls_methods` SET `id` = 2482,`order` = 46 WHERE `admin_controls_methods`.`id` = 2482;
UPDATE `admin_controls_methods` SET `id` = 2483,`order` = 66 WHERE `admin_controls_methods`.`id` = 2483;
UPDATE `admin_controls_methods` SET `id` = 2484,`order` = 68 WHERE `admin_controls_methods`.`id` = 2484;
UPDATE `admin_controls_methods` SET `id` = 2485,`order` = 69 WHERE `admin_controls_methods`.`id` = 2485;
UPDATE `admin_controls_methods` SET `id` = 2486,`order` = 70 WHERE `admin_controls_methods`.`id` = 2486;
UPDATE `admin_controls_methods` SET `id` = 2487,`order` = 71 WHERE `admin_controls_methods`.`id` = 2487;
UPDATE `admin_controls_methods` SET `id` = 2488,`order` = 15 WHERE `admin_controls_methods`.`id` = 2488;
UPDATE `admin_controls_methods` SET `id` = 2489,`order` = 24 WHERE `admin_controls_methods`.`id` = 2489;
UPDATE `admin_controls_methods` SET `id` = 2492,`order` = 48 WHERE `admin_controls_methods`.`id` = 2492;
UPDATE `admin_controls_methods` SET `id` = 2493,`order` = 50 WHERE `admin_controls_methods`.`id` = 2493;
UPDATE `admin_controls_methods` SET `id` = 2494,`order` = 18 WHERE `admin_controls_methods`.`id` = 2494;
UPDATE `admin_controls_methods` SET `id` = 2495,`order` = 49 WHERE `admin_controls_methods`.`id` = 2495;
UPDATE `admin_controls_methods` SET `id` = 2496,`order` = 51 WHERE `admin_controls_methods`.`id` = 2496;
UPDATE `admin_controls_methods` SET `id` = 2497,`order` = 17 WHERE `admin_controls_methods`.`id` = 2497;
UPDATE `admin_controls_methods` SET `id` = 2498,`order` = 21 WHERE `admin_controls_methods`.`id` = 2498;
UPDATE `admin_controls_methods` SET `id` = 2499,`order` = 19 WHERE `admin_controls_methods`.`id` = 2499;
UPDATE `admin_controls_methods` SET `id` = 2500,`order` = 20 WHERE `admin_controls_methods`.`id` = 2500;

CREATE TABLE IF NOT EXISTS `site_users_access` (
  `site_user` int(10) unsigned NOT NULL,
  `start` bigint(20) unsigned NOT NULL,
  `last` bigint(20) unsigned NOT NULL,
  `attempts` int(10) unsigned NOT NULL,
  PRIMARY KEY (`site_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `site_users_catch` (
  `site_user` int(10) unsigned NOT NULL,
  `attempts` int(10) unsigned NOT NULL,
  PRIMARY KEY (`site_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ip_access_log` (
  `ip` bigint(20) NOT NULL,
  `timestamp` datetime NOT NULL,
  `login` enum('Y','N') NOT NULL DEFAULT 'N',
  KEY `ip` (`ip`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE`status` SET `db_version` = '1.05' WHERE `status`.`id` =1;
