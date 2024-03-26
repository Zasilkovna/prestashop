<?php

use Packetery\ApiCarrier\ApiCarrierRepository;
use Packetery\Carrier\CarrierAdminForm;
use Packetery\Carrier\CarrierRepository;
use Packetery\Module\Installer;
use Packetery\Module\Uninstaller;
use Packetery\Product\ProductAttributeRepository;
use Packetery\Tools\ConfigHelper;
use Packetery\Tools\DbTools;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $module
 * @return bool
 */
function upgrade_module_3_0_0($module)
{
    $installer = $module->diContainer->get(Installer::class);
    $installer->setModule($module);
    $uninstaller = $module->diContainer->get(Uninstaller::class);
    $result = (
        $module->unregisterHook('actionOrderHistoryAddAfter') &&
        $module->unregisterHook('displayBackOfficeHeader') &&
        $module->registerHook('actionValidateOrder') &&
        $module->registerHook('displayBeforeCarrier') &&
        $module->registerHook('actionObjectCartUpdateBefore') &&
        $module->registerHook('displayPacketeryOrderGridListAfter') &&
        $module->registerHook('actionPacketeryOrderGridListingResultsModifier') &&
        $module->registerHook('actionValidateStepComplete') &&
        $module->registerHook('displayAdminProductsExtra') &&
        $module->registerHook('actionProductUpdate') &&
        $module->registerHook('actionProductDelete') &&
        $module->registerHook('actionPacketeryCarrierGridListingResultsModifier') &&
        ConfigHelper::update('PACKETERY_WIDGET_AUTOOPEN', 0) &&
        ConfigHelper::update('PACKETERY_CRON_TOKEN', Tools::passwdGen(32)) &&
        ConfigHelper::update('PACKETERY_ID_PREFERENCE', Packetery::ID_PREF_ID) &&
        ConfigHelper::update('PACKETERY_CARRIER_LABEL_FORMAT', 'A6 on A4') &&
        ConfigHelper::update('PACKETERY_DEFAULT_PACKAGE_PRICE', 0) &&
        ConfigHelper::update('PACKETERY_DEFAULT_PACKAGE_WEIGHT', 0) &&
        ConfigHelper::update('PACKETERY_DEFAULT_PACKAGING_WEIGHT', 0) &&
        ConfigHelper::update(ConfigHelper::KEY_LAST_FEATURE_CHECK, (string)time()) &&
        ConfigHelper::update(ConfigHelper::KEY_LAST_VERSION, $module->version) &&
        ConfigHelper::update(ConfigHelper::KEY_LAST_VERSION_URL, '') &&
        ConfigHelper::update(ConfigHelper::KEY_USE_PS_CURRENCY_CONVERSION, 0) &&
        Configuration::deleteByName('PACKETERY_LAST_BRANCHES_UPDATE') &&
        Configuration::deleteByName('PACKETERY_ORDERS_PER_PAGE') &&
        Configuration::deleteByName('PACKETERY_ADDRESS_VALIDATION') &&
        $uninstaller->deleteTab('Adminpacketery') &&
        $installer->insertMenuItems()
    );
    if ($result === false) {
        return false;
    }

    $sql = [];
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'packetery_order`
        ADD `carrier_number` varchar(255) NULL AFTER `longitude`,
		ADD `length` int NULL AFTER `weight`,
        ADD `height` int NULL AFTER `length`,
        ADD `width` int NULL AFTER `height`;';

    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'packetery_address_delivery`
        ADD `address_validation` varchar(40) NULL,
        ADD `allowed_vendors` text NULL;';
    $sql[] = 'UPDATE `' . _DB_PREFIX_ . 'packetery_address_delivery`
        SET `address_validation` = "none" WHERE `pickup_point_type` IS NULL;';
    $apiCarrierRepository = $module->diContainer->get(ApiCarrierRepository::class);
    $sql[] = $apiCarrierRepository->getCreateTableSql();
    $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_branch`;';
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'packetery_address_delivery`
        CHANGE `id_branch` `id_branch` varchar(255) NOT NULL AFTER `id_carrier`;';
    $sql[] = 'UPDATE `' . _DB_PREFIX_ . 'packetery_address_delivery` SET
        `id_branch` = "' . Packetery::ZPOINT . '"
        WHERE `pickup_point_type` = "internal" AND
        `id_branch` = "";';
    $sql[] = 'UPDATE `' . _DB_PREFIX_ . 'packetery_address_delivery` SET
        `id_branch` = "' . Packetery::PP_ALL . '"
        WHERE `pickup_point_type` = "external" AND
        `id_branch` = "";';

    $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_product`';
    $sql[] = 'CREATE TABLE `' . _DB_PREFIX_ . 'packetery_product` (
            `id_product` int(11) NOT NULL PRIMARY KEY,
            `is_adult` tinyint(1) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

    $productAttributeRepository = $module->diContainer->get(ProductAttributeRepository::class);
    $sql[] = $productAttributeRepository->getCreateTableSql();

    $dbTools = $module->diContainer->get(DbTools::class);
    if (!$dbTools->executeQueries($sql, $module->l('Exception raised during Packetery module upgrade:', 'upgrade-3.0.0'), true)) {
        return false;
    }

    $carrierUpdates = [];
    $carrierRepository = $module->diContainer->get(CarrierRepository::class);
    $internalPickupPointCarriers = $carrierRepository->getInternalPickupPointCarriers();
    if ($internalPickupPointCarriers) {
        foreach ($internalPickupPointCarriers as $carrierData) {
            $carrierAdminForm = new CarrierAdminForm((int)$carrierData['id_carrier'], $module);
            // Second parameter ensures that internal pickup points are not needed to exist in carriers table.
            $allowedVendorsJson = $carrierAdminForm->getDefaultAllowedVendors($carrierData, ['id' => $carrierData['id_branch']]);
            $carrierUpdates[] = sprintf('UPDATE `' . _DB_PREFIX_ . 'packetery_address_delivery`
                SET `allowed_vendors` = "%s" WHERE `id_carrier` = "%s";',
                $dbTools->db->escape($allowedVendorsJson), $carrierData['id_carrier']);
        }
        if (!$dbTools->executeQueries($carrierUpdates, $module->l('Exception raised during Packetery module upgrade:', 'upgrade-3.0.0'), true)) {
            return false;
        }
    }

    return true;
}
