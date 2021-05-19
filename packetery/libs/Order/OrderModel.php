<?php

namespace Packetery\Order;

use \Db;

class OrderModel
{
    /**
     * @param int $cartId
     * @return bool
     */
    public function existsByCart($cartId)
    {
        $result = Db::getInstance()->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . 'packetery_order` WHERE `id_cart` = ' . (int)$cartId
        );

        return ((int)$result === 1);
    }
}
