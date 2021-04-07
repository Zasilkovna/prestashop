<?php
/**
 * 2019 RTsoft s.r.o.
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
 *  @author    RTsoft s.r.o
 *  @copyright 2019 RTsoft s.r.o
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}
function upgrade_module_2_1_3($object)
{
    $oldSettings = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'packetery_settings`');
    $updateData = [];
    foreach ($oldSettings as $oldSetting) {
        switch (strtoupper($oldSetting['option'])) {
            case 'APIKEY':
            case 'APIPASS':
                $updateData[] = ['id' => 1, 'option' => 'APIPASS', 'value' => $oldSetting['value']];
                break;
            case 'ESHOPDOMAIN':
            case 'ESHOP_ID':
                $updateData[] = ['id' => 2, 'option' => 'ESHOP_ID', 'value' => $oldSetting['value']];
                break;
            case 'LABEL_FORMAT':
                $updateData[] = ['id' => 3, 'option' => 'LABEL_FORMAT', 'value' => $oldSetting['value']];
                break;
            case 'LAST_BRANCHES_UPDATE':
                $updateData[] = ['id' => 4, 'option' => 'LAST_BRANCHES_UPDATE', 'value' => $oldSetting['value']];
                break;
            case 'WIDGET_TYPE':
                $updateData[] = ['id' => 5, 'option' => 'WIDGET_TYPE', 'value' => $oldSetting['value']];
                break;
            case 'FORCE_COUNTRY':
                $updateData[] = ['id' => 6, 'option' => 'FORCE_COUNTRY', 'value' => $oldSetting['value']];
                break;
            case 'FORCE_LANG':
                $updateData[] = ['id' => 7, 'option' => 'FORCE_LANGUAGE', 'value' => $oldSetting['value']];
                break;
        }
    }

    foreach ($updateData as $settingData) {
        Db::getInstance()->insert('packetery_settings', $settingData, false, true, Db::ON_DUPLICATE_KEY);
    }

    return true;
}
