ALTER TABLE  `requests` ADD  `request_approved` BOOLEAN NOT NULL DEFAULT FALSE AFTER  `request_status` ,
ADD INDEX (  `request_approved` ) ;

UPDATE `status` SET `db_version` = '1.13.4' WHERE `status`.`id` =1;
