<?php

use Packetery\Tools\DbTools;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $module
 * @return bool
 */
function upgrade_module_3_2_0($module)
{
    $sql = [];

    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'packetery_order`
        ADD `price_total` decimal(20,6) NULL,
        ADD `price_cod` decimal(20,6) NULL AFTER `price_total`,
        ADD `age_verification_required` tinyint(1) unsigned NULL AFTER `price_cod`;';

    $dbTools = $module->diContainer->get(DbTools::class);
    $executeResult = $dbTools->executeQueries(
        $sql,
        $module->l('Exception raised during Packetery module upgrade:', 'upgrade-3.2.0'),
        true
    );

    return $executeResult !== false;
}
