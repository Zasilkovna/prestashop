<?php

namespace Packetery\Module;

use Packetery;
use Packetery\Exceptions\SenderGetReturnRoutingException;
use Validate;
use Packetery\Tools\ConfigHelper;

class Options
{
    /** @var Packetery */
    private $module;

    /** @var SoapApi */
    private $soapApi;

    const PACKETA_API_KEY_TEST_URI = 'http://www.zasilkovna.cz/api/%s/test';

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
                if (!$this->isApiPasswordValid($value)) {
                    return $this->module->l('Api password is wrong.', 'options');
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
                if (Validate::isUnsignedInt($value) || $value === 0 || (Validate::isFloat($value) && $value >= 0)) {
                    return false;
                }
                return $this->module->l('Please insert default package price', 'options');
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
                return str_replace([',', ' '], ['.', ''], $value);
            default:
                return $value;
        }
    }

    /**
     * @param string $apiPassword
     * @return bool
     * @throws \ReflectionException
     * @throws \Packetery\Exceptions\ApiClientException
     */
    public function isApiPasswordValid($apiPassword)
    {
        if (\Tools::strlen($apiPassword) !== 32) {
            return false;
        }
        $apiKey = ConfigHelper::getApiKeyFromApiPass($apiPassword);
        $url = sprintf(self::PACKETA_API_KEY_TEST_URI, $apiKey);

        /** @var \Packetery\Module\ApiClientFacade $client */
        $client = $this->module->diContainer->get(ApiClientFacade::class);

        return $client->get($url) === '1';
    }

}
