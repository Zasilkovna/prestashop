<?php

namespace Packetery\Module;

use Packetery;
use Packetery\Exceptions\DownloadException;
use Packetery\Exceptions\SenderGetReturnRoutingException;
use ReflectionException;
use Tools;
use Validate;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Message\Response;
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
                if (!$this->isApiKeyValid($value)) {
                    return $this->module->l('Api password is wrong.', 'options');
                }

                return false;
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
     * @param string $apiKeyPass
     * @return bool
     */
    public function isApiKeyValid($apiKeyPass)
    {
        if (\Tools::strlen($apiKeyPass) !== 32) {
            return false;
        }
        $apiKey = ConfigHelper::getApiKeyFromApiPass($apiKeyPass);

        $url = sprintf(self::PACKETA_API_KEY_TEST_URI, $apiKey);

        if (class_exists('GuzzleHttp\Client')) {
            $client = new Client();
            try {
                /** @var Response $result */
                $result = $client->get($url);
            } catch (TransferException $exception) {
                throw new DownloadException($exception->getMessage());
            }
            $body = $result->getBody();

            return (isset($body) && $body->getContents() == 1);
        }

        return (\Tools::file_get_contents($test, false, null, 30, true) == 1);
    }

}
