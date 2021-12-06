<?php

namespace Packetery\Payment;

use Db;
use Packetery\Exceptions\DatabaseException;
use Packetery\Tools\DbTools;

class PaymentRepository
{
    /** @var Db $db */
    private $db;

    /** @var DbTools */
    private $dbTools;

    /**
     * PaymentRepository constructor.
     * @param Db $db
     * @param DbTools $dbTools
     */
    public function __construct(Db $db, DbTools $dbTools)
    {
        $this->db = $db;
        $this->dbTools = $dbTools;
    }

    /**
     * @param string $moduleName
     * @return bool
     * @throws DatabaseException
     */
    public function existsByModuleName($moduleName)
    {
        $result = $this->dbTools->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . 'packetery_payment`
            WHERE `module_name` = "' . $this->db->escape($moduleName) . '"'
        );

        return ((int)$result === 1);
    }

    /**
     * @param string $paymentModuleName
     * @return bool
     * @throws DatabaseException
     */
    public function isCod($paymentModuleName)
    {
        $isCod = $this->dbTools->getValue(
            'SELECT `is_cod` FROM `' . _DB_PREFIX_ . 'packetery_payment`
            WHERE `module_name` = "' . $this->db->escape($paymentModuleName) . '"'
        );

        return ((int)$isCod === 1);
    }

    /**
     * @param int $value
     * @param string $moduleName
     * @return bool
     * @throws DatabaseException
     */
    public function setCod($value, $moduleName)
    {
        $value = (int)$value;
        return $this->dbTools->update('packetery_payment', ['is_cod' => $value], '`module_name` = "' . $this->db->escape($moduleName) . '"');
    }

    /**
     * @param int $isCod
     * @param string $moduleName
     * @return bool
     * @throws DatabaseException
     */
    public function insert($isCod, $moduleName)
    {
        $isCod = (int)$isCod;
        return $this->dbTools->insert(
            'packetery_payment',
            [
                'is_cod' => $isCod,
                'module_name' => $this->db->escape($moduleName),
            ]
        );
    }

    /**
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     * @throws DatabaseException
     */
    public function getAll()
    {
        return $this->dbTools->getRows('SELECT DISTINCT `module_name`, `is_cod` FROM `' . _DB_PREFIX_ . 'packetery_payment`');
    }

    /**
     * @return bool
     * @throws DatabaseException
     */
    public function clearCod()
    {
        return $this->dbTools->update('packetery_payment', ['is_cod' => false]);
    }
}
