<?php

namespace Packetery\Module;

use Packetery;
use Packetery\Exceptions\SenderGetReturnRoutingException;
use Validate;
use Packetery\Tools\ConfigHelper;

class Options
{
    const API_PASSWORD_LENGTH = 32;

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
     * @param string $id from POST
     * @param string $value from POST
     * @return false|string false on success, error message on failure
     * @throws \Packetery\Exceptions\ApiClientException
     * @throws \ReflectionException
     */
    public function validate($id, $value)
    {
        switch ($id) {
            case 'PACKETERY_APIPASS':
                if (\Tools::strlen($value) !== self::API_PASSWORD_LENGTH) {
                    return $this->module->l('Api password must be 32 characters long.', 'options');
                }

                return false;
            case 'PACKETERY_ESHOP_ID':
                $configHelper = $this->module->diContainer->get(\Packetery\Tools\ConfigHelper::class);
                if (!$configHelper->getApiPass()) {
                    // Error for PACKETERY_APIPASS is enough.
                    return false;
                }
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
                if ($this->isNonNegative($value)) {
                    return false;
                }
                return $this->module->l('Please insert default package price', 'options');
            case 'PACKETERY_DEFAULT_PACKAGE_WEIGHT':
                if ($this->isNonNegative($value)) {
                    return false;
                }
                return $this->module->l('Please insert default package weight in kg', 'options');
            case 'PACKETERY_DEFAULT_PACKAGING_WEIGHT':
                if ($this->isNonNegative($value)) {
                    return false;
                }
                return $this->module->l('Please insert default packaging weight in kg', 'options');
            default:
                return false;
        }
    }

    /**
     * @param string $option
     * @param string $value
     * @return string
     */
    public function formatOption($option, $value)
    {
        switch ($option) {
            case 'PACKETERY_DEFAULT_PACKAGE_PRICE':
            case 'PACKETERY_DEFAULT_PACKAGE_WEIGHT':
            case 'PACKETERY_DEFAULT_PACKAGING_WEIGHT':
                return str_replace([',', ' '], ['.', ''], $value);
            default:
                return $value;
        }
    }

    /**
     * @param string $value
     * @return bool
     */
    public function isNonNegative($value)
    {
        return (Validate::isUnsignedInt($value) || (Validate::isFloat($value) && $value >= 0));
    }

}
