<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

use Packetery\ApiCarrier\ApiCarrierRepository;
use Packetery\Carrier\CarrierRepository;
use Packetery\Tools\DbTools;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $module
 *
 * @return bool
 */
function upgrade_module_3_5_0(Packetery $module): bool
{
    /** @var DbTools $dbTools */
    $dbTools = $module->diContainer->get(DbTools::class);
    $apiCarrierTable = _DB_PREFIX_ . ApiCarrierRepository::$tableName;
    $addressDeliveryTable = _DB_PREFIX_ . 'packetery_address_delivery';

    $countries = array_map('strtoupper', CarrierRepository::ADDRESS_VALIDATION_COUNTRIES);
    $countriesList = "'" . implode("','", array_map('pSQL', $countries)) . "'";

    $sql = [];
    $sql[] = 'UPDATE `' . bqSQL($addressDeliveryTable) . '` pad
        JOIN `' . bqSQL($apiCarrierTable) . '` pc ON pad.id_branch = pc.id
        SET pad.address_validation = NULL
        WHERE UPPER(pc.country) NOT IN (' . $countriesList . ')
        AND pad.address_validation IS NOT NULL';

    $executeResult = $dbTools->executeQueries(
        $sql,
        $module->l('Exception raised during Packetery module upgrade:', 'upgrade-3.5.0'),
        true
    );

    return $executeResult !== false;
}
