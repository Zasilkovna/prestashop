<?php

namespace Packetery\ApiCarrier;

use Packetery;
use Packetery\Exceptions\DatabaseException;
use Packetery\Tools\DbTools;

class ApiCarrierRepository
{
    private static $columnDefinitions = [
        'is_pickup_points' => [
            'apiName' => 'pickupPoints',
            'type' => 'bool',
            'defaultPPValue' => true
        ],
        'has_carrier_direct_label' => [
            'apiName' => 'apiAllowed',
            'type' => 'bool',
            'defaultPPValue' => false
        ],
        'separate_house_number' => [
            'apiName' => 'separateHouseNumber',
            'type' => 'bool',
            'defaultPPValue' => false
        ],
        'customs_declarations' => [
            'apiName' => 'customsDeclarations',
            'type' => 'bool',
            'defaultPPValue' => false
        ],
        'requires_email' => [
            'apiName' => 'requiresEmail',
            'type' => 'bool',
            'defaultPPValue' => false
        ],
        'requires_phone' => [
            'apiName' => 'requiresPhone',
            'type' => 'bool',
            'defaultPPValue' => false
        ],
        'requires_size' => [
            'apiName' => 'requiresSize',
            'type' => 'bool',
            'defaultPPValue' => false
        ],
        'disallows_cod' => [
            'apiName' => 'disallowsCod',
            'type' => 'bool',
            'defaultPPValue' => false
        ],
        'country' => ['defaultPPValue' => ''],
        'currency' => ['defaultPPValue' => ''],
        'max_weight' => ['defaultPPValue' => 10],
        'deleted' => ['defaultPPValue' => false],
    ];

    /** @var DbTools */
    private $dbTools;

    /** @var Packetery|null */
    private $module;

    public static $tableName = 'packetery_carriers';

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
        $mappedData = [];
        foreach ($carriers as $carrier) {
            $carrierId = (int)$carrier['id'];
            $carrierData = [
                'name' => $this->dbTools->db->escape($carrier['name']),
                'country' => $this->dbTools->db->escape($carrier['country']),
                'currency' => $this->dbTools->db->escape($carrier['currency']),
                'max_weight' => (float)$carrier['maxWeight'],
                'deleted' => false,
            ];
            foreach (self::$columnDefinitions as $columnName => $columnOptions) {
                if (isset($columnOptions['type']) && $columnOptions['type'] === 'bool') {
                    $carrierData[$columnName] = ('true' === $carrier[$columnOptions['apiName']]);
                }
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
        $defaultPickupPointsValues = array_combine(array_keys(self::$columnDefinitions), array_column(self::$columnDefinitions, 'defaultPPValue'));
        $mappedData[Packetery::ZPOINT] = $defaultPickupPointsValues;
        $mappedData[Packetery::ZPOINT]['name'] = $this->module->l('Packeta pickup points', 'apicarrierrepository');
        $mappedData[Packetery::PP_ALL] = $defaultPickupPointsValues;
        $mappedData[Packetery::PP_ALL]['name'] = $this->module->l('Packeta pickup points (Packeta + carriers)', 'apicarrierrepository');

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
        $carriersInFeed = [];

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
     * @return int
     * @throws DatabaseException
     */
    public function getAdAndExternalCount()
    {
        $result = $this->dbTools->getValue('SELECT COUNT(*) FROM `' . $this->getPrefixedTableName() . '`');
        if ($result > 0) {
            return (int)$result;
        }

        return 0;
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
                if ($carrier['id'] === Packetery::ZPOINT) {
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

    /**
     * @param array $countryIsoCodes
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     * @throws DatabaseException
     */
    public function getByCountries(array $countryIsoCodes)
    {
        $countryIsoCodesSql = '"' . implode('","', $countryIsoCodes) . '"';
        return $this->dbTools->getRows('SELECT `id`, `name`
            FROM `' . $this->getPrefixedTableName() . '`
            WHERE `country` IN (' . $countryIsoCodesSql . ') OR `country` = ""
            ORDER BY `country`, `name`');
    }

    /**
     * @param string $id
     * @return array|bool|object|null
     * @throws DatabaseException
     */
    public function getById($id)
    {
        return $this->dbTools->getRow('SELECT `id`, `name`, `currency`, `is_pickup_points`, `country`, `disallows_cod`
            FROM `' . $this->getPrefixedTableName() . '`
            WHERE `id` = "' . $this->dbTools->db->escape($id) . '"');
    }

    /**
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     * @throws DatabaseException
     */
    public function getExternalPickupPointCountries()
    {
        $result = $this->dbTools->getRows(
            'SELECT `country` FROM `' . $this->getPrefixedTableName() . '`
            WHERE `deleted` = 0 AND `is_pickup_points` = 1 AND `country` != ""
            GROUP BY `country`'
        );
        return array_column($result, 'country');
    }
}
