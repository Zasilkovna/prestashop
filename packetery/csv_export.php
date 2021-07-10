<?php

require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');
require_once(dirname(__FILE__).'/packetery.class.php');

$orders = Tools::getValue('orders');

$orders = explode(",", $orders);

$orderData = Packeteryclass::collectOrdersDataForCsvExport($orders);

$date = date('Y-m-d');

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="export_'.$date.'.csv"');
/** @var resource $fp */
$fp = fopen('php://output', 'wb');
fputcsv($fp, ['version 6']);
/** @phpstan-ignore-next-line */
fputcsv($fp, []);
foreach ( $orderData as $line ) {
    fputcsv($fp, $line);
}
fclose($fp);
