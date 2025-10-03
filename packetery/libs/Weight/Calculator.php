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

namespace Packetery\Weight;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Tools\ConfigHelper;

class Calculator
{
    /**
     * @param \OrderCore $order
     *
     * @return float|null
     */
    public function getComputedOrDefaultWeight(\OrderCore $order)
    {
        $packagingWeight = (float) ConfigHelper::get('PACKETERY_DEFAULT_PACKAGING_WEIGHT');
        $defaultOrderWeight = (float) ConfigHelper::get('PACKETERY_DEFAULT_PACKAGE_WEIGHT');
        $orderWeight = $this->convertUnits($order->getTotalWeight());

        if ($orderWeight === 0.0 && $defaultOrderWeight > 0) {
            $orderWeight = $defaultOrderWeight;
        }
        if ($packagingWeight > 0) {
            $orderWeight += $packagingWeight;
        }

        if ($orderWeight === 0.0) {
            return null;
        }

        return $orderWeight;
    }

    /**
     * @param array $packeteryOrder
     *
     * @return float|null
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function getFinalWeight(array $packeteryOrder)
    {
        if ($packeteryOrder['weight'] !== null) {
            return (float) $packeteryOrder['weight'];
        }

        $order = new \Order($packeteryOrder['id_order']);

        return $this->getComputedOrDefaultWeight($order);
    }

    /**
     * @param float $weight
     *
     * @return float
     */
    private function convertUnits($weight)
    {
        if (Converter::isKgConversionSupported()) {
            return Converter::getKilograms($weight);
        }

        return 0.0;
    }
}
