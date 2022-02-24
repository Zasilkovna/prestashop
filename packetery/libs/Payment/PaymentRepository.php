<?php

namespace Packetery\Payment;

use Db;
use Module;
use Packetery\Exceptions\DatabaseException;
use Packetery\Order\OrderRepository;
use Packetery\Tools\DbTools;
use PaymentModule;
use Tools;

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
     * @param string $order_currency_iso
     * @param string $branch_currency_iso
     * @param float|int $total
     * @return float|int
     * @throws DatabaseException
     */
    public function getRateTotal($order_currency_iso, $branch_currency_iso, $total)
    {
        $cnb_rates = null;
        $conversion_rate_order = $this->orderRepository->getConversionRate($order_currency_iso);
        $conversion_rate_branch = $this->orderRepository->getConversionRate($branch_currency_iso);

        if ($conversion_rate_branch) {
            $conversion_rate = $conversion_rate_branch / $conversion_rate_order;
            $total = round($conversion_rate * $total, 2);
        } else {
            if (!$cnb_rates) {
                $data = @Tools::file_get_contents(
                    'http://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt'
                );
                if ($data) {
                    $cnb_rates = array();
                    foreach (array_slice(explode("\n", $data), 2) as $rate) {
                        $rate = explode('|', $rate);
                        if (!empty($rate[3])) {
                            $cnb_rates[$rate[3]] = (float)preg_replace(
                                '/[^0-9.]*/',
                                '',
                                str_replace(',', '.', $rate[4])
                            );
                        }
                    }
                    $cnb_rates['CZK'] = 1;
                }
            }
            if (isset($cnb_rates[$order_currency_iso]) && ($cnb_rates)) {
                $total = round($total * $cnb_rates[$order_currency_iso] / $cnb_rates[$branch_currency_iso], 2);
            } else {
                return 0;
            }
        }
        return $total;
    }

    /**
     * Get list of payments for configuration
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
                'module_name' => $installedPaymentModule['name']
            ];
        }
        return $payments;
    }

}
