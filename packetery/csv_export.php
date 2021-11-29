<?php

use Packetery\Order\OrderRepository;

require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');
require_once(dirname(__FILE__) . '/packetery.class.php');

$orders = Tools::getValue('orders');
$orders = explode(',', $orders);

$module = new Packetery();
$orderRepository = $module->diContainer->get(OrderRepository::class);
Packeteryclass::outputCsvExport($orders, $orderRepository);
