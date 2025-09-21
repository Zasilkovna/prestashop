<?php

namespace Packetery\Weight;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Tools\ConfigHelper;

class Calculator
{
    /**
     * @param \OrderCore $order
     * @return float|null
     */
    public function getComputedOrDefaultWeight(\OrderCore $order)
    {
        $packagingWeight = (float)ConfigHelper::get('PACKETERY_DEFAULT_PACKAGING_WEIGHT');
        $defaultOrderWeight = (float)ConfigHelper::get('PACKETERY_DEFAULT_PACKAGE_WEIGHT');
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
     * @return float|null
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
