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
}
