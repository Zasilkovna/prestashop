<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $object
 * @return bool
 */
function upgrade_module_2_1_6($object)
{
    $hookName = 'displayAdminOrderMain';
    if (Tools::version_compare(_PS_VERSION_, '1.7.7', '<')) {
        $hookName = 'displayAdminOrderLeft';
    }
    $result =  $object->registerHook([
        $hookName,
        'actionAdminControllerSetMedia',
        'displayOrderConfirmation',
        'displayOrderDetail',
        'sendMailAlterTemplateVars',
        'actionObjectOrderUpdateBefore',
    ]);
    if ($result === false) {
        return false;
    }

    $result = Db::getInstance()->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'packetery_order`
        CHANGE `id_branch` `id_branch` int(11) NULL,
        CHANGE `name_branch` `name_branch` varchar(255) NULL,        
        CHANGE `currency_branch` `currency_branch` char(3) NULL;
    ');
    if ($result === false) {
        return false;
    }

    $previousSettings = Db::getInstance()->executeS('SELECT `option`, `value` FROM `' . _DB_PREFIX_ . 'packetery_settings`');
    if ($previousSettings) {
        foreach ($previousSettings as $previousSetting) {
            switch ($previousSetting['option']) {
                case 'APIPASS':
                    Configuration::updateValue('PACKETERY_APIPASS', $previousSetting['value']);
                    break;
                case 'ESHOP_ID':
                    Configuration::updateValue('PACKETERY_ESHOP_ID', $previousSetting['value']);
                    break;
                case 'LABEL_FORMAT':
                    Configuration::updateValue('PACKETERY_LABEL_FORMAT', $previousSetting['value']);
                    break;
                case 'LAST_BRANCHES_UPDATE':
                    Configuration::updateValue('PACKETERY_LAST_BRANCHES_UPDATE', $previousSetting['value']);
                    break;
                default:
                    return false;
            }
        }
    }

    return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_settings`');
}
