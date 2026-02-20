<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

use Packetery\Module\Helper;
use Packetery\Tools\ConfigHelper;
use Packetery\Tools\DbTools;

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_4_0(Packetery $module): bool
{
    $keysToMigrate = [
        'PACKETERY_PACKET_STATUS_TRACKING_ORDER_STATES',
        'PACKETERY_PACKET_STATUS_TRACKING_PACKET_STATUSES',
    ];

    foreach ($keysToMigrate as $key) {
        $rawValue = ConfigHelper::get($key);
        if ($rawValue === false) {
            continue;
        }

        if (is_string($rawValue)) {
            json_decode($rawValue, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                continue;
            }
        }

        $decodedValue = Helper::unserialize($rawValue);
        if (!is_array($decodedValue)) {
            $decodedValue = [];
        }

        $updateResult = ConfigHelper::update($key, json_encode($decodedValue));
        if (!$updateResult) {
            return false;
        }
    }

    $sql = [
        'ALTER TABLE `' . _DB_PREFIX_ . 'packetery_order`
             ADD `point_place` varchar(70) NULL,
             ADD `point_street` varchar(120) NULL AFTER `point_place`,
             ADD `point_zip` varchar(10) NULL AFTER `point_street`,
             ADD `point_city` varchar(70) NULL AFTER `point_zip`',
    ];

    $dbTools = $module->diContainer->get(DbTools::class);
    $executeResult = $dbTools->executeQueries(
        $sql,
        $module->l('Exception raised during Packetery module upgrade:', 'upgrade-3.4.0'),
        true
    );

    return $executeResult !== false;
}
