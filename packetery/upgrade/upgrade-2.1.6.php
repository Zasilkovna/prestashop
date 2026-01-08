<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
use Packetery\Tools\ConfigHelper;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $module
 *
 * @return bool
 */
function upgrade_module_2_1_6($module)
{
    $hookName = 'displayAdminOrderMain';
    if (Tools::version_compare(_PS_VERSION_, '1.7.7', '<')) {
        $hookName = 'displayAdminOrderLeft';
    }
    $result = $module->registerHook([
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

    $dbTools = $module->diContainer->get(Packetery\Tools\DbTools::class);
    $result = $dbTools->execute('
        ALTER TABLE `' . _DB_PREFIX_ . 'packetery_order`
        CHANGE `id_branch` `id_branch` int(11) NULL,
        CHANGE `name_branch` `name_branch` varchar(255) NULL,        
        CHANGE `currency_branch` `currency_branch` char(3) NULL;
    ');
    if ($result === false) {
        return false;
    }

    $previousSettings = $dbTools->getRows(
        'SELECT `option`, `value` FROM `' . _DB_PREFIX_ . 'packetery_settings`'
    );
    if ($previousSettings) {
        foreach ($previousSettings as $previousSetting) {
            switch ($previousSetting['option']) {
                case 'APIPASS':
                    ConfigHelper::update(ConfigHelper::KEY_APIPASS, $previousSetting['value']);
                    break;
                case 'ESHOP_ID':
                    ConfigHelper::update('PACKETERY_ESHOP_ID', $previousSetting['value']);
                    break;
                case 'LABEL_FORMAT':
                    ConfigHelper::update('PACKETERY_LABEL_FORMAT', $previousSetting['value']);
                    break;
                case 'LAST_BRANCHES_UPDATE':
                    ConfigHelper::update('PACKETERY_LAST_BRANCHES_UPDATE', $previousSetting['value']);
                    break;
                default:
                    break;
            }
        }
    }

    return $dbTools->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'packetery_settings`');
}
