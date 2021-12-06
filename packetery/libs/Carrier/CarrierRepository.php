<?php

namespace Packetery\Carrier;

use Db;
use Packetery\Exceptions\DatabaseException;
use Packetery\Tools\DbTools;
use Tools;

class CarrierRepository
{
    /** @var Db $db */
    public $db;

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
     * Get all active packetery AD carriers
     * @return array|false|\mysqli_result|null|\PDOStatement|resource
     * @throws DatabaseException
     */
    public function getPacketeryCarriersList()
    {
        return $this->dbTools->getRows('
            SELECT `c`.`id_carrier`, `c`.`name`, `pad`.`id_branch`, `pad`.`is_cod`, `pad`.`pickup_point_type` 
            FROM `' . _DB_PREFIX_ . 'carrier` `c`
            LEFT JOIN `' . _DB_PREFIX_ . 'packetery_address_delivery` `pad` USING(`id_carrier`)
            WHERE `c`.`deleted` = 0
            AND `c`.`active` = 1
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
                   `address_validation`
            FROM `' . _DB_PREFIX_ . 'packetery_address_delivery`
            WHERE `id_carrier` = ' . $carrierId);
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
     * @param int $isCod
     * @return bool
     * @throws DatabaseException
     */
    public function setCodFlag($carrierId, $isCod)
    {
        $carrierId = (int)$carrierId;
        $isCod = (int)$isCod;
        return $this->dbTools->update('packetery_address_delivery', ['is_cod' => $isCod], '`id_carrier` = ' . $carrierId);
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

    // Methods below work with branch table, which is now used only for carriers obtained through API feed
    // probably the table will be renamed in future

    /**
     * @return false|string|null
     * @throws DatabaseException
     */
    public function getAdAndExternalCount()
    {
        $result = $this->dbTools->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'packetery_branch');
        if ($result > 0) {
            return $result;
        }

        return false;
    }

    /**
     * @return array
     * @throws DatabaseException
     */
    public function getAdAndExternalCarriers()
    {
        $sql = 'SELECT `id_branch`, `name`, `country`, `currency`, `is_pickup_point`
                FROM `' . _DB_PREFIX_ . 'packetery_branch`
                WHERE `is_ad` = 1 OR `is_pickup_point` = 1
                ORDER BY `country`, `name`';
        $result = $this->dbTools->getRows($sql);
        $branches = [];
        if ($result) {
            foreach ($result as $branch) {
                $branches[] = array(
                    'id_branch' => $branch['id_branch'],
                    'name' => $branch['name'] . ', ' . Tools::strtoupper($branch['country']),
                    'currency' => $branch['currency'],
                    'pickup_point_type' => ($branch['is_pickup_point'] ? 'external' : null),
                );
            }
        }
        return $branches;
    }

    /**
     * @param object $branch
     * @param string $openingHoursTableLong
     * @param string $openingHoursCompactShort
     * @param string $openingHoursCompactLong
     * @param string $openingHoursRegular
     * @return bool
     * @throws DatabaseException
     */
    public function addBranch($branch, $openingHoursTableLong, $openingHoursCompactShort, $openingHoursCompactLong, $openingHoursRegular)
    {
        // TODO: use "insert" method
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'packetery_branch VALUES(
                    ' . (int)$branch->id . ',
                    \'' . $this->db->escape($branch->name) . '\',
                    \'' . $this->db->escape($branch->nameStreet) . '\',
                    \'' . $this->db->escape($branch->place) . '\',
                    \'' . $this->db->escape($branch->street) . '\',
                    \'' . $this->db->escape($branch->city) . '\',
                    \'' . $this->db->escape($branch->zip) . '\',
                    \'' . $this->db->escape($branch->country) . '\',
                    \'' . $this->db->escape($branch->currency) . '\',
                    \'' . $this->db->escape($branch->wheelchairAccessible) . '\',
                    \'' . $this->db->escape($branch->latitude) . '\',
                    \'' . $this->db->escape($branch->longitude) . '\',
                    \'' . $this->db->escape($branch->url) . '\',
                    ' . (int)$branch->dressingRoom . ',
                    ' . (int)$branch->claimAssistant . ',
                    ' . (int)$branch->packetConsignment . ',
                    ' . (int)$branch->maxWeight . ',
                    \'' . $this->db->escape($branch->region) . '\',
                    \'' . $this->db->escape($branch->district) . '\',
                    \'' . $this->db->escape($branch->labelRouting) . '\',
                    \'' . $this->db->escape($branch->labelName) . '\',
                    \'' . $this->db->escape($openingHoursTableLong) . '\',
                    \'' . $this->db->escape($branch->photos->photo->normal) . '\',
                    \'' . $this->db->escape($openingHoursCompactShort) . '\',
                    \'' . $this->db->escape($openingHoursCompactLong) . '\',
                    \'' . $this->db->escape($openingHoursRegular) . '\',
                    0,
                    0
                    );';
        return $this->dbTools->execute($sql);
    }

    /**
     * @param object $carrier
     * @throws DatabaseException
     */
    public function addCarrier($carrier)
    {
        // TODO: use "insert" method
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'packetery_branch` VALUES(
                    ' . (int)$carrier->id . ',
                    \'' . $this->db->escape($carrier->name) . '\',
                    \'' . $this->db->escape($carrier->labelName) . '\',
                    \'\',
                    \'\',
                    \'\',
                    \'\',
                    \'' . $this->db->escape($carrier->country) . '\',
                    \'' . $this->db->escape($carrier->currency) . '\',
                    \'\',
                    \'\',
                    \'\',
                    \'\',
                    0,
                    0,
                    0,
                    0,
                    \'\',
                    \'\',
                    \'' . $this->db->escape($carrier->labelRouting) . '\',
                    \'' . $this->db->escape($carrier->labelName) . '\',
                    \'\',
                    \'\',
                    \'\',
                    \'\',
                    \'\',
                    ' . ((string)$carrier->pickupPoints === 'false' ? 1 : 0) . ',
                    ' . ((string)$carrier->pickupPoints === 'true' ? 1 : 0) . '
                    );';

        $this->dbTools->execute($sql);
    }

    /**
     * @throws DatabaseException
     */
    public function dropBranchList()
    {
        $this->dbTools->delete('packetery_branch');
    }

}
