INSERT INTO `lang` (`id`, `key`, `esp`, `eng`, `order`, `p_id`) VALUES
(330, 'buy-errors-no-compatible', 'No es posible crear una órden de mercado porque no hay órdenes compatibles en el sistema.', 'You cannot create a market order because there are no compatible orders available.', '', 17),
(331, 'buy-errors-too-little', 'La cantidad mínima para una órden es [fa_symbol][amount].', 'The minimum order quantity is [fa_symbol][amount].', '', 17);
ALTER TABLE site_users ADD `google_2fa_code` VARCHAR( 255 ) NOT NULL;
ALTER TABLE site_users ADD `verified_google` ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N' ;

