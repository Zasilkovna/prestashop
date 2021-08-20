<?php

namespace Packetery\Order;

class Ajax
{
    /** @var OrderRepository */
    private $orderRepository;

    /**
     * Ajax constructor.
     * @param OrderRepository $orderRepository
     */
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }


    public function setWeights()
    {
        $orderWeights = (\Tools::getIsset('orderWeights') ? \Tools::getValue('orderWeights') : null);
        if (empty($orderWeights)) {
            return 'No order weights to set provided.';
        }

        foreach ($orderWeights as $orderId => $weight) {
            if ($weight === '') {
                $weight = null;
            } else {
                $weight = (float)str_replace([',', ' '], ['.', ''], $weight);
            }
            $this->orderRepository->updateWeight($orderId, $weight);
        }

        return 'ok';
    }
}
