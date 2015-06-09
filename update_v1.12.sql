INSERT INTO `lang` (`id`, `key`, `esp`, `eng`, `order`, `p_id`, `zh`, `ru`) VALUES
(448, 'orders-order-cancelled-all', 'Se han cancelado todas sus órdenes.', 'You have cancelled all your orders.', '', 9, '您已取消所有订单。', 'Вы отменили все ваши заявки.'),
(449, 'orders-order-cancelled-error', 'Ocurrió un error al cancelar las órdenes.', 'An error ocurred when cancelling your orders.', '', 9, '取消订单的时发生了错误。', 'Произошла ошибка при отмене ваших заявок.'),
(450, 'order-cancel-all', 'Cancelar todas las órdenes', 'Cancel All Orders', '', 9, '取消所有订单', 'Отменить все заявки');
INSERT INTO `lang` (`id`, `key`, `esp`, `eng`, `order`, `p_id`, `zh`, `ru`) VALUES
(451, 'order-cancel-all-conf', 'Cancelar todas las órdenes?', 'Cancel all orders?', '', 9, '取消所有订单吗？', 'Отменить все заявки ?');

UPDATE`status` SET `db_version` = '1.12' WHERE `status`.`id` =1;
