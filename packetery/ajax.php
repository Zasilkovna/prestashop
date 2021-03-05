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
 * @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
 * @copyright 2017 Zlab Solutions
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

include_once(dirname(__file__) . '/packetery.class.php');
include_once(dirname(__file__) . '/packetery.api.php');

$token = Tools::getValue('token');
$id_employee = Tools::getValue('check_e');
$real_token = Packeteryclass::getAdminToken($id_employee);

if ($token !== $real_token)
{
    exit;
}

switch (Tools::getValue('action'))
{
    /*BACK*/
    case 'updatesettings':
        Packeteryclass::updateSettings();
        break;
    case 'getcountbranches':
        PacketeryApi::countBranchesAjax();
        break;
    case 'updatebranches':
        PacketeryApi::updateBranchListAjax();
        break;
    /*SETTINGS*/
    case 'change_payment_cod':
        Packeteryclass::changePaymentCodAjax();
        break;
    case 'change_ad_carrier_cod':
        Packeteryclass::changeAdCarrierCodAjax();
        break;
    case 'set_ad_carrier_association':
        Packeteryclass::setPacketeryCarrierAjax();
        break;
    /*END SETTINGS*/
    /*ORDERS*/
    case 'get_orders_rows':
        Packeteryclass::getListOrdersAjax();
        break;
    case 'change_order_cod':
        Packeteryclass::changeOrderCodAjax();
        break;
    case 'change_order_branch':
        Packeteryclass::changeOrderBranchAjax();
        break;
    case 'prepare_order_export':
        PacketeryApi::prepareOrderExportAjax();
        break;
    case 'order_export':
        PacketeryApi::ordersExportAjax();
        break;
    case 'download_pdf':
        PacketeryApi::downloadPdfAjax();
        break;
    /*END ORDERS*/
    /*FRONT*/
    case 'widgetgetcities':
        PacketeryApi::widgetGetCitiesAjax();
        break;
    case 'widgetgetnames':
        PacketeryApi::widgetGetNamesAjax();
        break;
    case 'widgetgetdetails':
        PacketeryApi::widgetGetDetailsAjax();
        break;
    case 'widgetsaveorderbranch':
        PacketeryApi::widgetSaveOrderBranch();
        break;
    default:
        exit;
}
