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
    return (
        $module->unregisterHook('actionOrderHistoryAddAfter') &&
        $module->registerHook('actionValidateOrder') &&
        $module->registerHook('displayBeforeCarrier') &&
        $module->registerHook('actionObjectCartUpdateBefore') &&
        $module->registerHook('displayPacketeryOrderGridListAfter') &&
        $module->registerHook('actionPacketeryOrderGridListingResultsModifier') &&
        Configuration::updateValue('PACKETERY_WIDGET_AUTOOPEN', 0) &&
        Configuration::updateValue('PACKETERY_CRON_TOKEN', Tools::passwdGen(32)) &&
        Configuration::updateValue('PACKETERY_LABEL_MAX_AGE_DAYS', 7) &&
        $uninstaller->deleteTab('Adminpacketery') &&
        $installer->insertTab() &&
        Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'packetery_order` ADD `weight` decimal(20,6) NULL;')
    );
}
