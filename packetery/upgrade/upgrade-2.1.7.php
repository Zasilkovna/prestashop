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

use Packetery\Order\OrderRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $module
 *
 * @return bool
 */
function upgrade_module_2_1_7($module)
{
    $dbTools = $module->diContainer->get(Packetery\Tools\DbTools::class);
    $result = $dbTools->update('packetery_order', ['id_branch' => null], '`id_branch` = 0', 0, true);
    if ($result === false) {
        return false;
    }

    // fix broken orders from version <= 2.1.5
    $ordersWithoutIdCarrier = $dbTools->getRows(
        'SELECT `po`.`id_order`, `o`.`id_carrier`, `pad`.`id_carrier` AS `id_carrier_pad` 
            FROM `' . _DB_PREFIX_ . 'packetery_order` `po`
            JOIN `' . _DB_PREFIX_ . 'orders` `o` ON `o`.`id_order` = `po`.`id_order`
            LEFT JOIN `' . _DB_PREFIX_ . 'packetery_address_delivery` `pad` ON `pad`.`id_carrier` = `o`.`id_carrier`
            WHERE `po`.`id_carrier` = 0 AND `pad`.`id_carrier` IS NOT NULL'
    );
    if (!$ordersWithoutIdCarrier) {
        return true;
    }
    $orderRepository = $module->diContainer->get(OrderRepository::class);
    foreach ($ordersWithoutIdCarrier as $orderWithoutIdCarrier) {
        $result = $orderRepository->updateCarrierId(
            (int) $orderWithoutIdCarrier['id_order'],
            (int) $orderWithoutIdCarrier['id_carrier_pad']
        );
        if ($result === false) {
            return false;
        }
    }

    return true;
}
