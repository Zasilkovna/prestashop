<?php

use Packetery\Order\CsvExporter;

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/packetery.class.php';

$orders = Tools::getValue('orders');
$orders = explode(',', $orders);

$module = new Packetery();
$csvExporter = $module->diContainer->get(CsvExporter::class);
$csvExporter->outputCsvExport($orders);
