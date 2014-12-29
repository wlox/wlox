ALTER TABLE site_users DROP INDEX pass_2;
ALTER TABLE site_users DROP INDEX dont_ask_30_days_2;
ALTER TABLE site_users DROP INDEX dont_ask_date;
ALTER TABLE sessions DROP INDEX nonce;

DELETE FROM `admin_controls_methods` WHERE `admin_controls_methods`.`id` = 2134;
INSERT INTO `admin_controls_methods` (`id`, `method`, `arguments`, `order`, `control_id`, `p_id`) VALUES
(2339, 'hiddenInput', 'a:8:{s:4:"name";s:15:"google_2fa_code";s:8:"required";s:0:"";s:5:"value";s:0:"";s:2:"id";s:0:"";s:13:"db_field_type";s:0:"";s:7:"jscript";s:0:"";s:20:"is_current_timestamp";s:0:"";s:15:"on_every_update";s:0:"";}', 34, 130, 0);

UPDATE`status` SET `db_version` = '1.03' WHERE `status`.`id` =1;
