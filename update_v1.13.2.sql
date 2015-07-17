ALTER TABLE `status` ADD `btc_24h` DOUBLE( 10, 2 ) NOT NULL DEFAULT '0' AFTER `withdrawals_status`;

UPDATE`status` SET `db_version` = '1.13.2' WHERE `status`.`id` =1;
