<?php

namespace Packetery\Product;

use Packetery\Tools\DbTools;
use Packetery\Exceptions\DatabaseException;

class ProductAttributeRepository
{

    /** @var DbTools */
    private $dbTools;

    public static $tableName = 'packetery_product_attribute';

    /**
     * ProductRepository constructor.
     * @param DbTools $dbTools
     */
    public function __construct(DbTools $dbTools)
    {
        $this->dbTools = $dbTools;
    }

    /**
     * @param int $idProduct
     * @return array|false
     * @throws DatabaseException
     */
    public function getRow($idProduct)
    {
        $getRow = $this->dbTools->getRow(
            'SELECT
                `id_product`,
                `is_adult`
                FROM ' . $this->getPrefixedTableName() . ' WHERE `id_product` = ' . $idProduct
        );

        if (is_array($getRow)) {
            return $getRow;
        }
        return false;
    }

    /**
     * @param int $idProduct
     * @param string $value
     * @return string|false
     * @throws DatabaseException
     */
    public function getValue($idProduct, $value)
    {
        $getValue = $this->dbTools->getValue(
            'SELECT '.pSQL($value).' FROM ' . $this->getPrefixedTableName() . ' WHERE `id_product` = ' . $idProduct
        );

        if (is_string($getValue)) {
            return $getValue;
        }
        return false;
    }

    /**
     * @param array $data
     * @return bool
     * @throws DatabaseException
     */
    public function insert(array $data)
    {
        return $this->dbTools->insert(
            self::$tableName,
            $data
        );
    }

    /**
     * @param array $data
     * @param int $idProduct
     * @return bool
     * @throws DatabaseException
     */
    public function update($idProduct, array $data)
    {
        return $this->dbTools->update(
            self::$tableName,
            $data,
            '`id_product` = '. $idProduct
        );
    }

    /**
     * @param int $idProduct
     * @return bool
     * @throws DatabaseException
     */
    public function delete($idProduct)
    {
        return $this->dbTools->delete(
            self::$tableName,
            '`id_product` = ' . $idProduct
        );
    }

    /**
     * @return string
     */
    private function getPrefixedTableName()
    {
        return _DB_PREFIX_ . self::$tableName;
    }
}
