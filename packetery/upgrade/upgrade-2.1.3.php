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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param $module
 *
 * @return bool
 */
function upgrade_module_2_1_3($module)
{
    $insertData = [];
    $dbTools = $module->diContainer->get(Packetery\Tools\DbTools::class);
    $oldSettings = $dbTools->getRows('SELECT * FROM `' . _DB_PREFIX_ . 'packetery_settings`');
    if ($oldSettings) {
        foreach ($oldSettings as $oldSetting) {
            switch (strtoupper($oldSetting['option'])) {
                case 'APIKEY':
                    // no longer needed
                    break;
                case 'APIPASS':
                    $insertData[] = ['id' => 1, 'option' => 'APIPASS', 'value' => $oldSetting['value']];
                    break;
                case 'ESHOPDOMAIN':
                    $insertData[] = ['id' => 2, 'option' => 'ESHOP_ID', 'value' => $oldSetting['value']];
                    break;
                case 'LABEL_FORMAT':
                    $insertData[] = ['id' => 3, 'option' => 'LABEL_FORMAT', 'value' => $oldSetting['value']];
                    break;
                case 'LAST_BRANCHES_UPDATE':
                    $insertData[] = ['id' => 4, 'option' => 'LAST_BRANCHES_UPDATE', 'value' => $oldSetting['value']];
                    break;
                case 'WIDGET_TYPE':
                    $insertData[] = ['id' => 5, 'option' => 'WIDGET_TYPE', 'value' => $oldSetting['value']];
                    break;
                case 'FORCE_COUNTRY':
                    $insertData[] = ['id' => 6, 'option' => 'FORCE_COUNTRY', 'value' => $oldSetting['value']];
                    break;
                case 'FORCE_LANG':
                    $insertData[] = ['id' => 7, 'option' => 'FORCE_LANGUAGE', 'value' => $oldSetting['value']];
                    break;
            }
        }
    }

    $result = $dbTools->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'packetery_settings`');
    if ($result === false) {
        return false;
    }

    return $dbTools->insert('packetery_settings', $insertData);
}
