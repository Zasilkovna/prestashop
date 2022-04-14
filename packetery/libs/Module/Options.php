<?php

namespace Packetery\Module;

use Packetery;
use Packetery\Exceptions\SenderGetReturnRoutingException;
use ReflectionException;
use Tools;
use Validate;

class Options
{
    /** @var Packetery */
    private $module;
    /** @var SoapApi */
    private $soapApi;

    public function __construct(Packetery $module, SoapApi $soapApi)
    {
        $this->module = $module;
        $this->soapApi = $soapApi;
    }

    /**
     * @param Packetery $module
     * @param string $id from POST
     * @param string $value from POST
     * @return false|string false on success, error message on failure
     * @throws ReflectionException
     */
    public function validate($id, $value)
    {
        switch ($id) {
            case 'PACKETERY_APIPASS':
                if (Validate::isString($value)) {
                    if (Tools::strlen($value) !== 32) {
                        return $this->module->l('Api password is wrong.', 'options');
                    }
                    return false;
                }
                return $this->module->l('Api password must be string', 'options');
            case 'PACKETERY_ESHOP_ID':
                try {
                    $this->soapApi->senderGetReturnRouting($value);
                    return false;
                } catch (SenderGetReturnRoutingException $e) {
                    if ($e->senderNotExists === true) {
                        return $this->module->l('Provided sender indication does not exist.', 'options');
                    }
                    return sprintf(
                        '%s: %s',
                        $this->module->l('Sender indication validation failed', 'options'),
                        $e->getMessage()
                    );
                }
            case 'PACKETERY_DEFAULT_PACKAGE_PRICE':
                if (Validate::isUnsignedInt($value) || $value === 0 || (Validate::isFloat($value) && $value >= 0)) {
                    return false;
                }
                return $this->module->l('Please insert default package price', 'options');
            default:
                return false;
        }
    }

}
