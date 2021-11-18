<?php

namespace Packetery\Order;

use Db;
use PrestaShopDatabaseExceptionCore as PrestaShopDatabaseException;
use PrestaShopException;
use PrestaShopLoggerCore as PrestaShopLogger;

class OrderRepository
{
    /** @var Db $db */
    public $db;

    /**
     * OrderRepository constructor.
     * @param Db $db
     */
    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @param int $cartId
     * @return bool
     * @throws PrestaShopException
     */
    public function existsByCart($cartId)
    {
        $result = $this->db->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . 'packetery_order` WHERE `id_cart` = ' . (int)$cartId
        );

        return ((int)$result === 1);
    }

    /**
     * @param int $orderId
     * @return bool
     * @throws PrestaShopException
     */
    public function existsByOrder($orderId)
    {
        $result = $this->db->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . 'packetery_order` WHERE `id_order` = ' . (int)$orderId
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
     * @param array $fields
     * @return bool
     * @throws \PrestaShopDatabaseException
     */
    public function insert($fields)
    {
        return $this->db->insert('packetery_order', $fields);
    }

    /**
     * @param array $fields
     * @param int $cartId
     * @return bool
     * @throws \PrestaShopDatabaseException
     */
    public function updateByCart($fields, $cartId)
    {
        return $this->db->update('packetery_order', $fields, '`id_cart` = ' . $cartId);
    }

    /**
     * @param array $fields
     * @param int $orderId
     * @return bool
     * @throws \PrestaShopDatabaseException
     */
    public function updateByOrder($fields, $orderId)
    {
        return $this->db->update('packetery_order', $fields, '`id_order` = ' . $orderId);
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
     * @return bool
     * @throws \PrestaShopDatabaseException
     */
    public function updateCarrierId($orderId, $carrierId)
    {
        return $this->db->update('packetery_order', ['id_carrier' => $carrierId], '`id_order` = ' . $orderId);
    }

    /**
     * @param int $cartId
     * @return array|bool|object|null
     * @throws PrestaShopException
     */
    public function getByCart($cartId)
    {
        return $this->db->getRow('SELECT `is_ad`, `zip`, `name_branch` FROM `' . _DB_PREFIX_ . 'packetery_order` WHERE `id_cart` =' . $cartId);
    }

    /**
     * @param int $cartId
     * @param int $carrierId
     * @return array|bool|object|null
     * @throws PrestaShopException
     */
    public function getByCartAndCarrier($cartId, $carrierId)
    {
        return $this->db->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'packetery_order` WHERE `id_cart` =' . $cartId . ' AND `id_carrier` = ' . $carrierId);
    }

    /**
     * @param int $orderId
     * @return array|bool|object|null
     * @throws PrestaShopException
     */
    public function getOrderWithCountry($orderId)
    {
        return $this->db->getRow(
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
     * @throws PrestaShopException
     */
    public function getById($orderId)
    {
        return $this->db->getRow('
            SELECT 
                   `id_branch`, 
                   `name_branch`
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
     * @param $currencyIsoCode
     * @return false|string|null
     * @throws PrestaShopException
     */
    public function getConversionRate($currencyIsoCode)
    {
        return $this->db->getValue(
            'SELECT `cs`.`conversion_rate`
                FROM `' . _DB_PREFIX_ . 'currency_shop` `cs` 
                INNER JOIN `' . _DB_PREFIX_ . 'currency` `c` ON `c`.`id_currency` = `cs`.`id_currency` 
                AND `c`.`iso_code` = "' . $this->db->escape($currencyIsoCode) . '"'
        );
    }

    /**
     * @param string $orderIds comma separated integers
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     * @throws PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    public function getTrackingNumbers($orderIds)
    {
        return $this->db->executeS(
            'SELECT `tracking_number`
                FROM `' . _DB_PREFIX_ . 'packetery_order` 
                WHERE `id_order` IN(' . $this->db->escape($orderIds) . ') AND `tracking_number` != ""'
        );
    }

    /**
     * @param int $orderId
     * @param string $trackingNumber
     * @return bool
     * @throws PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    public function setTrackingNumber($orderId, $trackingNumber)
    {
        return $this->db->execute('UPDATE `' . _DB_PREFIX_ . 'packetery_order` 
            SET `tracking_number` = "' . $this->db->escape($trackingNumber) . '"
            WHERE `id_order` = ' . $orderId);
    }

    /**
     * @param int|bool $exported
     * @param int $orderId
     * @return bool
     * @throws PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    public function setExported($exported, $orderId)
    {
        return $this->db->execute('UPDATE `' . _DB_PREFIX_ . 'packetery_order` 
            SET `exported` = ' . $exported . '
            WHERE `id_order` = ' . $orderId );
    }

}
