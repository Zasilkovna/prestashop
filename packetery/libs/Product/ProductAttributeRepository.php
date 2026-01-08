<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Product;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Exceptions\DatabaseException;
use Packetery\Tools\DbTools;

class ProductAttributeRepository
{
    /** @var DbTools */
    private $dbTools;

    public static $tableName = 'packetery_product_attribute';

    /**
     * ProductRepository constructor.
     *
     * @param DbTools $dbTools
     */
    public function __construct(DbTools $dbTools)
    {
        $this->dbTools = $dbTools;
    }

    /**
     * @param int $productId
     *
     * @return ProductAttributes|null
     */
    public function findByProductId($productId)
    {
        $productAttributesRow = $this->getRow($productId);
        if ($productAttributesRow === false) {
            return null;
        }

        return ProductAttributes::fromDbRow($productAttributesRow);
    }

    /**
     * @param int $idProduct
     *
     * @return array|false
     *
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
     * @param array $data
     *
     * @return bool
     *
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
     *
     * @return bool
     *
     * @throws DatabaseException
     */
    public function update($idProduct, array $data)
    {
        return $this->dbTools->update(
            self::$tableName,
            $data,
            '`id_product` = ' . $idProduct
        );
    }

    /**
     * @param int $idProduct
     *
     * @return bool
     *
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
     * @return string
     */
    public function getDropTableSql()
    {
        return 'DROP TABLE IF EXISTS `' . $this->getPrefixedTableName() . '`;';
    }

    /**
     * @return string
     */
    public function getCreateTableSql()
    {
        return 'CREATE TABLE `' . $this->getPrefixedTableName() . '` (
            `id_product` int(11) NOT NULL PRIMARY KEY,
            `is_adult` tinyint(1) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
    }
}
