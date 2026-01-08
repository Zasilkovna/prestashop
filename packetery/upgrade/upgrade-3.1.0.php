<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
use Packetery\PacketTracking\PacketTrackingRepository;
use Packetery\Tools\ConfigHelper;
use Packetery\Tools\DbTools;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $module
 *
 * @return bool
 */
function upgrade_module_3_1_0($module)
{
    $sql = [];

    /** @var PacketTrackingRepository $packetTrackingRepository */
    $packetTrackingRepository = $module->diContainer->get(PacketTrackingRepository::class);
    $sql[] = $packetTrackingRepository->getDropTableSql();
    $sql[] = $packetTrackingRepository->getCreateTableSql();
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'packetery_order`
        ADD `last_update_tracking_status` datetime NULL;';

    $dbTools = $module->diContainer->get(DbTools::class);
    $executeResult = $dbTools->executeQueries(
        $sql,
        $module->l('Exception raised during Packetery module upgrade:', 'upgrade-3.1.0'),
        true
    )
        && ConfigHelper::update(ConfigHelper::KEY_LAST_VERSION_CHECK_TIMESTAMP, time())
        && Configuration::deleteByName('PACKETERY_LAST_FEATURE_CHECK');

    return $executeResult !== false;
}
