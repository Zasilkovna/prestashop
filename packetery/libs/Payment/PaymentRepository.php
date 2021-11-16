<?php

namespace Packetery\Payment;

use Db;
use PrestaShopDatabaseException;
use PrestaShopException;

class PaymentRepository
{
    /** @var Db $db */
    private $db;

    /**
     * PaymentRepository constructor.
     * @param Db $db
     */
    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $moduleName
     * @return bool
     * @throws PrestaShopException
     */
    public function existsByModuleName($moduleName)
    {
        $result = $this->db->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . 'packetery_payment`
            WHERE `module_name` = "' . $this->db->escape($moduleName) . '"'
        );

        return ((int)$result === 1);
    }

    /**
     * @param string $paymentModuleName
     * @return bool
     * @throws PrestaShopException
     */
    public function isCod($paymentModuleName)
    {
        $isCod = $this->db->getValue(
            'SELECT `is_cod` FROM `' . _DB_PREFIX_ . 'packetery_payment`
            WHERE `module_name` = "' . $this->db->escape($paymentModuleName) . '"'
        );

        return ((int)$isCod === 1);
    }

    /**
     * @param int $value
     * @param string $moduleName
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function setCod($value, $moduleName)
    {
        return $this->db->execute('UPDATE `' . _DB_PREFIX_ . 'packetery_payment` 
            SET `is_cod` = ' . $value . ' 
            WHERE `module_name` = "' . $this->db->escape($moduleName) . '"');
    }

    /**
     * @param int $isCod
     * @param string $moduleName
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function insert($isCod, $moduleName)
    {
        return $this->db->execute('INSERT INTO `' . _DB_PREFIX_ . 'packetery_payment` 
            SET `is_cod` = ' . $isCod . ', 
            `module_name` = "' . $this->db->escape($moduleName) . '"');
    }

    /**
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getAll()
    {
        return $this->db->executeS('SELECT DISTINCT `module_name`, `is_cod` FROM `' . _DB_PREFIX_ . 'packetery_payment`');
    }
}
