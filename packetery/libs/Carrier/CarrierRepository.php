<?php

namespace Packetery\Carrier;

use Db;
use Packetery;
use Packetery\ApiCarrier\ApiCarrierRepository;
use Packetery\Exceptions\DatabaseException;
use Packetery\Tools\DbTools;

class CarrierRepository
{
    /** @var Db $db */
    private $db;

    /** @var DbTools */
    private $dbTools;

    /**
     * CarrierRepository constructor.
     * @param Db $db
     * @param DbTools $dbTools
     */
    public function __construct(Db $db, DbTools $dbTools)
    {
        $this->db = $db;
        $this->dbTools = $dbTools;
    }

    /**
     * @param int $carrierId
     * @return bool
     * @throws DatabaseException
     */
    public function existsById($carrierId)
    {
        $carrierId = (int)$carrierId;
        $result = $this->dbTools->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . 'packetery_address_delivery` WHERE `id_carrier` = ' . $carrierId
        );

        return ((int)$result === 1);
    }

    /**
     * Get all active packetery carriers
     * @return array|false|\mysqli_result|null|\PDOStatement|resource
     * @throws DatabaseException
     */
    public function getPacketeryCarriersList()
    {
        return $this->dbTools->getRows('
            SELECT `c`.`id_carrier`, `c`.`name`, `pad`.`id_branch`, `pad`.`name_branch`, `pad`.`is_cod`, `pad`.`pickup_point_type` 
            FROM `' . _DB_PREFIX_ . 'carrier` `c`
            LEFT JOIN `' . _DB_PREFIX_ . 'packetery_address_delivery` `pad` USING(`id_carrier`)
            LEFT JOIN `' . _DB_PREFIX_ . ApiCarrierRepository::$tableName . '` `ac` ON `ac`.`id` = `pad`.`id_branch` 
            WHERE `c`.`deleted` = 0 AND `c`.`active` = 1
            ORDER BY `ac`.`country`, `ac`.`name`
        ');
    }

    /**
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     * @throws DatabaseException
     */
    public function getPickupPointCarriers()
    {
        return $this->dbTools->getRows(
            'SELECT `pad`.`id_carrier` FROM `' . _DB_PREFIX_ . 'packetery_address_delivery` `pad`
            JOIN `' . _DB_PREFIX_ . 'carrier` `c` USING(`id_carrier`)
            WHERE `c`.`deleted` = 0 AND `pad`.`pickup_point_type` IS NOT NULL'
        );
    }

    /**
     * @return array|false
     * @throws DatabaseException
     */
    public function getAddressValidationLevels()
    {
        $result = $this->dbTools->getRows(
            'SELECT `id_carrier`, `address_validation` FROM `' . _DB_PREFIX_ . 'packetery_address_delivery`
            WHERE `pickup_point_type` IS NULL'
        );
        return $this->dbTools->getPairs($result, 'id_carrier', 'address_validation');
    }

    /**
     * @param int $carrierId
     * @return array|bool|object|null
     * @throws DatabaseException
     */
    public function getPacketeryCarrierById($carrierId)
    {
        $carrierId = (int)$carrierId;
        return $this->dbTools->getRow('
            SELECT `id_carrier`, `id_branch`, `name_branch`, `currency_branch`, `pickup_point_type`, `is_cod`, 
                   `address_validation`, `allowed_vendors`
            FROM `' . _DB_PREFIX_ . 'packetery_address_delivery`
            WHERE `id_carrier` = ' . $carrierId);
    }

    /**
     * @param int $carrierId
     * @return array|bool|object|null
     * @throws DatabaseException
     */
    public function getById($carrierId)
    {
        $carrierId = (int)$carrierId;
        return $this->dbTools->getRow('
            SELECT `c`.`id_carrier`, `name`, `id_branch`, `is_cod`, `address_validation`, `allowed_vendors`
            FROM `' . _DB_PREFIX_ . 'carrier` `c`
            LEFT JOIN `' . _DB_PREFIX_ . 'packetery_address_delivery` `pad` USING(`id_carrier`)
            WHERE `c`.`id_carrier` = ' . $carrierId);
    }

    /**
     * @param string $carrierId
     * @return bool
     * @throws DatabaseException
     */
    public function isPickupPointCarrier($carrierId)
    {
        $result = $this->dbTools->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . 'packetery_carriers` WHERE is_pickup_points = 1 AND `id` = "' . $this->db->escape($carrierId) . '"'
        );

        return ((int)$result === 1);
    }

    /**
     * @param int $oldId
     * @param int $newId
     * @throws DatabaseException
     */
    public function swapId($oldId, $newId)
    {
        $oldId = (int)$oldId;
        $newId = (int)$newId;
        $this->dbTools->update('packetery_address_delivery', ['id_carrier' => $newId], '`id_carrier` = ' . $oldId);
    }

    /**
     * @param int $carrierId
     * @return bool
     * @throws DatabaseException
     */
    public function deleteById($carrierId)
    {
        $carrierId = (int)$carrierId;
        return $this->dbTools->delete('packetery_address_delivery', '`id_carrier` = ' . $carrierId);
    }

    /**
     * @param array $fieldsToSet
     * @return bool
     * @throws DatabaseException
     */
    public function insertPacketery(array $fieldsToSet)
    {
        return $this->dbTools->insert('packetery_address_delivery', $fieldsToSet, true);
    }

    /**
     * @param array $carrierUpdate
     * @param int $carrierId
     * @throws DatabaseException
     */
    public function updatePresta(array $carrierUpdate, $carrierId)
    {
        $carrierId = (int)$carrierId;
        $this->dbTools->update('carrier', $carrierUpdate, '`id_carrier` = ' . $carrierId, 0, true);
    }

    /**
     * @param array $carrierUpdate
     * @param int $carrierId
     * @return bool
     * @throws DatabaseException
     */
    public function updatePacketery(array $carrierUpdate, $carrierId)
    {
        $carrierId = (int)$carrierId;
        return $this->dbTools->update('packetery_address_delivery', $carrierUpdate, '`id_carrier` = ' . $carrierId, 0, true);
    }

    /**
     * Add address delivery to carrier in DB
     * @param int $carrierId
     * @param string $branchId
     * @param string $branchName
     * @param string|null $branchCurrency
     * @param string|null $pickupPointType
     * @param bool $isCod
     * @param string|null $addressValidation
     * @return bool
     * @throws DatabaseException
     */
    public function setPacketeryCarrier(
        $carrierId,
        $branchId,
        $branchName,
        $branchCurrency,
        $pickupPointType,
        $isCod,
        $addressValidation,
        $allowedVendors
    )
    {
        $carrierId = (int)$carrierId;
        $branchId = (string)$branchId;
        $branchName = (string)$branchName;
        $isCod = (bool)$isCod;

        $isPacketeryCarrier = $this->existsById($carrierId);
        if ($branchId === '' && $isPacketeryCarrier) {
            $carrierUpdate = ['is_module' => 0, 'external_module_name' => null, 'need_range' => 0, 'allowed_vendors' => null];
            $result = $this->deleteById($carrierId);
        } else {
            $fieldsToSet = [
                'pickup_point_type' => $pickupPointType,
                'id_branch'         => $this->db->escape($branchId),
                'name_branch'       => $this->db->escape($branchName),
                'is_cod'            => $isCod,
                'allowed_vendors'   => $allowedVendors
            ];
            if ($pickupPointType === null) {
                if (!$addressValidation) {
                    $addressValidation = 'none';
                }
                $fieldsToSet['address_validation'] = $addressValidation;
            } else {
                $fieldsToSet['address_validation'] = null;
            }
            if ($branchId === Packetery::ZPOINT || $branchId === Packetery::PP_ALL) {
                $fieldsToSet['currency_branch'] = null;
            } else {
                $fieldsToSet['currency_branch'] = $this->db->escape($branchCurrency);
            }
            if ($isPacketeryCarrier) {
                $result = $this->updatePacketery($fieldsToSet, $carrierId);
            } else {
                $fieldsToSet['id_carrier'] = $carrierId;
                $result = $this->insertPacketery($fieldsToSet);
            }
            $carrierUpdate = ['is_module' => 1, 'external_module_name' => 'packetery', 'need_range' => 1];
        }
        $this->updatePresta($carrierUpdate, $carrierId);

        return $result;
    }

}
