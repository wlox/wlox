ALTER TABLE `transactions` ENGINE = InnoDB;
ALTER TABLE `conversions` ENGINE = InnoDB;
ALTER TABLE `fees` ENGINE = InnoDB;
ALTER TABLE `history` ENGINE = InnoDB;
ALTER TABLE `order_log` ENGINE = InnoDB;

ALTER TABLE app_configuration ADD `email_notify_fiat_failed` ENUM('Y','N') NOT NULL DEFAULT 'Y';

CREATE TABLE site_users_balances (id INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ) ENGINE = InnoDB DEFAULT CHARSET=latin1;
ALTER TABLE site_users_balances ADD `balance` DOUBLE(20,8) NOT NULL;
ALTER TABLE site_users_balances ADD `site_user` INT( 10 ) NOT NULL;
ALTER TABLE site_users_balances ADD `currency` INT( 10 ) NOT NULL;
ALTER TABLE `site_users_balances` ADD INDEX ( `site_user` ) ;
ALTER TABLE `site_users_balances` ADD INDEX ( `currency` );

INSERT INTO site_users_balances (site_user,balance,currency) 
SELECT site_users1.id, site_users1.balance, currencies.id FROM
(
  SELECT id,btc AS balance, 'BTC' AS currency from site_users 
  union all
  SELECT id,eur,'EUR' from site_users 
  union all
  SELECT id,cny,'CNY' from site_users
  union all
  SELECT id,gbp,'GBP' from site_users
  union all
  SELECT id,jpy,'JPY' from site_users
  union all
  SELECT id,cad,'CAD' from site_users
  union all
  SELECT id,bgn,'BGN' from site_users
  union all
  SELECT id,czk,'CZK' from site_users
  union all
  SELECT id,dkk,'DKK' from site_users
  union all
  SELECT id,hkd,'HKD' from site_users
  union all
  SELECT id,hrk,'HRK' from site_users
  union all
  SELECT id,huf,'HUF' from site_users
  union all
  SELECT id,ils,'ILS' from site_users
  union all
  SELECT id,inr,'INR' from site_users
  union all
  SELECT id,ltl,'LTL' from site_users
  union all
  SELECT id,lvl,'LVL' from site_users
  union all
  SELECT id,mxn,'MXN' from site_users
  union all
  SELECT id,nok,'NOK' from site_users
  union all
  SELECT id,nzd,'NZD' from site_users
  union all
  SELECT id,pln,'PLN' from site_users
  union all
  SELECT id,ron,'RON' from site_users
  union all
  SELECT id,rub,'RUB' from site_users
  union all
  SELECT id,sek,'SEK' from site_users
  union all
  SELECT id,sgd,'SGD' from site_users
  union all
  SELECT id,thb,'THB' from site_users
  union all
  SELECT id,try,'TRY' from site_users
  union all
  SELECT id,zar,'ZAR' from site_users
  union all
  SELECT id,usd,'USD' from site_users
  union all
  SELECT id,mur,'MUR' from site_users
  union all
  SELECT id,aud,'AUD' from site_users
  union all
  SELECT id,chf,'CHF' from site_users
) site_users1
LEFT JOIN currencies ON (BINARY site_users1.currency = BINARY currencies.currency)
WHERE site_users1.balance > 0;

ALTER TABLE `site_users`
  DROP `btc`,
  DROP `usd`,
  DROP `eur`,
  DROP `cny`,
  DROP `gbp`,
  DROP `cad`,
  DROP `jpy`,
  DROP `aud`,
  DROP `chf`,
  DROP `rub`,
  DROP `mxn`,
  DROP `hkd`,
  DROP `ils`,
  DROP `inr`,
  DROP `dkk`,
  DROP `huf`,
  DROP `lvl`,
  DROP `ltl`,
  DROP `nzd`,
  DROP `nok`,
  DROP `pln`,
  DROP `ron`,
  DROP `sgd`,
  DROP `zar`,
  DROP `sek`,
  DROP `thb`,
  DROP `try`,
  DROP `czk`,
  DROP `bgn`,
  DROP `hrk`,
  DROP `ars`,
  DROP `cop`,
  DROP `mur`,
  DROP `twd`;

ALTER TABLE `status` DROP `trading_status`;
ALTER TABLE `status` DROP `withdrawals_status`;

CREATE TABLE status_escrows (id INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ) ENGINE = InnoDB DEFAULT CHARSET=latin1;
ALTER TABLE status_escrows ADD `balance` DOUBLE(20,8) NOT NULL;
ALTER TABLE status_escrows ADD `currency` INT( 10 ) NOT NULL;
ALTER TABLE status_escrows ADD `status_id` INT( 10 ) NOT NULL;

INSERT INTO status_escrows (status_id,balance,currency) 
SELECT 1, status1.balance, currencies.id FROM
(
  SELECT id,btc_escrow AS balance, 'BTC' AS currency from status 
  union all
  SELECT id,eur_escrow,'EUR' from status 
  union all
  SELECT id,cny_escrow,'CNY' from status
  union all
  SELECT id,gbp_escrow,'GBP' from status
  union all
  SELECT id,jpy_escrow,'JPY' from status
  union all
  SELECT id,cad_escrow,'CAD' from status
  union all
  SELECT id,bgn_escrow,'BGN' from status
  union all
  SELECT id,czk_escrow,'CZK' from status
  union all
  SELECT id,dkk_escrow,'DKK' from status
  union all
  SELECT id,hkd_escrow,'HKD' from status
  union all
  SELECT id,hrk_escrow,'HRK' from status
  union all
  SELECT id,huf_escrow,'HUF' from status
  union all
  SELECT id,ils_escrow,'ILS' from status
  union all
  SELECT id,inr_escrow,'INR' from status
  union all
  SELECT id,ltl_escrow,'LTL' from status
  union all
  SELECT id,lvl_escrow,'LVL' from status
  union all
  SELECT id,mxn_escrow,'MXN' from status
  union all
  SELECT id,nok_escrow,'NOK' from status
  union all
  SELECT id,nzd_escrow,'NZD' from status
  union all
  SELECT id,pln_escrow,'PLN' from status
  union all
  SELECT id,ron_escrow,'RON' from status
  union all
  SELECT id,rub_escrow,'RUB' from status
  union all
  SELECT id,sek_escrow,'SEK' from status
  union all
  SELECT id,sgd_escrow,'SGD' from status
  union all
  SELECT id,thb_escrow,'THB' from status
  union all
  SELECT id,try_escrow,'TRY' from status
  union all
  SELECT id,zar_escrow,'ZAR' from status
  union all
  SELECT id,usd_escrow,'USD' from status
  union all
  SELECT id,mur_escrow,'MUR' from status
  union all
  SELECT id,aud_escrow,'AUD' from status
  union all
  SELECT id,chf_escrow,'CHF' from status
) status1
LEFT JOIN currencies ON (BINARY status1.currency = BINARY currencies.currency)
WHERE status1.balance > 0;

ALTER TABLE `status`
  DROP `btc_escrow`,
  DROP `usd_escrow`,
  DROP `eur_escrow`,
  DROP `cny_escrow`,
  DROP `gbp_escrow`,
  DROP `cad_escrow`,
  DROP `jpy_escrow`,
  DROP `aud_escrow`,
  DROP `chf_escrow`,
  DROP `rub_escrow`,
  DROP `mxn_escrow`,
  DROP `hkd_escrow`,
  DROP `ils_escrow`,
  DROP `inr_escrow`,
  DROP `dkk_escrow`,
  DROP `huf_escrow`,
  DROP `lvl_escrow`,
  DROP `ltl_escrow`,
  DROP `nzd_escrow`,
  DROP `nok_escrow`,
  DROP `pln_escrow`,
  DROP `ron_escrow`,
  DROP `sgd_escrow`,
  DROP `zar_escrow`,
  DROP `sek_escrow`,
  DROP `thb_escrow`,
  DROP `try_escrow`,
  DROP `czk_escrow`,
  DROP `bgn_escrow`,
  DROP `hrk_escrow`,
  DROP `ars_escrow`,
  DROP `cop_escrow`,
  DROP `mur_escrow`,
  DROP `twd_escrow`;

UPDATE `admin_controls_methods` SET control_id = 269 WHERE id = 2303 OR id = 2304;
ALTER TABLE app_configuration ADD `trading_status` VARCHAR( 255 ) NOT NULL;
ALTER TABLE app_configuration ADD `withdrawals_status` VARCHAR( 255 ) NOT NULL;

INSERT INTO `emails` (`id`, `key`, `title_en`,`content_en`) VALUES
(26, 'fiat-deposit-failure', 'Fiat Deposit #[id] Failure ', 0x3c703e4445504f534954204641494c555245204e4f54494649434154494f4e3a3c2f703e0d0a0d0a3c703e5b65786368616e67655f6e616d655d20636f756c64206e6f74206964656e7469667920612075736572206163636f756e7420666f7220612075736572206465706f73697420776974682043727970746f204361706974616c20494420235b69645d2e3c2f703e0d0a0d0a3c703e266e6273703b3c2f703e0d0a);

INSERT INTO `admin_pages` (`id`, `f_id`, `name`, `url`, `icon`, `order`, `page_map_reorders`, `one_record`) VALUES
(95, 30, 'User Balances', 'site-users-balances', '', 0, 0, ''),
(96, 64, 'Escrow Balances', 'status-escrows', '', 0, 0, '');

INSERT INTO `admin_controls` (`id`, `page_id`, `tab_id`, `action`, `class`, `arguments`, `order`, `is_static`) VALUES
(270, 95, 0, 'form', 'Form', 'a:10:{s:4:"name";s:19:"site_users_balances";s:6:"method";s:0:"";s:5:"class";s:0:"";s:5:"table";s:19:"site_users_balances";s:18:"start_on_construct";s:0:"";s:9:"go_to_url";s:0:"";s:12:"go_to_action";s:0:"";s:12:"go_to_is_tab";s:0:"";s:6:"target";s:0:"";s:14:"return_to_self";s:0:"";}', 0, 'N'),
(271, 95, 0, '', 'Grid', 'a:24:{s:5:"table";s:19:"site_users_balances";s:4:"mode";s:0:"";s:13:"rows_per_page";s:0:"";s:5:"class";s:0:"";s:12:"show_buttons";s:1:"Y";s:14:"target_elem_id";s:8:"edit_box";s:9:"max_pages";s:2:"20";s:16:"pagination_label";s:0:"";s:18:"show_list_captions";s:0:"";s:8:"order_by";s:0:"";s:9:"order_asc";s:0:"";s:10:"save_order";s:0:"";s:12:"enable_graph";s:0:"";s:12:"enable_table";s:0:"";s:11:"enable_list";s:0:"";s:17:"enable_graph_line";s:0:"";s:16:"enable_graph_pie";s:0:"";s:15:"button_link_url";s:0:"";s:18:"button_link_is_tab";s:0:"";s:10:"sql_filter";s:0:"";s:16:"alert_condition1";s:0:"";s:16:"alert_condition2";s:0:"";s:8:"group_by";s:0:"";s:11:"no_group_by";s:0:"";}', 0, 'N'),
(274, 96, 0, 'form', 'Form', 'a:10:{s:4:"name";s:14:"status_escrows";s:6:"method";s:0:"";s:5:"class";s:0:"";s:5:"table";s:0:"";s:18:"start_on_construct";s:0:"";s:9:"go_to_url";s:0:"";s:12:"go_to_action";s:0:"";s:12:"go_to_is_tab";s:0:"";s:6:"target";s:0:"";s:14:"return_to_self";s:0:"";}', 0, 'N'),
(275, 96, 0, '', 'Grid', 'a:24:{s:5:"table";s:14:"status_escrows";s:4:"mode";s:0:"";s:13:"rows_per_page";s:0:"";s:5:"class";s:0:"";s:12:"show_buttons";s:1:"Y";s:14:"target_elem_id";s:8:"edit_box";s:9:"max_pages";s:2:"20";s:16:"pagination_label";s:0:"";s:18:"show_list_captions";s:0:"";s:8:"order_by";s:0:"";s:9:"order_asc";s:0:"";s:10:"save_order";s:0:"";s:12:"enable_graph";s:0:"";s:12:"enable_table";s:0:"";s:11:"enable_list";s:0:"";s:17:"enable_graph_line";s:0:"";s:16:"enable_graph_pie";s:0:"";s:15:"button_link_url";s:0:"";s:18:"button_link_is_tab";s:0:"";s:10:"sql_filter";s:0:"";s:16:"alert_condition1";s:0:"";s:16:"alert_condition2";s:0:"";s:8:"group_by";s:0:"";s:11:"no_group_by";s:0:"";}', 0, 'N');


DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1539;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1591;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1592;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1597;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1598;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1599;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1600;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1601;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1602;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1603;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1604;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1605;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1606;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1607;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1608;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1609;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1610;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1611;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1612;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1613;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1614;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1615;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1651;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1652;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1653;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1654;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1655;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1656;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2098;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2099;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2100;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2101;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2272;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2273;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1593;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1594;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1595;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1596;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1664;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1665;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1666;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1667;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1668;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1669;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1670;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1671;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1672;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1673;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1674;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1675;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1676;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1677;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1678;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1679;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1680;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1681;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1682;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1683;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1684;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1685;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1686;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2102;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2103;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2104;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2105;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2274;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2275;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1970;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1971;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1972;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1973;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1974;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1975;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1976;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1977;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1978;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1979;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1980;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1981;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1982;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1983;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1984;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1985;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1986;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1987;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1988;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1989;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1990;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1991;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1992;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1993;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1994;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1995;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1996;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 1997;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2106;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2107;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2108;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2109;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2286;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2287;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2299;
DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2300;

INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(2505, 'checkBox', 'a:9:{s:4:"name";s:24:"email_notify_fiat_failed";s:7:"caption";s:27:"Notify fiat deposit failure";s:8:"required";s:0:"";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:11:"label_class";s:0:"";s:7:"checked";s:0:"";}', 15, 269, 0);

INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(2506, 'textInput', 'a:13:{s:4:"name";s:7:"balance";s:7:"caption";s:7:"Balance";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:7:"decimal";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 2, 270, 0),
(2507, 'autoComplete', 'a:22:{s:4:"name";s:9:"site_user";s:7:"caption";s:4:"User";s:8:"required";s:0:"";s:5:"value";s:0:"";s:8:"multiple";s:0:"";s:13:"options_array";s:0:"";s:8:"subtable";s:10:"site_users";s:15:"subtable_fields";a:4:{s:4:"user";s:4:"user";s:10:"first_name";s:10:"first_name";s:9:"last_name";s:9:"last_name";s:5:"email";s:5:"email";}s:13:"subtable_f_id";s:0:"";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:10:"depends_on";s:0:"";s:10:"depend_url";s:0:"";s:10:"f_id_field";s:0:"";s:17:"list_field_values";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";s:12:"is_tokenizer";s:0:"";s:16:"get_table_fields";s:0:"";s:16:"first_is_default";s:0:"";}', 0, 270, 0),
(2508, 'selectInput', 'a:20:{s:4:"name";s:8:"currency";s:7:"caption";s:8:"Currency";s:8:"required";s:0:"";s:5:"value";s:0:"";s:13:"options_array";s:0:"";s:8:"subtable";s:10:"currencies";s:15:"subtable_fields";a:1:{s:8:"currency";s:8:"currency";}s:13:"subtable_f_id";s:0:"";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:10:"f_id_field";s:0:"";s:12:"default_text";s:0:"";s:10:"depends_on";s:0:"";s:20:"function_to_elements";s:0:"";s:16:"first_is_default";s:0:"";s:5:"level";s:0:"";s:11:"concat_char";s:0:"";s:9:"is_catsel";s:0:"";}', 1, 270, 0),
(2509, 'submitButton', 'a:6:{s:4:"name";s:4:"Save";s:5:"value";s:4:"Save";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";}', 3, 270, 0),
(2510, 'cancelButton', 'a:4:{s:5:"value";s:6:"Cancel";s:2:"id";s:0:"";s:5:"class";s:0:"";s:5:"style";s:0:"";}', 4, 270, 0),
(2511, 'field', 'a:18:{s:4:"name";s:9:"site_user";s:8:"subtable";s:10:"site_users";s:14:"header_caption";s:4:"User";s:6:"filter";s:1:"Y";s:8:"link_url";s:0:"";s:15:"subtable_fields";a:1:{s:4:"user";s:4:"user";}s:22:"subtable_fields_concat";s:0:"";s:5:"class";s:0:"";s:18:"aggregate_function";s:0:"";s:12:"thumb_amount";s:0:"";s:12:"media_amount";s:0:"";s:10:"media_size";s:0:"";s:10:"f_id_field";s:0:"";s:8:"order_by";s:0:"";s:9:"order_asc";s:0:"";s:11:"link_is_tab";s:0:"";s:16:"limit_is_curdate";s:0:"";s:8:"is_media";s:0:"";}', 0, 271, 0),
(2512, 'field', 'a:18:{s:4:"name";s:8:"currency";s:8:"subtable";s:10:"currencies";s:14:"header_caption";s:8:"Currency";s:6:"filter";s:1:"Y";s:8:"link_url";s:0:"";s:15:"subtable_fields";a:1:{s:8:"currency";s:8:"currency";}s:22:"subtable_fields_concat";s:0:"";s:5:"class";s:0:"";s:18:"aggregate_function";s:0:"";s:12:"thumb_amount";s:0:"";s:12:"media_amount";s:0:"";s:10:"media_size";s:0:"";s:10:"f_id_field";s:0:"";s:8:"order_by";s:0:"";s:9:"order_asc";s:0:"";s:11:"link_is_tab";s:0:"";s:16:"limit_is_curdate";s:0:"";s:8:"is_media";s:0:"";}', 1, 271, 0),
(2513, 'field', 'a:18:{s:4:"name";s:7:"balance";s:8:"subtable";s:0:"";s:14:"header_caption";s:7:"Balance";s:6:"filter";s:1:"Y";s:8:"link_url";s:0:"";s:15:"subtable_fields";s:0:"";s:22:"subtable_fields_concat";s:0:"";s:5:"class";s:0:"";s:18:"aggregate_function";s:0:"";s:12:"thumb_amount";s:0:"";s:12:"media_amount";s:0:"";s:10:"media_size";s:0:"";s:10:"f_id_field";s:0:"";s:8:"order_by";s:0:"";s:9:"order_asc";s:0:"";s:11:"link_is_tab";s:0:"";s:16:"limit_is_curdate";s:0:"";s:8:"is_media";s:0:"";}', 2, 271, 0),
(2515, 'startTab', 'a:5:{s:7:"caption";s:8:"Balances";s:3:"url";s:19:"site-users-balances";s:6:"is_tab";s:0:"";s:14:"inset_id_field";s:9:"site_user";s:6:"area_i";s:0:"";}', 39, 130, 0),
(2524, 'startTab', 'a:4:{s:7:"caption";s:8:"Balances";s:3:"url";s:19:"site-users-balances";s:6:"is_tab";s:0:"";s:14:"inset_id_field";s:9:"site_user";}', 13, 131, 0),
(2526, 'startArea', 'a:3:{s:6:"legend";s:8:"Balances";s:5:"class";s:9:"box_right";s:6:"height";s:0:"";}', 12, 131, 0),
(2527, 'endArea', '', 14, 131, 0),
(2528, 'selectInput', 'a:20:{s:4:"name";s:8:"currency";s:7:"caption";s:8:"Currency";s:8:"required";s:0:"";s:5:"value";s:0:"";s:13:"options_array";s:0:"";s:8:"subtable";s:10:"currencies";s:15:"subtable_fields";a:1:{s:8:"currency";s:8:"currency";}s:13:"subtable_f_id";s:0:"";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:10:"f_id_field";s:0:"";s:12:"default_text";s:0:"";s:10:"depends_on";s:0:"";s:20:"function_to_elements";s:0:"";s:16:"first_is_default";s:0:"";s:5:"level";s:0:"";s:11:"concat_char";s:0:"";s:9:"is_catsel";s:0:"";}', 0, 274, 0),
(2529, 'textInput', 'a:13:{s:4:"name";s:7:"balance";s:7:"caption";s:7:"Balance";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";s:15:"is_manual_array";s:0:"";s:9:"is_unique";s:0:"";s:12:"default_text";s:0:"";s:17:"delete_whitespace";s:0:"";}', 1, 274, 0),
(2530, 'submitButton', 'a:6:{s:4:"name";s:4:"Save";s:5:"value";s:4:"Save";s:2:"id";s:0:"";s:5:"class";s:0:"";s:7:"jscript";s:0:"";s:5:"style";s:0:"";}', 3, 274, 0),
(2531, 'cancelButton', 'a:4:{s:5:"value";s:6:"Cancel";s:2:"id";s:0:"";s:5:"class";s:0:"";s:5:"style";s:0:"";}', 4, 274, 0),
(2532, 'field', 'a:18:{s:4:"name";s:8:"currency";s:8:"subtable";s:10:"currencies";s:14:"header_caption";s:8:"Currency";s:6:"filter";s:1:"Y";s:8:"link_url";s:0:"";s:15:"subtable_fields";a:1:{s:8:"currency";s:8:"currency";}s:22:"subtable_fields_concat";s:0:"";s:5:"class";s:0:"";s:18:"aggregate_function";s:0:"";s:12:"thumb_amount";s:0:"";s:12:"media_amount";s:0:"";s:10:"media_size";s:0:"";s:10:"f_id_field";s:0:"";s:8:"order_by";s:0:"";s:9:"order_asc";s:0:"";s:11:"link_is_tab";s:0:"";s:16:"limit_is_curdate";s:0:"";s:8:"is_media";s:0:"";}', 0, 275, 0),
(2533, 'field', 'a:18:{s:4:"name";s:7:"balance";s:8:"subtable";s:0:"";s:14:"header_caption";s:7:"Balance";s:6:"filter";s:1:"Y";s:8:"link_url";s:0:"";s:15:"subtable_fields";s:0:"";s:22:"subtable_fields_concat";s:0:"";s:5:"class";s:0:"";s:18:"aggregate_function";s:0:"";s:12:"thumb_amount";s:0:"";s:12:"media_amount";s:0:"";s:10:"media_size";s:0:"";s:10:"f_id_field";s:0:"";s:8:"order_by";s:0:"";s:9:"order_asc";s:0:"";s:11:"link_is_tab";s:0:"";s:16:"limit_is_curdate";s:0:"";s:8:"is_media";s:0:"";}', 1, 275, 0),
(2534, 'startTab', 'a:5:{s:7:"caption";s:8:"Balances";s:3:"url";s:14:"status-escrows";s:6:"is_tab";s:0:"";s:14:"inset_id_field";s:9:"status_id";s:6:"area_i";s:0:"";}', 12, 244, 0),
(2535, 'hiddenInput', 'a:8:{s:4:"name";s:9:"status_id";s:8:"required";s:0:"";s:5:"value";s:1:"1";s:2:"id";s:0:"";s:13:"db_field_type";s:3:"int";s:7:"jscript";s:0:"";s:20:"is_current_timestamp";s:0:"";s:15:"on_every_update";s:0:"";}', 2, 274, 0),
(2536, 'filterAutocomplete', 'a:6:{s:10:"field_name";s:13:"site_users.id";s:7:"caption";s:4:"User";s:13:"options_array";s:0:"";s:8:"subtable";s:10:"site_users";s:15:"subtable_fields";a:4:{s:4:"user";s:4:"user";s:10:"first_name";s:10:"first_name";s:9:"last_name";s:9:"last_name";s:5:"email";s:5:"email";}s:5:"class";s:0:"";}', 3, 271, 0),
(2537, 'startArea', 'a:3:{s:6:"legend";s:10:"Operations";s:5:"class";s:3:"box";s:6:"height";s:0:"";}', 2, 269, 0),
(2538, 'endArea', '', 5, 269, 0);

UPDATE `admin_controls_methods` SET `id` = 603,`order` = 4 WHERE `admin_controls_methods`.`id` = 603;
UPDATE `admin_controls_methods` SET `id` = 604,`order` = 7 WHERE `admin_controls_methods`.`id` = 604;
UPDATE `admin_controls_methods` SET `id` = 605,`order` = 8 WHERE `admin_controls_methods`.`id` = 605;
UPDATE `admin_controls_methods` SET `id` = 606,`order` = 10 WHERE `admin_controls_methods`.`id` = 606;
UPDATE `admin_controls_methods` SET `id` = 1249,`order` = 9 WHERE `admin_controls_methods`.`id` = 1249;
UPDATE `admin_controls_methods` SET `id` = 1882,`order` = 25 WHERE `admin_controls_methods`.`id` = 1882;
UPDATE `admin_controls_methods` SET `id` = 834,`order` = 2 WHERE `admin_controls_methods`.`id` = 834;
UPDATE `admin_controls_methods` SET `id` = 836,`order` = 4 WHERE `admin_controls_methods`.`id` = 836;
UPDATE `admin_controls_methods` SET `id` = 837,`order` = 5 WHERE `admin_controls_methods`.`id` = 837;
UPDATE `admin_controls_methods` SET `id` = 838,`order` = 7 WHERE `admin_controls_methods`.`id` = 838;
UPDATE `admin_controls_methods` SET `id` = 849,`order` = 13 WHERE `admin_controls_methods`.`id` = 849;
UPDATE `admin_controls_methods` SET `id` = 851,`order` = 3 WHERE `admin_controls_methods`.`id` = 851;
UPDATE `admin_controls_methods` SET `id` = 854,`order` = 9 WHERE `admin_controls_methods`.`id` = 854;
UPDATE `admin_controls_methods` SET `id` = 871,`order` = 0 WHERE `admin_controls_methods`.`id` = 871;
UPDATE `admin_controls_methods` SET `id` = 1019,`order` = 0 WHERE `admin_controls_methods`.`id` = 1019;
UPDATE `admin_controls_methods` SET `id` = 1020,`order` = 1 WHERE `admin_controls_methods`.`id` = 1020;
UPDATE `admin_controls_methods` SET `id` = 1237,`order` = 5 WHERE `admin_controls_methods`.`id` = 1237;
UPDATE `admin_controls_methods` SET `id` = 1917,`order` = 1 WHERE `admin_controls_methods`.`id` = 1917;
UPDATE `admin_controls_methods` SET `id` = 1250,`order` = 6 WHERE `admin_controls_methods`.`id` = 1250;
UPDATE `admin_controls_methods` SET `id` = 1857,`order` = 10 WHERE `admin_controls_methods`.`id` = 1857;
UPDATE `admin_controls_methods` SET `id` = 1870,`order` = 14 WHERE `admin_controls_methods`.`id` = 1870;
UPDATE `admin_controls_methods` SET `id` = 1871,`order` = 16 WHERE `admin_controls_methods`.`id` = 1871;
UPDATE `admin_controls_methods` SET `id` = 1872,`order` = 15 WHERE `admin_controls_methods`.`id` = 1872;
UPDATE `admin_controls_methods` SET `id` = 1876,`order` = 36 WHERE `admin_controls_methods`.`id` = 1876;
UPDATE `admin_controls_methods` SET `id` = 1877,`order` = 20 WHERE `admin_controls_methods`.`id` = 1877;
UPDATE `admin_controls_methods` SET `id` = 1874,`order` = 18 WHERE `admin_controls_methods`.`id` = 1874;
UPDATE `admin_controls_methods` SET `id` = 1875,`order` = 19 WHERE `admin_controls_methods`.`id` = 1875;
UPDATE `admin_controls_methods` SET `id` = 1883,`order` = 27 WHERE `admin_controls_methods`.`id` = 1883;
UPDATE `admin_controls_methods` SET `id` = 1382,`order` = 3 WHERE `admin_controls_methods`.`id` = 1382;
UPDATE `admin_controls_methods` SET `id` = 1878,`order` = 21 WHERE `admin_controls_methods`.`id` = 1878;
UPDATE `admin_controls_methods` SET `id` = 1879,`order` = 22 WHERE `admin_controls_methods`.`id` = 1879;
UPDATE `admin_controls_methods` SET `id` = 1880,`order` = 23 WHERE `admin_controls_methods`.`id` = 1880;
UPDATE `admin_controls_methods` SET `id` = 1881,`order` = 24 WHERE `admin_controls_methods`.`id` = 1881;
UPDATE `admin_controls_methods` SET `id` = 1856,`order` = 6 WHERE `admin_controls_methods`.`id` = 1856;
UPDATE `admin_controls_methods` SET `id` = 1937,`order` = 9 WHERE `admin_controls_methods`.`id` = 1937;
UPDATE `admin_controls_methods` SET `id` = 1914,`order` = 3 WHERE `admin_controls_methods`.`id` = 1914;
UPDATE `admin_controls_methods` SET `id` = 1915,`order` = 7 WHERE `admin_controls_methods`.`id` = 1915;
UPDATE `admin_controls_methods` SET `id` = 1916,`order` = 0 WHERE `admin_controls_methods`.`id` = 1916;
UPDATE `admin_controls_methods` SET `id` = 1919,`order` = 30 WHERE `admin_controls_methods`.`id` = 1919;
UPDATE `admin_controls_methods` SET `id` = 2535,`order` = 2 WHERE `admin_controls_methods`.`id` = 2535;
UPDATE `admin_controls_methods` SET `id` = 2536,`order` = 3 WHERE `admin_controls_methods`.`id` = 2536;
UPDATE `admin_controls_methods` SET `id` = 1662,`order` = 1 WHERE `admin_controls_methods`.`id` = 1662;
UPDATE `admin_controls_methods` SET `id` = 1688,`order` = 11 WHERE `admin_controls_methods`.`id` = 1688;
UPDATE `admin_controls_methods` SET `id` = 1658,`order` = 2 WHERE `admin_controls_methods`.`id` = 1658;
UPDATE `admin_controls_methods` SET `id` = 1659,`order` = 37 WHERE `admin_controls_methods`.`id` = 1659;
UPDATE `admin_controls_methods` SET `id` = 1660,`order` = 38 WHERE `admin_controls_methods`.`id` = 1660;
UPDATE `admin_controls_methods` SET `id` = 1661,`order` = 41 WHERE `admin_controls_methods`.`id` = 1661;
UPDATE `admin_controls_methods` SET `id` = 1929,`order` = 5 WHERE `admin_controls_methods`.`id` = 1929;
UPDATE `admin_controls_methods` SET `id` = 1930,`order` = 6 WHERE `admin_controls_methods`.`id` = 1930;
UPDATE `admin_controls_methods` SET `id` = 1931,`order` = 4 WHERE `admin_controls_methods`.`id` = 1931;
UPDATE `admin_controls_methods` SET `id` = 1932,`order` = 8 WHERE `admin_controls_methods`.`id` = 1932;
UPDATE `admin_controls_methods` SET `id` = 1966,`order` = 2 WHERE `admin_controls_methods`.`id` = 1966;
UPDATE `admin_controls_methods` SET `id` = 1967,`order` = 10 WHERE `admin_controls_methods`.`id` = 1967;
UPDATE `admin_controls_methods` SET `id` = 1968,`order` = 11 WHERE `admin_controls_methods`.`id` = 1968;
UPDATE `admin_controls_methods` SET `id` = 1969,`order` = 13 WHERE `admin_controls_methods`.`id` = 1969;
UPDATE `admin_controls_methods` SET `id` = 2016,`order` = 31 WHERE `admin_controls_methods`.`id` = 2016;
UPDATE `admin_controls_methods` SET `id` = 2017,`order` = 29 WHERE `admin_controls_methods`.`id` = 2017;
UPDATE `admin_controls_methods` SET `id` = 2018,`order` = 32 WHERE `admin_controls_methods`.`id` = 2018;
UPDATE `admin_controls_methods` SET `id` = 2019,`order` = 33 WHERE `admin_controls_methods`.`id` = 2019;
UPDATE `admin_controls_methods` SET `id` = 2534,`order` = 12 WHERE `admin_controls_methods`.`id` = 2534;
UPDATE `admin_controls_methods` SET `id` = 2533,`order` = 1 WHERE `admin_controls_methods`.`id` = 2533;
UPDATE `admin_controls_methods` SET `id` = 2530,`order` = 3 WHERE `admin_controls_methods`.`id` = 2530;
UPDATE `admin_controls_methods` SET `id` = 2531,`order` = 4 WHERE `admin_controls_methods`.`id` = 2531;
UPDATE `admin_controls_methods` SET `id` = 2532,`order` = 0 WHERE `admin_controls_methods`.`id` = 2532;
UPDATE `admin_controls_methods` SET `id` = 2135,`order` = 17 WHERE `admin_controls_methods`.`id` = 2135;
UPDATE `admin_controls_methods` SET `id` = 2136,`order` = 12 WHERE `admin_controls_methods`.`id` = 2136;
UPDATE `admin_controls_methods` SET `id` = 2137,`order` = 8 WHERE `admin_controls_methods`.`id` = 2137;
UPDATE `admin_controls_methods` SET `id` = 2263,`order` = 11 WHERE `admin_controls_methods`.`id` = 2263;
UPDATE `admin_controls_methods` SET `id` = 2529,`order` = 1 WHERE `admin_controls_methods`.`id` = 2529;
UPDATE `admin_controls_methods` SET `id` = 2524,`order` = 13 WHERE `admin_controls_methods`.`id` = 2524;
UPDATE `admin_controls_methods` SET `id` = 2526,`order` = 12 WHERE `admin_controls_methods`.`id` = 2526;
UPDATE `admin_controls_methods` SET `id` = 2527,`order` = 14 WHERE `admin_controls_methods`.`id` = 2527;
UPDATE `admin_controls_methods` SET `id` = 2528,`order` = 0 WHERE `admin_controls_methods`.`id` = 2528;
UPDATE `admin_controls_methods` SET `id` = 2537,`order` = 2 WHERE `admin_controls_methods`.`id` = 2537;
UPDATE `admin_controls_methods` SET `id` = 2538,`order` = 5 WHERE `admin_controls_methods`.`id` = 2538;
UPDATE `admin_controls_methods` SET `id` = 2303,`order` = 3 WHERE `admin_controls_methods`.`id` = 2303;
UPDATE `admin_controls_methods` SET `id` = 2304,`order` = 4 WHERE `admin_controls_methods`.`id` = 2304;
UPDATE `admin_controls_methods` SET `id` = 2305,`order` = 18 WHERE `admin_controls_methods`.`id` = 2305;
UPDATE `admin_controls_methods` SET `id` = 2306,`order` = 26 WHERE `admin_controls_methods`.`id` = 2306;
UPDATE `admin_controls_methods` SET `id` = 2307,`order` = 19 WHERE `admin_controls_methods`.`id` = 2307;
UPDATE `admin_controls_methods` SET `id` = 2308,`order` = 20 WHERE `admin_controls_methods`.`id` = 2308;
UPDATE `admin_controls_methods` SET `id` = 2309,`order` = 21 WHERE `admin_controls_methods`.`id` = 2309;
UPDATE `admin_controls_methods` SET `id` = 2310,`order` = 22 WHERE `admin_controls_methods`.`id` = 2310;
UPDATE `admin_controls_methods` SET `id` = 2312,`order` = 24 WHERE `admin_controls_methods`.`id` = 2312;
UPDATE `admin_controls_methods` SET `id` = 2311,`order` = 23 WHERE `admin_controls_methods`.`id` = 2311;
UPDATE `admin_controls_methods` SET `id` = 2313,`order` = 25 WHERE `admin_controls_methods`.`id` = 2313;
UPDATE `admin_controls_methods` SET `id` = 2314,`order` = 26 WHERE `admin_controls_methods`.`id` = 2314;
UPDATE `admin_controls_methods` SET `id` = 2315,`order` = 28 WHERE `admin_controls_methods`.`id` = 2315;
UPDATE `admin_controls_methods` SET `id` = 2334,`order` = 34 WHERE `admin_controls_methods`.`id` = 2334;
UPDATE `admin_controls_methods` SET `id` = 2339,`order` = 35 WHERE `admin_controls_methods`.`id` = 2339;
UPDATE `admin_controls_methods` SET `id` = 2397,`order` = 6 WHERE `admin_controls_methods`.`id` = 2397;
UPDATE `admin_controls_methods` SET `id` = 2398,`order` = 21 WHERE `admin_controls_methods`.`id` = 2398;
UPDATE `admin_controls_methods` SET `id` = 2399,`order` = 63 WHERE `admin_controls_methods`.`id` = 2399;
UPDATE `admin_controls_methods` SET `id` = 2400,`order` = 66 WHERE `admin_controls_methods`.`id` = 2400;
UPDATE `admin_controls_methods` SET `id` = 2401,`order` = 67 WHERE `admin_controls_methods`.`id` = 2401;
UPDATE `admin_controls_methods` SET `id` = 2402,`order` = 69 WHERE `admin_controls_methods`.`id` = 2402;
UPDATE `admin_controls_methods` SET `id` = 2403,`order` = 70 WHERE `admin_controls_methods`.`id` = 2403;
UPDATE `admin_controls_methods` SET `id` = 2404,`order` = 73 WHERE `admin_controls_methods`.`id` = 2404;
UPDATE `admin_controls_methods` SET `id` = 2405,`order` = 38 WHERE `admin_controls_methods`.`id` = 2405;
UPDATE `admin_controls_methods` SET `id` = 2406,`order` = 62 WHERE `admin_controls_methods`.`id` = 2406;
UPDATE `admin_controls_methods` SET `id` = 2407,`order` = 78 WHERE `admin_controls_methods`.`id` = 2407;
UPDATE `admin_controls_methods` SET `id` = 2408,`order` = 100 WHERE `admin_controls_methods`.`id` = 2408;
UPDATE `admin_controls_methods` SET `id` = 2409,`order` = 60 WHERE `admin_controls_methods`.`id` = 2409;
UPDATE `admin_controls_methods` SET `id` = 2410,`order` = 61 WHERE `admin_controls_methods`.`id` = 2410;
UPDATE `admin_controls_methods` SET `id` = 2412,`order` = 7 WHERE `admin_controls_methods`.`id` = 2412;
UPDATE `admin_controls_methods` SET `id` = 2413,`order` = 13 WHERE `admin_controls_methods`.`id` = 2413;
UPDATE `admin_controls_methods` SET `id` = 2414,`order` = 12 WHERE `admin_controls_methods`.`id` = 2414;
UPDATE `admin_controls_methods` SET `id` = 2415,`order` = 8 WHERE `admin_controls_methods`.`id` = 2415;
UPDATE `admin_controls_methods` SET `id` = 2416,`order` = 11 WHERE `admin_controls_methods`.`id` = 2416;
UPDATE `admin_controls_methods` SET `id` = 2417,`order` = 101 WHERE `admin_controls_methods`.`id` = 2417;
UPDATE `admin_controls_methods` SET `id` = 2418,`order` = 1 WHERE `admin_controls_methods`.`id` = 2418;
UPDATE `admin_controls_methods` SET `id` = 2422,`order` = 102 WHERE `admin_controls_methods`.`id` = 2422;
UPDATE `admin_controls_methods` SET `id` = 2421,`order` = 0 WHERE `admin_controls_methods`.`id` = 2421;
UPDATE `admin_controls_methods` SET `id` = 2423,`order` = 64 WHERE `admin_controls_methods`.`id` = 2423;
UPDATE `admin_controls_methods` SET `id` = 2425,`order` = 65 WHERE `admin_controls_methods`.`id` = 2425;
UPDATE `admin_controls_methods` SET `id` = 2450,`order` = 79 WHERE `admin_controls_methods`.`id` = 2450;
UPDATE `admin_controls_methods` SET `id` = 2427,`order` = 27 WHERE `admin_controls_methods`.`id` = 2427;
UPDATE `admin_controls_methods` SET `id` = 2428,`order` = 37 WHERE `admin_controls_methods`.`id` = 2428;
UPDATE `admin_controls_methods` SET `id` = 2429,`order` = 28 WHERE `admin_controls_methods`.`id` = 2429;
UPDATE `admin_controls_methods` SET `id` = 2430,`order` = 31 WHERE `admin_controls_methods`.`id` = 2430;
UPDATE `admin_controls_methods` SET `id` = 2431,`order` = 32 WHERE `admin_controls_methods`.`id` = 2431;
UPDATE `admin_controls_methods` SET `id` = 2432,`order` = 33 WHERE `admin_controls_methods`.`id` = 2432;
UPDATE `admin_controls_methods` SET `id` = 2433,`order` = 34 WHERE `admin_controls_methods`.`id` = 2433;
UPDATE `admin_controls_methods` SET `id` = 2434,`order` = 35 WHERE `admin_controls_methods`.`id` = 2434;
UPDATE `admin_controls_methods` SET `id` = 2435,`order` = 36 WHERE `admin_controls_methods`.`id` = 2435;
UPDATE `admin_controls_methods` SET `id` = 2436,`order` = 49 WHERE `admin_controls_methods`.`id` = 2436;
UPDATE `admin_controls_methods` SET `id` = 2437,`order` = 48 WHERE `admin_controls_methods`.`id` = 2437;
UPDATE `admin_controls_methods` SET `id` = 2438,`order` = 39 WHERE `admin_controls_methods`.`id` = 2438;
UPDATE `admin_controls_methods` SET `id` = 2439,`order` = 40 WHERE `admin_controls_methods`.`id` = 2439;
UPDATE `admin_controls_methods` SET `id` = 2440,`order` = 41 WHERE `admin_controls_methods`.`id` = 2440;
UPDATE `admin_controls_methods` SET `id` = 2441,`order` = 42 WHERE `admin_controls_methods`.`id` = 2441;
UPDATE `admin_controls_methods` SET `id` = 2442,`order` = 43 WHERE `admin_controls_methods`.`id` = 2442;
UPDATE `admin_controls_methods` SET `id` = 2443,`order` = 44 WHERE `admin_controls_methods`.`id` = 2443;
UPDATE `admin_controls_methods` SET `id` = 2444,`order` = 59 WHERE `admin_controls_methods`.`id` = 2444;
UPDATE `admin_controls_methods` SET `id` = 2445,`order` = 58 WHERE `admin_controls_methods`.`id` = 2445;
UPDATE `admin_controls_methods` SET `id` = 2446,`order` = 50 WHERE `admin_controls_methods`.`id` = 2446;
UPDATE `admin_controls_methods` SET `id` = 2447,`order` = 53 WHERE `admin_controls_methods`.`id` = 2447;
UPDATE `admin_controls_methods` SET `id` = 2448,`order` = 10 WHERE `admin_controls_methods`.`id` = 2448;
UPDATE `admin_controls_methods` SET `id` = 2449,`order` = 14 WHERE `admin_controls_methods`.`id` = 2449;
UPDATE `admin_controls_methods` SET `id` = 2452,`order` = 17 WHERE `admin_controls_methods`.`id` = 2452;
UPDATE `admin_controls_methods` SET `id` = 2453,`order` = 9 WHERE `admin_controls_methods`.`id` = 2453;
UPDATE `admin_controls_methods` SET `id` = 2454,`order` = 80 WHERE `admin_controls_methods`.`id` = 2454;
UPDATE `admin_controls_methods` SET `id` = 2455,`order` = 82 WHERE `admin_controls_methods`.`id` = 2455;
UPDATE `admin_controls_methods` SET `id` = 2456,`order` = 83 WHERE `admin_controls_methods`.`id` = 2456;
UPDATE `admin_controls_methods` SET `id` = 2457,`order` = 84 WHERE `admin_controls_methods`.`id` = 2457;
UPDATE `admin_controls_methods` SET `id` = 2458,`order` = 85 WHERE `admin_controls_methods`.`id` = 2458;
UPDATE `admin_controls_methods` SET `id` = 2459,`order` = 86 WHERE `admin_controls_methods`.`id` = 2459;
UPDATE `admin_controls_methods` SET `id` = 2460,`order` = 87 WHERE `admin_controls_methods`.`id` = 2460;
UPDATE `admin_controls_methods` SET `id` = 2461,`order` = 88 WHERE `admin_controls_methods`.`id` = 2461;
UPDATE `admin_controls_methods` SET `id` = 2462,`order` = 90 WHERE `admin_controls_methods`.`id` = 2462;
UPDATE `admin_controls_methods` SET `id` = 2463,`order` = 91 WHERE `admin_controls_methods`.`id` = 2463;
UPDATE `admin_controls_methods` SET `id` = 2464,`order` = 92 WHERE `admin_controls_methods`.`id` = 2464;
UPDATE `admin_controls_methods` SET `id` = 2465,`order` = 89 WHERE `admin_controls_methods`.`id` = 2465;
UPDATE `admin_controls_methods` SET `id` = 2466,`order` = 93 WHERE `admin_controls_methods`.`id` = 2466;
UPDATE `admin_controls_methods` SET `id` = 2467,`order` = 94 WHERE `admin_controls_methods`.`id` = 2467;
UPDATE `admin_controls_methods` SET `id` = 2468,`order` = 95 WHERE `admin_controls_methods`.`id` = 2468;
UPDATE `admin_controls_methods` SET `id` = 2469,`order` = 96 WHERE `admin_controls_methods`.`id` = 2469;
UPDATE `admin_controls_methods` SET `id` = 2470,`order` = 97 WHERE `admin_controls_methods`.`id` = 2470;
UPDATE `admin_controls_methods` SET `id` = 2471,`order` = 98 WHERE `admin_controls_methods`.`id` = 2471;
UPDATE `admin_controls_methods` SET `id` = 2472,`order` = 99 WHERE `admin_controls_methods`.`id` = 2472;
UPDATE `admin_controls_methods` SET `id` = 2473,`order` = 30 WHERE `admin_controls_methods`.`id` = 2473;
UPDATE `admin_controls_methods` SET `id` = 2474,`order` = 18 WHERE `admin_controls_methods`.`id` = 2474;
UPDATE `admin_controls_methods` SET `id` = 2475,`order` = 15 WHERE `admin_controls_methods`.`id` = 2475;
UPDATE `admin_controls_methods` SET `id` = 2476,`order` = 16 WHERE `admin_controls_methods`.`id` = 2476;
UPDATE `admin_controls_methods` SET `id` = 2477,`order` = 68 WHERE `admin_controls_methods`.`id` = 2477;
UPDATE `admin_controls_methods` SET `id` = 2478,`order` = 45 WHERE `admin_controls_methods`.`id` = 2478;
UPDATE `admin_controls_methods` SET `id` = 2479,`order` = 46 WHERE `admin_controls_methods`.`id` = 2479;
UPDATE `admin_controls_methods` SET `id` = 2480,`order` = 47 WHERE `admin_controls_methods`.`id` = 2480;
UPDATE `admin_controls_methods` SET `id` = 2481,`order` = 71 WHERE `admin_controls_methods`.`id` = 2481;
UPDATE `admin_controls_methods` SET `id` = 2482,`order` = 52 WHERE `admin_controls_methods`.`id` = 2482;
UPDATE `admin_controls_methods` SET `id` = 2483,`order` = 72 WHERE `admin_controls_methods`.`id` = 2483;
UPDATE `admin_controls_methods` SET `id` = 2484,`order` = 74 WHERE `admin_controls_methods`.`id` = 2484;
UPDATE `admin_controls_methods` SET `id` = 2485,`order` = 75 WHERE `admin_controls_methods`.`id` = 2485;
UPDATE `admin_controls_methods` SET `id` = 2486,`order` = 76 WHERE `admin_controls_methods`.`id` = 2486;
UPDATE `admin_controls_methods` SET `id` = 2487,`order` = 77 WHERE `admin_controls_methods`.`id` = 2487;
UPDATE `admin_controls_methods` SET `id` = 2488,`order` = 19 WHERE `admin_controls_methods`.`id` = 2488;
UPDATE `admin_controls_methods` SET `id` = 2489,`order` = 29 WHERE `admin_controls_methods`.`id` = 2489;
UPDATE `admin_controls_methods` SET `id` = 2492,`order` = 54 WHERE `admin_controls_methods`.`id` = 2492;
UPDATE `admin_controls_methods` SET `id` = 2493,`order` = 56 WHERE `admin_controls_methods`.`id` = 2493;
UPDATE `admin_controls_methods` SET `id` = 2494,`order` = 23 WHERE `admin_controls_methods`.`id` = 2494;
UPDATE `admin_controls_methods` SET `id` = 2495,`order` = 55 WHERE `admin_controls_methods`.`id` = 2495;
UPDATE `admin_controls_methods` SET `id` = 2496,`order` = 57 WHERE `admin_controls_methods`.`id` = 2496;
UPDATE `admin_controls_methods` SET `id` = 2497,`order` = 22 WHERE `admin_controls_methods`.`id` = 2497;
UPDATE `admin_controls_methods` SET `id` = 2498,`order` = 26 WHERE `admin_controls_methods`.`id` = 2498;
UPDATE `admin_controls_methods` SET `id` = 2499,`order` = 24 WHERE `admin_controls_methods`.`id` = 2499;
UPDATE `admin_controls_methods` SET `id` = 2500,`order` = 25 WHERE `admin_controls_methods`.`id` = 2500;
UPDATE `admin_controls_methods` SET `id` = 2502,`order` = 51 WHERE `admin_controls_methods`.`id` = 2502;
UPDATE `admin_controls_methods` SET `id` = 2503,`order` = 81 WHERE `admin_controls_methods`.`id` = 2503;
UPDATE `admin_controls_methods` SET `id` = 2505,`order` = 20 WHERE `admin_controls_methods`.`id` = 2505;
UPDATE `admin_controls_methods` SET `id` = 2506,`order` = 2 WHERE `admin_controls_methods`.`id` = 2506;
UPDATE `admin_controls_methods` SET `id` = 2507,`order` = 0 WHERE `admin_controls_methods`.`id` = 2507;
UPDATE `admin_controls_methods` SET `id` = 2508,`order` = 1 WHERE `admin_controls_methods`.`id` = 2508;
UPDATE `admin_controls_methods` SET `id` = 2509,`order` = 3 WHERE `admin_controls_methods`.`id` = 2509;
UPDATE `admin_controls_methods` SET `id` = 2510,`order` = 4 WHERE `admin_controls_methods`.`id` = 2510;
UPDATE `admin_controls_methods` SET `id` = 2511,`order` = 0 WHERE `admin_controls_methods`.`id` = 2511;
UPDATE `admin_controls_methods` SET `id` = 2512,`order` = 1 WHERE `admin_controls_methods`.`id` = 2512;
UPDATE `admin_controls_methods` SET `id` = 2513,`order` = 2 WHERE `admin_controls_methods`.`id` = 2513;
UPDATE `admin_controls_methods` SET `id` = 2515,`order` = 39 WHERE `admin_controls_methods`.`id` = 2515;

UPDATE`status` SET `db_version` = '1.10' WHERE `status`.`id` =1;
