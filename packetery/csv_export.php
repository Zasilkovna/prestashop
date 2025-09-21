<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Order\CsvExporter;

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';

$orders = Tools::getValue('orders');
$orders = explode(',', $orders);

$module = new Packetery();
$csvExporter = $module->diContainer->get(CsvExporter::class);
$csvExporter->outputCsvExport($orders);
