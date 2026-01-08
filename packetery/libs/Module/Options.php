<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Module;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery;
use Packetery\Log\LogRepository;
use Packetery\Module\Exception\IncorrectApiPasswordException;
use Packetery\Module\Exception\SenderNotExistsException;
use Packetery\Tools\ConfigHelper;
use Packetery\Tools\Tools;

class Options
{
    public const API_PASSWORD_LENGTH = 32;

    /** @var \Packetery */
    private $module;

    /** @var SoapApi */
    private $soapApi;

    /** @var LogRepository */
    private $logRepository;

    /** @var ConfigHelper */
    private $configHelper;

    public function __construct(
        \Packetery $module,
        SoapApi $soapApi,
        LogRepository $logRepository,
        ConfigHelper $configHelper
    ) {
        $this->module = $module;
        $this->soapApi = $soapApi;
        $this->logRepository = $logRepository;
        $this->configHelper = $configHelper;
    }

    /**
     * @param string $id from POST
     * @param string $value from POST
     *
     * @return false|string false on success, error message on failure
     *
     * @throws Packetery\Exceptions\ApiClientException
     * @throws \ReflectionException
     */
    public function validate($id, $value)
    {
        switch ($id) {
            case ConfigHelper::KEY_APIPASS:
                if (\Tools::strlen($value) !== self::API_PASSWORD_LENGTH) {
                    return $this->module->l('Api password must be 32 characters long.', 'options');
                }
                try {
                    $this->soapApi->senderGetReturnRouting('', $value);

                    return false;
                } catch (IncorrectApiPasswordException $e) {
                    $this->logRepository->insertRow(
                        LogRepository::ACTION_SENDER_VALIDATION,
                        [
                            'incorrectApiPasswordFaultMessage' => $e->getMessage(),
                        ],
                        LogRepository::STATUS_ERROR
                    );

                    return $this->module->l('Invalid API password.', 'options');
                } catch (SenderNotExistsException $e) {
                    return false;
                }
            case ConfigHelper::KEY_ESHOP_ID:
                $apiPassword = $this->configHelper->getApiPass();
                if (!$apiPassword) {
                    return false;
                }
                try {
                    $this->soapApi->senderGetReturnRouting($value, $apiPassword);
                    $this->logRepository->insertRow(
                        LogRepository::ACTION_SENDER_VALIDATION,
                        [
                            'value' => $value,
                        ],
                        LogRepository::STATUS_SUCCESS
                    );

                    return false;
                } catch (SenderNotExistsException $e) {
                    $this->logRepository->insertRow(
                        LogRepository::ACTION_SENDER_VALIDATION,
                        [
                            'value' => $value,
                            'senderNotExistsMessage' => $e->getMessage(),
                        ],
                        LogRepository::STATUS_ERROR
                    );

                    return $this->module->l('Provided sender indication does not exist.', 'options');
                } catch (IncorrectApiPasswordException $e) {
                    return $this->module->l('The provided sender cannot be verified: Invalid API password.', 'options');
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
            case 'PACKETERY_PACKET_STATUS_TRACKING_MAX_PROCESSED_ORDERS':
                if ($this->isNonNegative($value)) {
                    return false;
                }

                return $this->module->l('Insert maximum number of orders that will be processed', 'options');
            case 'PACKETERY_PACKET_STATUS_TRACKING_MAX_ORDER_AGE_DAYS':
                if ($this->isNonNegative($value)) {
                    return false;
                }

                return $this->module->l('Insert maximum order age in days', 'options');
            default:
                return false;
        }
    }

    /**
     * @param string $option
     * @param string $value
     *
     * @return string
     */
    public function formatOption($option, $value)
    {
        switch ($option) {
            case 'PACKETERY_DEFAULT_PACKAGE_PRICE':
            case 'PACKETERY_DEFAULT_PACKAGE_WEIGHT':
            case 'PACKETERY_DEFAULT_PACKAGING_WEIGHT':
                return Tools::sanitizeFloatValue($value);
            default:
                return $value;
        }
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public function isNonNegative($value)
    {
        return \Validate::isUnsignedInt($value) || (\Validate::isFloat($value) && $value >= 0);
    }
}
