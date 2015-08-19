ALTER TABLE `change_settings` ADD `type` ENUM( 's', 'r' ) NOT NULL DEFAULT 's';

UPDATE`status` SET `db_version` = '1.13.3' WHERE `status`.`id` =1;
