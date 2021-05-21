<?php

namespace Packetery\Payment;

use \Db;

class PaymentRepo
{
    private $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $paymentModuleName
     * @return bool
     */
    public function isCod($paymentModuleName)
    {
        $isCod = $this->db->getValue(
            'SELECT `is_cod` FROM `' . _DB_PREFIX_ . 'packetery_payment` WHERE module_name="' . $this->db->escape($paymentModuleName) . '"'
        );

        return ((int)$isCod === 1);
    }
}
