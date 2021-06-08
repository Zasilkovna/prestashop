<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Packetery $module
 * @return bool
 */
function upgrade_module_2_1_7($module)
{
    $module->orderRepository->setIdBranchNull();

    // fix broken orders from version <= 2.1.5
    $ordersWithoutIdCarrier = $module->orderRepository->getWithoutIdCarrier();
    if ($ordersWithoutIdCarrier) {
        foreach ($ordersWithoutIdCarrier as $orderWithoutIdCarrier) {
            if ($orderWithoutIdCarrier['id_carrier_pa'] !== null) {
                $module->orderRepository->updateCarrierId(
                    (int)$orderWithoutIdCarrier['id_order'],
                    (int)$orderWithoutIdCarrier['id_carrier_pa']
                );
            }
        }
    }

    return true;
}
