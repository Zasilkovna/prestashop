<?php

namespace Packetery\Weight;

use Packetery\Tools\ConfigHelper;

class Calculator
{
    /**
     * @param \OrderCore $order
     * @return float|null
     */
    public function getPacketeryWeight(\OrderCore $order)
    {
        $packagingWeight = (float)ConfigHelper::get('PACKETERY_DEFAULT_PACKAGING_WEIGHT');
        $defaultWeight = (float)ConfigHelper::get('PACKETERY_DEFAULT_PACKAGE_WEIGHT');

        if (empty($packagingWeight) && empty($defaultWeight)) {
            return null;
        }

        $orderWeight = $this->convertUnits($order->getTotalWeight());

        if ($orderWeight === 0.0) {
            if ($defaultWeight > 0) {
                $orderWeight = $defaultWeight;
            }
        } else {
            $orderWeight += $packagingWeight;
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

        return $this->getPacketeryWeight($order);
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
