<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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
