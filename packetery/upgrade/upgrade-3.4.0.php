<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

use Packetery\Module\Helper;
use Packetery\Tools\ConfigHelper;

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

    return true;
}
