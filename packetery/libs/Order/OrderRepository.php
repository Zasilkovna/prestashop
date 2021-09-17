<?php

namespace Packetery\Order;

use \Db;
use \PrestaShopDatabaseException;
use \PrestaShopLogger;

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
     */
    public function save($data)
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->db->escape($value);
            }
        }
        try {
            $this->db->insert('packetery_order', $data, true, true, Db::ON_DUPLICATE_KEY);
        } catch (PrestaShopDatabaseException $exception) {
            PrestaShopLogger::addLog($exception->getMessage(), 3, null, null, null, true);
        }
    }

    /**
     * @param int $orderId
     */
    public function delete($orderId)
    {
        $this->db->delete('packetery_order', '`id_order` = ' . $orderId);
    }

    /**
     * @param int $cartId
     */
    public function deleteByCartId($cartId)
    {
        $this->db->delete('packetery_order', '`id_cart` = ' . (int)$cartId);
    }

    /**
     * @param int $orderId
     * @param int $carrierId
     * @return bool
     */
    public function updateCarrierId($orderId, $carrierId)
    {
        return $this->db->update('packetery_order', ['id_carrier' => $carrierId], '`id_order` = ' . $orderId);
    }

    /**
     * @param int $orderId
     * @param int $weight
     * @return bool
     */
    public function updateWeight($orderId, $weight)
    {
        return $this->db->update('packetery_order', ['weight' => $weight], '`id_order` = ' . $orderId, 0, true);
    }

    /**
     * @param array $orderIds
     * @return array
     */
    public function getWeights(array $orderIds)
    {
        return $this->db->executeS(
            'SELECT `id_order`, `weight` FROM `' . _DB_PREFIX_ . 'packetery_order`
            WHERE `id_order` IN (' . implode(',', $orderIds) . ')'
        );
    }
}
