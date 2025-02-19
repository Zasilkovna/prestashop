<?php

use Packetery\PacketTracking\PacketTrackingRepository;
use Packetery\Tools\DbTools;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $module
 * @return bool
 */
function upgrade_module_3_1_0($module) {
    $sql = [];

    $packetTrackingRepository = $module->diContainer->get(PacketTrackingRepository::class);
    $sql[] = $packetTrackingRepository->getCreateSql();
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'packetery_order`
        ADD `last_update_tracking_status` datetime NULL;';

    $dbTools = $module->diContainer->get(DbTools::class);
    $executeResult = $dbTools->executeQueries(
        $sql,
        $module->l('Exception raised during Packetery module upgrade:', 'upgrade-3.1.0'),
        true
    );

    return $executeResult !== false;
}
