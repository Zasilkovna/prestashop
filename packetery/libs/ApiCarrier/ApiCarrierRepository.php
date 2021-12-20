<?php

namespace Packetery\ApiCarrier;

use Packetery;
use Packetery\Exceptions\DatabaseException;
use Packetery\Tools\DbTools;
use Packeteryclass;

class ApiCarrierRepository
{
    /** @var DbTools */
    private $dbTools;

    /** @var Packetery|null */
    private $module;

    private static $tableName = 'packetery_carriers';

    /**
     * CarrierRepository constructor.
     * @param DbTools $dbTools
     */
    public function __construct(DbTools $dbTools)
    {
        $this->dbTools = $dbTools;
    }

    private function getPrefixedTableName()
    {
        return _DB_PREFIX_ . self::$tableName;
    }

    /**
     * Maps input data to storage structure.
     * @param array $carriers Validated data retrieved from API.
     * @return array data to store in db
     */
    private function carriersMapper(array $carriers)
    {
        $mappedData = array();

        $carrierBooleanParams = array(
            'is_pickup_points' => 'pickupPoints',
            'has_carrier_direct_label' => 'apiAllowed',
            'separate_house_number' => 'separateHouseNumber',
            'customs_declarations' => 'customsDeclarations',
            'requires_email' => 'requiresEmail',
            'requires_phone' => 'requiresPhone',
            'requires_size' => 'requiresSize',
            'disallows_cod' => 'disallowsCod',
        );

        foreach ($carriers as $carrier) {
            $carrierId = (int)$carrier['id'];
            $carrierData = array(
                'name' => $this->dbTools->db->escape($carrier['name']),
                'country' => $this->dbTools->db->escape($carrier['country']),
                'currency' => $this->dbTools->db->escape($carrier['currency']),
                'max_weight' => (float)$carrier['maxWeight'],
                'deleted' => false,
            );
            foreach ($carrierBooleanParams as $columnName => $paramName) {
                $carrierData[$columnName] = ('true' === $carrier[$paramName]);
            }
            $mappedData[$carrierId] = $carrierData;
        }

        return $mappedData;
    }

    /**
     * @param array $mappedData data to store in db
     * @return array
     */
    private function addNonApiCarriers(array $mappedData) {
        $defaultPickupPointsValues = [
            'country' => '',
            'currency' => '',
            'max_weight' => 10,
            'deleted' => false,
            'is_pickup_points' => true,
            'has_carrier_direct_label' => false,
            'separate_house_number' => false,
            'customs_declarations' => false,
            'requires_email' => false,
            'requires_phone' => false,
            'requires_size' => false,
            'disallows_cod' => false,
        ];
        $mappedData[Packeteryclass::ZPOINT] = $defaultPickupPointsValues;
        $mappedData[Packeteryclass::ZPOINT]['name'] = $this->module->l('Packeta pickup points', 'apicarrierrepository');
        $mappedData[Packeteryclass::PP_ALL] = $defaultPickupPointsValues;
        $mappedData[Packeteryclass::PP_ALL]['name'] = $this->module->l('Packeta pickup points (Packeta + carriers)', 'apicarrierrepository');

        return $mappedData;
    }

    /**
     * Saves carriers.
     * @param array $carriers Validated data retrieved from API.
     * @throws DatabaseException
     */
    public function save(array $carriers, Packetery $module)
    {
        $this->module = $module;
        $mappedData = $this->carriersMapper($carriers);
        $mappedData = $this->addNonApiCarriers($mappedData);
        $carriersInFeed = array();

        $carrierCheck = $this->getCarrierIds();
        $carriersInDb = array_column($carrierCheck, 'id');
        foreach ($mappedData as $carrierId => $carrier) {
            $carriersInFeed[] = $carrierId;
            if (in_array((string)$carrierId, $carriersInDb, true)) {
                $this->update($carrier, (string)$carrierId);
            } else {
                $carrier['id'] = $carrierId;
                $this->insert($carrier);
            }
        }

        $this->setOthersAsDeleted($carriersInFeed);
    }

    public function getCreateTableSql()
    {
        return 'CREATE TABLE `' . $this->getPrefixedTableName() . '` (
            `id` varchar(255) NOT NULL,
            `name` varchar(255) NOT NULL,
            `is_pickup_points` boolean NOT NULL,
            `has_carrier_direct_label` boolean NOT NULL,
            `separate_house_number` boolean NOT NULL,
            `customs_declarations` boolean NOT NULL,
            `requires_email` boolean NOT NULL,
            `requires_phone` boolean NOT NULL,
            `requires_size` boolean NOT NULL,
            `disallows_cod` boolean NOT NULL,
            `country` varchar(255) NOT NULL,
            `currency` varchar(255) NOT NULL,
            `max_weight` float NOT NULL,
            `deleted` boolean NOT NULL,
            UNIQUE (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
    }

    public function getDropTableSql()
    {
        return 'DROP TABLE IF EXISTS `' . $this->getPrefixedTableName() . '`;';
    }

    /**
     * @param array $data
     * @throws DatabaseException
     */
    public function insert(array $data)
    {
        $this->dbTools->insert(self::$tableName, $data);
    }

    /**
     * @param array $data
     * @param string $carrierId
     * @throws DatabaseException
     */
    public function update(array $data, $carrierId)
    {
        $carrierId = (string)$carrierId;
        $this->dbTools->update(self::$tableName, $data, '`id` = "' . $this->dbTools->db->escape($carrierId) . '"');
    }

    /**
     * @throws DatabaseException
     */
    public function getCarrierIds()
    {
        return $this->dbTools->getRows('SELECT `id` FROM `' . $this->getPrefixedTableName() . '`');
    }

    /**
     * Set those not in feed as deleted.
     * @param array $carriersInFeed
     * @throws DatabaseException
     */
    public function setOthersAsDeleted(array $carriersInFeed)
    {
        $carriersInFeedSql = '"' . implode('","', $carriersInFeed) . '"';
        $this->dbTools->update(self::$tableName, ['deleted' => 1], '`id` NOT IN (' . $carriersInFeedSql . ')');
    }

    /**
     * @return false|string|null
     * @throws DatabaseException
     */
    public function getAdAndExternalCount()
    {
        $result = $this->dbTools->getValue('SELECT COUNT(*) FROM `' . $this->getPrefixedTableName() . '`');
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
        $sql = 'SELECT `id`, `name`, `country`, `currency`, `is_pickup_points`
                FROM `' . $this->getPrefixedTableName() . '`
                WHERE `deleted` = 0
                ORDER BY `country`, `name`';
        $result = $this->dbTools->getRows($sql);
        $carriers = [];
        if ($result) {
            foreach ($result as $carrier) {
                if ($carrier['id'] === \Packeteryclass::ZPOINT) {
                    $pickupPointType = 'internal';
                } else {
                    $pickupPointType = ($carrier['is_pickup_points'] ? 'external' : null);
                }
                $carriers[] = [
                    'id_branch' => $carrier['id'],
                    'name' => $carrier['name'],
                    'currency' => $carrier['currency'],
                    'pickup_point_type' => $pickupPointType,
                ];
            }
        }
        return $carriers;
    }
}
