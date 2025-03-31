<?php

namespace Packetery\Module;

use Context;
use Packetery;
use Packetery\Exceptions\SenderGetReturnRoutingException;
use Packetery\Log\LogRepository;
use Validate;
use Packetery\Tools\ConfigHelper;

class Options
{
    const API_PASSWORD_LENGTH = 32;

    /** @var Packetery */
    private $module;

    /** @var SoapApi */
    private $soapApi;

    /** @var LogRepository */
    private $logRepository;

    public function __construct(
        Packetery $module,
        SoapApi $soapApi,
        LogRepository $logRepository
    )
    {
        $this->module = $module;
        $this->soapApi = $soapApi;
        $this->logRepository = $logRepository;
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
                    return $this->module->getTranslator()->trans('Api password must be 32 characters long.', [], 'Modules.Packetery.Options');
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
                    $this->logRepository->insertRow(
                        LogRepository::ACTION_SENDER_VALIDATION,
                        [
                            'value' => $value,
                        ],
                        LogRepository::STATUS_SUCCESS
                    );

                    return false;
                } catch (SenderGetReturnRoutingException $e) {
                    if ($e->senderNotExists === true) {
                        return $this->module->getTranslator()->trans('Provided sender indication does not exist.', [], 'Modules.Packetery.Options');
                    }

                    $this->logRepository->insertRow(
                        LogRepository::ACTION_SENDER_VALIDATION,
                        [
                            'value' => $value,
                            'senderNotExists' => $e->senderNotExists,
                        ],
                        LogRepository::STATUS_ERROR
                    );

                    return sprintf(
                        '%s: %s',
                        $this->module->getTranslator()->trans('Sender indication validation failed', [], 'Modules.Packetery.Options'),
                        $e->getMessage()
                    );
                }
            case 'PACKETERY_DEFAULT_PACKAGE_PRICE':
                if ($this->isNonNegative($value)) {
                    return false;
                }
                return $this->module->getTranslator()->trans('Please insert default package price', [], 'Modules.Packetery.Options');
            case 'PACKETERY_DEFAULT_PACKAGE_WEIGHT':
                if ($this->isNonNegative($value)) {
                    return false;
                }
                return $this->module->getTranslator()->trans('Please insert default package weight in kg', [], 'Modules.Packetery.Options');
            case 'PACKETERY_DEFAULT_PACKAGING_WEIGHT':
                if ($this->isNonNegative($value)) {
                    return false;
                }
                return $this->module->getTranslator()->trans('Please insert default packaging weight in kg', [], 'Modules.Packetery.Options');
            case 'PACKETERY_PACKET_STATUS_TRACKING_MAX_PROCESSED_ORDERS':
                if ($this->isNonNegative($value)) {
                    return false;
                }
                return $this->module->getTranslator()->trans('Insert maximum number of orders that will be processed', [], 'Modules.Packetery.Options');
            case 'PACKETERY_PACKET_STATUS_TRACKING_MAX_ORDER_AGE_DAYS':
                if ($this->isNonNegative($value)) {
                    return false;
                }
                return $this->module->getTranslator()->trans('Insert maximum order age in days', [], 'Modules.Packetery.Options');
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
