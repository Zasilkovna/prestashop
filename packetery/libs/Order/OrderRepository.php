<?php

namespace Packetery\Order;

use Db;
use Packetery\Exceptions\DatabaseException;
use Packetery\Tools\DbTools;
use PrestaShopLoggerCore as PrestaShopLogger;

class OrderRepository
{
    /** @var Db $db */
    public $db;

    /** @var DbTools */
    private $dbTools;

    /**
     * OrderRepository constructor.
     * @param Db $db
     * @param DbTools $dbTools
     */
    public function __construct(Db $db, DbTools $dbTools)
    {
        $this->db = $db;
        $this->dbTools = $dbTools;
    }

    /**
     * @param int $cartId
     * @return bool
     * @throws DatabaseException
     */
    public function existsByCart($cartId)
    {
        $cartId = (int)$cartId;
        $result = $this->dbTools->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . 'packetery_order` WHERE `id_cart` = ' . $cartId
        );

        return ((int)$result === 1);
    }

    /**
     * @param int $orderId
     * @return bool
     * @throws DatabaseException
     */
    public function existsByOrder($orderId)
    {
        $orderId = (int)$orderId;
        $result = $this->dbTools->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . 'packetery_order` WHERE `id_order` = ' . $orderId
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
            $this->dbTools->insert('packetery_order', $data, true, true, Db::ON_DUPLICATE_KEY);
        } catch (DatabaseException $exception) {
            // there are more details in Packeta log
            PrestaShopLogger::addLog($exception->getMessage(), 3, null, null, null, true);
        }
    }

    /**
     * @param array $fields
     * @return bool
     * @throws DatabaseException
     */
    public function insert(array $fields)
    {
        return $this->dbTools->insert('packetery_order', $fields);
    }

    /**
     * @param array $fields
     * @param int $cartId
     * @return bool
     * @throws DatabaseException
     */
    public function updateByCart(array $fields, $cartId)
    {
        $cartId = (int)$cartId;
        return $this->dbTools->update('packetery_order', $fields, '`id_cart` = ' . $cartId);
    }

    /**
     * @param array $fields
     * @param int $orderId
     * @return bool
     * @throws DatabaseException
     */
    public function updateByOrder(array $fields, $orderId)
    {
        $orderId = (int)$orderId;
        return $this->dbTools->update('packetery_order', $fields, '`id_order` = ' . $orderId);
    }

    /**
     * @param int $orderId
     * @throws DatabaseException
     */
    public function delete($orderId)
    {
        $orderId = (int)$orderId;
        $this->dbTools->delete('packetery_order', '`id_order` = ' . $orderId);
    }

    /**
     * @param int $orderId
     * @param int $carrierId
     * @return bool
     * @throws DatabaseException
     */
    public function updateCarrierId($orderId, $carrierId)
    {
        $orderId = (int)$orderId;
        $carrierId = (int)$carrierId;
        return $this->dbTools->update('packetery_order', ['id_carrier' => $carrierId], '`id_order` = ' . $orderId);
    }

    /**
     * @param int $cartId
     * @return array|bool|object|null
     * @throws DatabaseException
     */
    public function getByCart($cartId)
    {
        $cartId = (int)$cartId;
        return $this->dbTools->getRow('SELECT `name_branch` FROM `' . _DB_PREFIX_ . 'packetery_order` WHERE `id_cart` = ' . $cartId);
    }

    /**
     * @param int $cartId
     * @param int $carrierId
     * @return array|bool|object|null
     * @throws DatabaseException
     */
    public function getByCartAndCarrier($cartId, $carrierId)
    {
        $cartId = (int)$cartId;
        $carrierId = (int)$carrierId;
        return $this->dbTools->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'packetery_order` WHERE `id_cart` = ' . $cartId . ' AND `id_carrier` = ' . $carrierId);
    }

    /**
     * @param int $orderId
     * @return array|bool|object|null
     * @throws DatabaseException
     */
    public function getOrderWithCountry($orderId)
    {
        $orderId = (int)$orderId;
        return $this->dbTools->getRow(
            'SELECT `po`.`id_carrier`, `po`.`id_branch`, `po`.`name_branch`, `po`.`is_ad`, `po`.`is_carrier`,
                    `c`.`iso_code` AS `country`
            FROM `' . _DB_PREFIX_ . 'packetery_order` `po`
            JOIN `' . _DB_PREFIX_ . 'orders` `o` ON `o`.`id_order` = `po`.`id_order`
            JOIN `' . _DB_PREFIX_ . 'address` `a` ON `a`.`id_address` = `o`.`id_address_delivery` 
            JOIN `' . _DB_PREFIX_ . 'country` `c` ON `c`.`id_country` = `a`.`id_country`
            WHERE `po`.`id_order` = ' . $orderId
        );
    }

    /**
     * @param int $orderId
     * @return array|bool|object|null
     * @throws DatabaseException
     */
    public function getById($orderId)
    {
        $orderId = (int)$orderId;
        return $this->dbTools->getRow('
            SELECT 
                   `id_branch`, 
                   `name_branch`,
                   `id_carrier`, 
                   `is_cod`, 
                   `is_ad`, 
                   `currency_branch`, 
                   `is_carrier`, 
                   `carrier_pickup_point` 
            FROM `' . _DB_PREFIX_ . 'packetery_order` 
            WHERE id_order = ' . $orderId);
    }

    /**
     * @param string $currencyIsoCode
     * @return false|string|null
     * @throws DatabaseException
     */
    public function getConversionRate($currencyIsoCode)
    {
        return $this->dbTools->getValue(
            'SELECT `cs`.`conversion_rate`
                FROM `' . _DB_PREFIX_ . 'currency_shop` `cs` 
                INNER JOIN `' . _DB_PREFIX_ . 'currency` `c` ON `c`.`id_currency` = `cs`.`id_currency` 
                AND `c`.`iso_code` = "' . $this->db->escape($currencyIsoCode) . '"'
        );
    }

    /**
     * @param string $orderIds comma separated integers
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     * @throws DatabaseException
     */
    public function getTrackingNumbers($orderIds)
    {
        return $this->dbTools->getRows(
            'SELECT `tracking_number`
                FROM `' . _DB_PREFIX_ . 'packetery_order` 
                WHERE `id_order` IN(' . $this->db->escape($orderIds) . ') AND `tracking_number` != ""'
        );
    }

    /**
     * @param int $orderId
     * @param string $trackingNumber
     * @return bool
     * @throws DatabaseException
     */
    public function setTrackingNumber($orderId, $trackingNumber)
    {
        $orderId = (int)$orderId;
        return $this->dbTools->update('packetery_order', ['tracking_number' => $this->db->escape($trackingNumber)], '`id_order` = ' . $orderId);
    }

    /**
     * @param int|bool $exported
     * @param int $orderId
     * @return bool
     * @throws DatabaseException
     */
    public function setExported($exported, $orderId)
    {
        $orderId = (int)$orderId;
        return $this->dbTools->update('packetery_order', ['exported' => $exported], '`id_order` = ' . $orderId);
    }

}
