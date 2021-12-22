<?php

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
        $module->unregisterHook('backOfficeHeader') &&
        $module->registerHook('actionValidateOrder') &&
        $module->registerHook('displayBeforeCarrier') &&
        $module->registerHook('actionObjectCartUpdateBefore') &&
        $module->registerHook('displayPacketeryOrderGridListAfter') &&
        $module->registerHook('actionPacketeryOrderGridListingResultsModifier') &&
        $module->registerHook('actionValidateStepComplete') &&
        $module->registerHook('actionPacketeryCarrierGridListingResultsModifier') &&
        Configuration::updateValue('PACKETERY_WIDGET_AUTOOPEN', 0) &&
        Configuration::updateValue('PACKETERY_CRON_TOKEN', Tools::passwdGen(32)) &&
        Configuration::updateValue('PACKETERY_LABEL_MAX_AGE_DAYS', 7) &&
        Configuration::deleteByName('PACKETERY_LAST_BRANCHES_UPDATE') &&
        $uninstaller->deleteTab('Adminpacketery') &&
        $installer->insertMenuItems()
    );
    if ($result === false) {
        return false;
    }

    $dbTools = $module->diContainer->get(\Packetery\Tools\DbTools::class);
    $addressCarriers = $dbTools->getRows(
        'SELECT `id_carrier` FROM `' . _DB_PREFIX_ . 'packetery_address_delivery` WHERE `pickup_point_type` IS NULL');
    if ($addressCarriers) {
        foreach ($addressCarriers as $addressCarrier) {
            $result = $dbTools->update(
                'carrier',
                ['is_module' => 1, 'external_module_name' => 'packetery', 'need_range' => 1],
                '`id_carrier` = ' . (int)$addressCarrier['id_carrier']);
            if ($result === false) {
                return false;
            }
        }
    }

    $sql = [];
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'packetery_order`
        ADD `weight` decimal(20,6) NULL,
        ADD `country` varchar(2) NULL AFTER `weight`,
        ADD `county` varchar(255) NULL AFTER `country`,
        ADD `zip` varchar(255) NULL AFTER `county`,
        ADD `city` varchar(255) NULL AFTER `zip`,
        ADD `street` varchar(255) NULL AFTER `city`,
        ADD `house_number` varchar(255) NULL AFTER `street`,
        ADD `latitude` varchar(255) NULL AFTER `house_number`,
        ADD `longitude` varchar(255) NULL AFTER `latitude`;';
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'packetery_address_delivery`
        ADD `address_validation` varchar(40) NULL;';
    $sql[] = 'UPDATE `' . _DB_PREFIX_ . 'packetery_address_delivery`
        SET `address_validation` = "none" WHERE `pickup_point_type` IS NULL;';
    $apiCarrierRepository = $module->diContainer->get(\Packetery\ApiCarrier\ApiCarrierRepository::class);
    $sql[] = $apiCarrierRepository->getCreateTableSql();
    $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_branch`;';
    $sql[] = 'ALTER TABLE `' . _DB_PREFIX_ . 'packetery_address_delivery`
        CHANGE `id_branch` `id_branch` varchar(255) NOT NULL AFTER `id_carrier`;';
    $sql[] = 'UPDATE `' . _DB_PREFIX_ . 'packetery_address_delivery` SET
        `id_branch` = "' . Packeteryclass::ZPOINT . '"
        WHERE `pickup_point_type` = "internal" AND
        `id_branch` = "";';
    $sql[] = 'UPDATE `' . _DB_PREFIX_ . 'packetery_address_delivery` SET
        `id_branch` = "' . Packeteryclass::PP_ALL . '"
        WHERE `pickup_point_type` = "external" AND
        `id_branch` = "";';

    if (!$dbTools->executeQueries($sql, $module->l('Exception raised during Packetery module upgrade:', 'upgrade-3.0.0'), true)) {
        return false;
    }

    return true;
}
