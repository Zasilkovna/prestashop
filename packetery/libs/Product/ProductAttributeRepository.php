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
     * @return array|bool|object|null
     * @throws DatabaseException
     */
    public function get($idProduct)
    {
        return $this->dbTools->getRow(
            'SELECT * FROM ' . $this->getPrefixedTableName() . ' WHERE `id_product` = ' . $idProduct
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

    /**
     * @param int $idProduct
     * @param bool $isAdult
     * @return bool
     * @throws DatabaseException
     */
    public function insertUpdateIsAdult($idProduct, $isAdult)
    {
        $sql = 'INSERT INTO `' . $this->getPrefixedTableName() . '` (`id_product`, `is_adult`)
                values ( ' . $idProduct . ', ' . $isAdult . ')
                ON DUPLICATE KEY UPDATE `id_product` = ' . $idProduct . ', `is_adult` = ' . $isAdult;
        return $this->dbTools->execute($sql);
    }

}
