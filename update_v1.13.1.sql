ALTER TABLE `site_users_balances` ADD INDEX ( `site_user` , `currency` );

UPDATE`status` SET `db_version` = '1.13.1' WHERE `status`.`id` =1;
