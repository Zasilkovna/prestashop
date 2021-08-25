<?php

namespace Packetery\Order;

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
            $result['error'] = $this->module->l('No order weights to set provided.', 'ajax');
            return json_encode($result);
        }

        foreach ($orderWeights as $orderId => $weight) {
            if ($weight === '') {
                $weight = null;
            } else {
                $weight = str_replace([',', ' '], ['.', ''], $weight);
            }
            if ($weight === null || is_numeric($weight)) {
                $this->orderRepository->updateWeight($orderId, $weight);
                $result[$orderId]['value'] = $weight;
                if ($weight === null) {
                    $order = new \Order($orderId);
                    $result[$orderId]['value'] = $order->getTotalWeight();
                }
            } else {
                $result[$orderId]['error'] = $this->module->l('Please enter a number.', 'ajax');
            }
        }

        return json_encode($result);
    }
}
