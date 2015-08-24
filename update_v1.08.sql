ALTER TABLE `order_log` ADD INDEX ( `p_id` );
UPDATE`status` SET `db_version` = '1.08' WHERE `status`.`id` =1;
