<?php

use Packetery\Order\OrderRepository;

require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');
require_once(dirname(__FILE__).'/packetery.class.php');

$orders = Tools::getValue('orders');

$orders = explode(",", $orders);

$module = new Packetery();
$orderRepository = $module->diContainer->get(OrderRepository::class);
$orderData = Packeteryclass::collectOrdersDataForCsvExport($orders, $orderRepository);

$date = date('Y-m-d');

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="export_'.$date.'.csv"');
$fp = fopen('php://output', 'wb');
fputcsv($fp, ['version 6']);
fputcsv($fp, []);
foreach ($orderData as $line) {
    fputcsv($fp, $line);
}
fclose($fp);
