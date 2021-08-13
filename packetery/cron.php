<?php
/**
 * @see https://devdocs.prestashop.com/1.7/modules/concepts/controllers/front-controllers/#using-a-front-controller-as-a-cron-task
 */

$_GET['fc'] = 'module';
$_GET['module'] = 'packetery';
$_GET['controller'] = 'cron';

require_once dirname(__FILE__) . '/../../index.php';
