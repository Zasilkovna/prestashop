<?php

/**
 * 2017 Zlab Solutions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
 *  @copyright Since 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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
