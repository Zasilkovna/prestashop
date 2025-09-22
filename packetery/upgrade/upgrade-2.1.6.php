<?php
/**
 * 2017 Zlab Solutions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
 *  @copyright Since 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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
                    ConfigHelper::update('PACKETERY_APIPASS', $previousSetting['value']);
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
