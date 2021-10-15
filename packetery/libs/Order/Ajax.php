<?php

namespace Packetery\Order;

use Packetery\Weight\Converter;

class Ajax
{
    /** @var OrderRepository */
    private $orderRepository;
    /** @var \Packetery */
    private $module;

    /**
     * Ajax constructor.
     * @param \Packetery $module
     * @param OrderRepository $orderRepository
     */
    public function __construct(\Packetery $module, OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->module = $module;
    }

    /**
     * @return string JSON encoded result.
     */
    public function actionSetWeights()
    {
        $result = [];
        $orderWeights = (\Tools::getIsset('orderWeights') ? \Tools::getValue('orderWeights') : null);
        if (empty($orderWeights)) {
            $result['info'] = $this->module->l('No changes to weights.', 'ajax');
            return json_encode($result);
        }

        $storedWeights = $this->orderRepository->getWeights(array_keys($orderWeights));
        if($storedWeights) {
            $storedWeightsAssoc = array_combine(
                array_column($storedWeights, 'id_order'),
                array_column($storedWeights, 'weight')
            );
        } else {
            $storedWeightsAssoc = [];
        }

        $changeCounter = 0;
        foreach ($orderWeights as $orderId => $weight) {
            if ($weight === '') {
                $weight = null;
            } else {
                $weight = str_replace([',', ' '], ['.', ''], $weight);
            }
            if ($weight === null || is_numeric($weight)) {
                if ($weight === null) {
                    $order = new \Order($orderId);
                    $result[$orderId]['value'] = Converter::getKilograms($order->getTotalWeight());
                }
                if ($weight != $storedWeightsAssoc[$orderId]) {
                    $this->orderRepository->updateWeight($orderId, $weight);
                    $changeCounter++;
                    if ($weight !== null) {
                        $result[$orderId]['value'] = $weight;
                    }
                }
            } else {
                $result[$orderId]['error'] = $this->module->l('Please enter a number.', 'ajax');
            }
        }
        if ($changeCounter === 0) {
            $result['info'] = $this->module->l('No changes to weights.', 'ajax');
        }

        return json_encode($result);
    }
}
