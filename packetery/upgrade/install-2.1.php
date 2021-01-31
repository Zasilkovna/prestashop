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
function upgrade_module_2_1($object)
{
    if ($object->active) {
        $oldSettings = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'packetery_settings`');
        $insert = [];
        foreach ($oldSettings as $oldSetting)
        {
            switch (strtoupper($oldSetting['option']))
            {
                case 'APIPASS':
                    $insert[] = ['id'=>1, 'option'=>'APIPASS', 'value'=>$oldSetting['value']];
                    break;
                case 'ESHOPDOMAIN':
                case 'ESHOP_ID':
                    $insert[] = ['id'=>2, 'option'=>'ESHOP_ID', 'value'=>$oldSetting['value']];
                    break;
                case 'LABEL_FORMAT':
                    $insert[] = ['id'=>3, 'option'=>'LABEL_FORMAT', 'value'=>$oldSetting['value']];
                    break;
                case 'LAST_BRANCHES_UPDATE':
                    $insert[] = ['id'=>4, 'option'=>'LAST_BRANCHES_UPDATE', 'value'=>$oldSetting['value']];
                    break;
                case 'FORCE_COUNTRY':
                    $insert[] = ['id'=>6, 'option'=>'FORCE_COUNTRY', 'value'=>$oldSetting['value']];
                    break;
                case 'FORCE_LANG':
                    $insert[] = ['id'=>7, 'option'=>'FORCE_LANGUAGE', 'value'=>$oldSetting['value']];
                    break;
                default:
                    continue;
            }
        }

        foreach ($insert as $k => $v)
        {
            switch (strtoupper($v['id']))
            {
                case 1:
                    $insert[] = ['id'=>1, 'option'=>'APIPASS', 'value'=>''];
                    break;
                case 2:
                    $insert[] = ['id'=>2, 'option'=>'ESHOP_ID', 'value'=>''];
                    break;
                case 3:
                    $insert[] = ['id'=>3, 'option'=>'LABEL_FORMAT', 'value'=>''];
                    break;
                case 4:
                    $insert[] = ['id'=>4, 'option'=>'LAST_BRANCHES_UPDATE', 'value'=>''];
                    break;
                case 6:
                    $insert[] = ['id'=>6, 'option'=>'FORCE_COUNTRY', 'value'=>''];
                    break;
                case 7:
                    $insert[] = ['id'=>7, 'option'=>'FORCE_LANGUAGE', 'value'=>''];
                    break;
                default:
                    continue;
            }
        }

        Db::getInstance()->delete('packetery_settings');
        Db::getInstance()->insert('packetery_settings', $insert);

        return true;
    }
    $object->upgrade_detail['2.1'][] = 'Packetery upgrade error !';
    return false;
}
