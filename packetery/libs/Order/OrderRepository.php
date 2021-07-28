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
     * Tested versions:
     * 1.6.0.6 - Db::ON_DUPLICATE_KEY missing
     * 1.6.1.24 - ok
     *
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
     * @param bool $nullValues
     * @return bool
     * @throws DatabaseException
     */
    public function insert(array $fields, $nullValues = false)
    {
        return $this->dbTools->insert('packetery_order', $fields, $nullValues);
    }

    /**
     * @param array $fields
     * @param int $cartId
     * @param bool $nullValues
     * @return bool
     * @throws DatabaseException
     */
    public function updateByCart(array $fields, $cartId, $nullValues = false)
    {
        $cartId = (int)$cartId;
        return $this->dbTools->update('packetery_order', $fields, '`id_cart` = ' . $cartId, 0, $nullValues);
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
    public function deleteByOrder($orderId)
    {
        $orderId = (int)$orderId;
        $this->dbTools->delete('packetery_order', '`id_order` = ' . $orderId);
    }

    /**
     * @param int $cartId
     */
    public function deleteByCart($cartId)
    {
        $cartId = (int)$cartId;
        $this->db->delete('packetery_order', '`id_cart` = ' . $cartId);
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
        return $this->dbTools->getRow('SELECT `is_ad`, `id_branch`, `name_branch`, `id_carrier`, `zip` FROM `' . _DB_PREFIX_ . 'packetery_order` WHERE `id_cart` = ' . $cartId);
    }

    /**
     * @param int $cartId
     * @return bool
     * @throws DatabaseException
     */
    public function isPickupPointChosenByCart($cartId)
    {
        $result = $this->dbTools->getValue('SELECT 1 FROM `' . _DB_PREFIX_ . 'packetery_order` WHERE `id_cart` = ' . (int)$cartId . ' AND `name_branch` IS NOT NULL');
        return ((int)$result === 1);
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
            'SELECT 
                   `po`.`id_order`, 
                   `po`.`id_carrier`, 
                   `po`.`id_branch`, 
                   `po`.`name_branch`, 
                   `po`.`is_ad`, 
                   `po`.`is_carrier`,
                   `po`.`country`, 
                   `po`.`street`, 
                   `po`.`house_number`, 
                   `po`.`city`, 
                   `po`.`zip`, 
                   `po`.`county`, 
                   `po`.`latitude`, 
                   `po`.`longitude`,
                   `po`.`weight`,
                   `po`.`exported`,
                   `c`.`iso_code` AS `ps_country`
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
                   `carrier_pickup_point`,
                   `tracking_number`,
                   `carrier_pickup_point`,
                   `weight`, 
                   `zip`,
                   `city`,
                   `street`,
                   `house_number`
            FROM `' . _DB_PREFIX_ . 'packetery_order` 
            WHERE id_order = ' . $orderId);
    }

    /**
     * @param int $orderId
     * @return array|bool|object|null
     * @throws DatabaseException
     */
    public function getWithShopById($orderId)
    {
        $orderId = (int)$orderId;
        return $this->dbTools->getRow('
            SELECT
                   `po`.`id_branch`,
                   `po`.`name_branch`,
                   `po`.`id_carrier`, 
                   `po`.`is_cod`, 
                   `po`.`is_ad`,
                   `po`.`currency_branch`, 
                   `po`.`is_carrier`,
                   `po`.`carrier_pickup_point`,
                   `po`.`tracking_number`,
                   `po`.`carrier_pickup_point`,
                   `po`.`weight`, 
                   `po`.`zip`,
                   `po`.`city`,
                   `po`.`street`,
                   `po`.`house_number`,
                   `o`.`id_shop_group`, 
                   `o`.`id_shop` 
            FROM `' . _DB_PREFIX_ . 'packetery_order` `po` 
            JOIN `' . _DB_PREFIX_ . 'orders` `o` ON `o`.`id_order` = `po`.`id_order`
            WHERE `po`.`id_order` = ' . $orderId);
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
        return $this->dbTools->update(
            'packetery_order',
            ['tracking_number' => $this->db->escape($trackingNumber), 'exported' => 1],
            '`id_order` = ' . $orderId
        );
    }

    /**
     * @param int $orderId
     * @return false|string|null
     * @throws DatabaseException
     */
    public function getCarrierNumber($orderId)
    {
        $orderId = (int)$orderId;
        return $this->dbTools->getValue('SELECT `carrier_number` FROM `' . _DB_PREFIX_ . 'packetery_order` WHERE `id_order` = ' . $orderId);
    }

    /**
     * @param int $orderId
     * @param string $carrierNumber
     * @return bool
     * @throws DatabaseException
     */
    public function setCarrierNumber($orderId, $carrierNumber)
    {
        $orderId = (int)$orderId;
        return $this->dbTools->update(
            'packetery_order',
            ['carrier_number' => $this->db->escape($carrierNumber)],
            '`id_order` = ' . $orderId
        );
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

    /**
     * @param int $orderId
     * @param float|null $value
     * @return bool
     * @throws DatabaseException
     */
    public function setWeight($orderId, $value)
    {
        $orderId = (int)$orderId;
        return $this->dbTools->update('packetery_order', ['weight' => $value], '`id_order` = ' . $orderId, 0, true);
    }

    /**
     * @param int $orderId
     * @return bool
     * @throws DatabaseException
     */
    public function isOrderAdult($orderId)
    {
        $sql = 'SELECT ppp.`is_adult` FROM `' . _DB_PREFIX_ . 'order_detail` pod 
                LEFT JOIN `' . _DB_PREFIX_ . 'packetery_product_attribute` ppp ON (pod.`product_id` = ppp.`id_product`)
                WHERE pod.`id_order` = ' . $orderId . ' AND ppp.`is_adult` = 1';

        return (bool) $this->dbTools->getValue($sql);
    }

}
