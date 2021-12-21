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

use Packetery\ApiCarrier\ApiCarrierRepository;
use Packetery\Carrier\CarrierRepository;

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', getcwd());
}

include_once(dirname(__file__) . '/packetery.class.php');
include_once(dirname(__file__) . '/packetery.api.php');

if (!Context::getContext()->employee ||
    !Context::getContext()->employee->isLoggedBack()
) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
    exit;
}

switch (Tools::getValue('action')) {
    /*SETTINGS*/
    case 'change_ad_carrier_cod':
        Packeteryclass::changeAdCarrierCodAjax();
        break;
    case 'set_ad_carrier_association':
        $module = new Packetery();
        $carrierRepository = $module->diContainer->get(CarrierRepository::class);
        Packeteryclass::setPacketeryCarrierAjax($carrierRepository);
        break;
    /*END SETTINGS*/
    default:
        exit;
}
