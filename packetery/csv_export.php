<?php

use Packetery\Order\CsvExporter;

require_once __DIR__ . '/../../config/config.inc.php';
require_once __DIR__ . '/../../init.php';
require_once __DIR__ . '/packetery.class.php';

$orders = Tools::getValue('orders');
$orders = explode(',', $orders);

$module = new Packetery();
$csvExporter = $module->diContainer->get(CsvExporter::class);
$csvExporter->outputCsvExport($orders);
