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
     * @param int $orderId
     * @param int $carrierId
     */
    public function updateCarrierId($orderId, $carrierId)
    {
        $this->db->update('packetery_order', ['id_carrier' => $carrierId], '`id_order` = ' . $orderId);
    }

    public function setIdBranchNull()
    {
        $this->db->update('packetery_order', ['id_branch' => null], '`id_branch` = 0', 0, true);
    }

    /**
     * @return mixed
     */
    public function getWithoutIdCarrier()
    {
        return $this->db->executeS(
            'SELECT `po`.`id_order`, `o`.`id_carrier`, `pa`.`id_carrier` AS `id_carrier_pa` 
            FROM `' . _DB_PREFIX_ . 'packetery_order` `po`
            JOIN `' . _DB_PREFIX_ . 'orders` `o` ON `o`.`id_order` = `po`.`id_order`
            LEFT JOIN `' . _DB_PREFIX_ . 'packetery_address_delivery` `pa` ON `pa`.`id_carrier` = `o`.`id_carrier`
            WHERE `po`.`id_carrier` = 0');
    }
}
