
INSERT INTO `lang` (`id`, `key`, `esp`, `eng`, `order`, `p_id`) VALUES
(353, 'limit-min-price', 'El precio mínimo es [price] debido a una órden suya en otra moneda.', 'The minimum price is [price] because of your order in a different currency.', '', 17),
(354, 'limit-max-price', 'El precio máximo es [price] debido a una órden suya en otra moneda.', 'The maximum price is [price] because of your order in a different currency.', '', 17);

UPDATE `lang` SET `id` = 350,`key` = 'orders-converted-from',`esp` = 'Esta órden ha sido convertida desde otra moneda ([currency]).',`eng` = 'This order has been converted from a different currency ([currency]).',`order` = '',`p_id` = 17 WHERE `lang`.`id` = 350;

INSERT INTO `lang` (`id`, `key`, `esp`, `eng`, `order`, `p_id`) VALUES
(355, 'fee-schedule-fee1', 'Comisión de fijador (maker)', 'Maker Fee', '', 16),
(356, 'fee-schedule-flc', 'CTC* - Volúmen global 24h', 'FLC* - 24h Global Volume', '', 16);

UPDATE `lang` SET `id` = 204,`key` = 'fee-schedule-fee',`esp` = 'Comisión de tomador (taker)',`eng` = 'Taker Fee',`order` = '',`p_id` = 16 WHERE `lang`.`id` = 204;

ALTER TABLE `bitcoin_addresses` DROP INDEX `address` ;
ALTER TABLE `bitcoin_addresses` ADD INDEX ( `address` ) ;
ALTER TABLE `bitcoin_addresses` ADD INDEX ( `site_user` ) ;
ALTER TABLE `bitcoin_addresses` ADD INDEX ( `system_address` ) ;
ALTER TABLE `bitcoin_addresses` ADD INDEX ( `hot_wallet` ) ;
ALTER TABLE `bitcoin_addresses` ADD INDEX ( `warm_wallet` ) ;
ALTER TABLE `conversions` ADD INDEX ( `date` ) ;
ALTER TABLE `conversions` ADD INDEX ( `is_active` ) ;
ALTER TABLE `conversions` ADD INDEX ( `factored` ) ;
ALTER TABLE `conversions` ADD INDEX ( `factored1` ) ;
ALTER TABLE `daily_reports` ADD INDEX ( `date` ) ;
ALTER TABLE `fees` ADD INDEX ( `date` ) ;
ALTER TABLE `fee_schedule` ADD INDEX ( `from_usd` ) ;
ALTER TABLE `fee_schedule` ADD INDEX ( `to_usd` ) ;
ALTER TABLE `fee_schedule` ADD INDEX ( `global_btc` ) ;
ALTER TABLE `history` ADD INDEX ( `date` ) ;
ALTER TABLE `history` ADD INDEX ( `site_user` ) ;
ALTER TABLE `lang` ADD INDEX ( `p_id` ) ;
ALTER TABLE `orders` DROP INDEX `order_type`;
ALTER TABLE `orders` ADD INDEX ( `order_type` ) ;
ALTER TABLE `orders` ADD INDEX ( `site_user` ) ;
ALTER TABLE `orders` ADD INDEX ( `currency` ) ;
ALTER TABLE `orders` ADD INDEX ( `btc_price` ) ;
ALTER TABLE `orders` ADD INDEX ( `market_price` ) ;
ALTER TABLE `requests` DROP INDEX `crypto_id_2` ;
ALTER TABLE `requests` ADD INDEX ( `date` ) ;
ALTER TABLE `requests` ADD INDEX ( `site_user` ) ;
ALTER TABLE `requests` ADD INDEX ( `currency` ) ;
ALTER TABLE `requests` ADD INDEX ( `request_status` ) ;
ALTER TABLE `requests` ADD INDEX ( `request_type` );
ALTER TABLE `requests` ADD INDEX ( `done` ) ;
ALTER TABLE `sessions` ADD INDEX ( `nonce` ) ;
ALTER TABLE `site_users` DROP INDEX `dont_ask_30_days` ;
ALTER TABLE `site_users` ADD INDEX ( `pass` ) ;
ALTER TABLE `site_users` ADD INDEX ( `user` ) ;
ALTER TABLE `site_users` ADD INDEX ( `dont_ask_30_days` ) ;
ALTER TABLE `site_users` ADD INDEX ( `dont_ask_date` ) ;
ALTER TABLE `site_users` ADD INDEX ( `deactivated` ) ;
ALTER TABLE `change_settings` ADD INDEX ( `date` ) ;
ALTER TABLE `transactions` DROP INDEX `date` ;
ALTER TABLE `transactions` ADD INDEX ( `date` ) ;
ALTER TABLE `transactions` ADD INDEX ( `site_user` ) ;
ALTER TABLE `transactions` ADD INDEX ( `currency` ) ;
ALTER TABLE `transactions` ADD INDEX ( `transaction_type` ) ;
ALTER TABLE `transactions` ADD INDEX ( `site_user1` ) ;
ALTER TABLE `transactions` ADD INDEX ( `transaction_type1` ) ;
ALTER TABLE `transactions` ADD INDEX ( `currency1` ) ;
ALTER TABLE `transactions` ADD INDEX ( `convert_from_currency` ) ;
ALTER TABLE `transactions` ADD INDEX ( `convert_to_currency` ) ;
ALTER TABLE `transactions` ADD INDEX ( `conversion` );
ALTER TABLE `orders` ADD INDEX ( `btc_price` ) ;
ALTER TABLE `orders` ADD INDEX ( `stop_price` ) ;
ALTER TABLE `currencies` ADD INDEX ( `usd_bid` ) ;
ALTER TABLE `currencies` ADD INDEX ( `usd_ask` ) ;
ALTER TABLE site_users ADD `default_currency` INT( 10 ) NOT NULL;

INSERT INTO `lang` (`id`, `key`, `esp`, `eng`, `order`, `p_id`) VALUES
(358, 'default-currency', 'Moneda por defecto', 'Default Currency', '', 27);

