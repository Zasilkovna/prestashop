<?php

use Packetery\Tools\ConfigHelper;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $module
 * @return bool
 */
function upgrade_module_3_0_0($module)
{
    $installer = $module->diContainer->get(\Packetery\Module\Installer::class);
    $installer->setModule($module);
    $uninstaller = $module->diContainer->get(\Packetery\Module\Uninstaller::class);
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
        ADD `carrier_number` varchar(255) NULL AFTER `longitude`;';

    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'packetery_address_delivery`
        ADD `address_validation` varchar(40) NULL,
        ADD `allowed_vendors` text NULL;';
    $sql[] = 'UPDATE `' . _DB_PREFIX_ . 'packetery_address_delivery`
        SET `address_validation` = "none" WHERE `pickup_point_type` IS NULL;';
    $apiCarrierRepository = $module->diContainer->get(\Packetery\ApiCarrier\ApiCarrierRepository::class);
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

    $dbTools = $module->diContainer->get(\Packetery\Tools\DbTools::class);
    if (!$dbTools->executeQueries($sql, $module->l('Exception raised during Packetery module upgrade:', 'upgrade-3.0.0'), true)) {
        return false;
    }

    return true;
}
