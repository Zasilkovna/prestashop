<?php

namespace Packetery\Payment;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Db;
use Module;
use Packetery\Exceptions\DatabaseException;
use Packetery\Order\OrderRepository;
use Packetery\Tools\DbTools;
use PaymentModule;

class PaymentRepository
{
    /** @var Db $db */
    private $db;

    /** @var DbTools */
    private $dbTools;

    /** @var OrderRepository */
    private $orderRepository;

    /**
     * PaymentRepository constructor.
     *
     * @param Db $db
     * @param DbTools $dbTools
     * @param OrderRepository $orderRepository
     */
    public function __construct(Db $db, DbTools $dbTools, OrderRepository $orderRepository)
    {
        $this->db = $db;
        $this->dbTools = $dbTools;
        $this->orderRepository = $orderRepository;
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
     * @param int $value
     * @param string $moduleName
     * @return bool
     * @throws DatabaseException
     */
    public function setOrInsert($value, $moduleName)
    {
        if ($this->existsByModuleName($moduleName)) {
            return $this->setCod($value, $moduleName);
        }
        return $this->insert($value, $moduleName);
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
     * Converts price from order currency to branch currency
     *
     * @param string $orderCurrencyIso
     * @param string $branchCurrencyIso
     * @param float|int $total
     * @return float|int|null Returns null if rate was not found.
     * @throws DatabaseException
     */
    public function getRateTotal($orderCurrencyIso, $branchCurrencyIso, $total)
    {
        $conversionRateOrder = $this->orderRepository->getConversionRate($orderCurrencyIso);
        $conversionRateBranch = $this->orderRepository->getConversionRate($branchCurrencyIso);

        if ($conversionRateBranch) {
            $conversionRate = $conversionRateBranch / $conversionRateOrder;
            return round($conversionRate * $total, 2);
        }

        return null;
    }

    /**
     * Get list of payments for configuration
     *
     * @return array
     * @throws DatabaseException
     */
    public function getListPayments()
    {
        $installedPaymentModules = PaymentModule::getInstalledPaymentModules();
        $packeteryPaymentConfig = $this->getAll();
        $paymentModules = [];
        if ($packeteryPaymentConfig) {
            $paymentModules = array_column($packeteryPaymentConfig, 'is_cod', 'module_name');
        }

        $payments = [];
        foreach ($installedPaymentModules as $installedPaymentModule) {
            $instance = Module::getInstanceByName($installedPaymentModule['name']);
            if ($instance === false) {
                continue;
            }
            $is_cod = (array_key_exists(
                $installedPaymentModule['name'],
                $paymentModules
            ) ? (int)$paymentModules[$installedPaymentModule['name']] : 0
            );
            $payments[] = [
                'name' => $instance->displayName,
                'is_cod' => $is_cod,
                'module_name' => $installedPaymentModule['name'],
            ];
        }
        return $payments;
    }
}
