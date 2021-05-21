<?php

namespace Packetery\Order;

use \Db;

class OrderRepo
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
     * Sets default values to clear selected shipment method
     * @param int $orderId
     */
    public function clear($orderId)
    {
        $this->db->update('packetery_order', [
            'id_carrier' => 0,
            'id_branch' => null,
            'name_branch' => null,
            'currency_branch' => '',
            'is_ad' => 0,
            'is_carrier' => 0,
            'carrier_pickup_point' => null,
        ], '`id_order` = ' . $orderId, 0, true);
    }

    /**
     * @param int $orderId
     */
    public function delete($orderId) {
        $this->db->execute('DELETE FROM `' . _DB_PREFIX_ . 'packetery_order` WHERE `id_order` = ' . $orderId);
    }

}
