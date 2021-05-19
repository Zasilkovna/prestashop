<?php

namespace Packetery\Payment;

use \Db;

class PaymentModel
{
    /**
     * @param string $paymentModuleName
     * @return bool
     */
    public function isCod($paymentModuleName)
    {
        $isCod = Db::getInstance()->getValue(
            'SELECT `is_cod` FROM `' . _DB_PREFIX_ . 'packetery_payment` WHERE module_name="' . pSQL($paymentModuleName) . '"'
        );

        return ((int)$isCod === 1);
    }
}
