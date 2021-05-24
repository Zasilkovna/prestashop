<?php

namespace Packetery\Order;

use \Db;

class OrderRepository
{
    private $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @param int $cartId
     * @return bool
     */
    public function existsByCart($cartId)
    {
        $result = $this->db->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . 'packetery_order` WHERE `id_cart` = ' . (int)$cartId
        );

        return ((int)$result === 1);
    }

    /**
     * @param array $data
     * @throws \PrestaShopDatabaseException
     */
    public function save($data) {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->db->escape($value);
            }
        }
        $this->db->insert('packetery_order', $data, true, true, Db::ON_DUPLICATE_KEY);
    }

    /**
     * @param int $orderId
     */
    public function delete($orderId)
    {
        $this->db->delete('packetery_order', '`id_order` = ' . $orderId);
    }

}
