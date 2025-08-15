<?php

namespace Packetery\Module;

use Packetery;
use Packetery\Exceptions\ApiClientException;
use Packetery\Exceptions\DatabaseException;
use Packetery\Payment\PaymentRepository;
use Packetery\Tools\ConfigHelper;
use Packetery\UserPermission\UserPermissionHelper;
use Tools;

class ConfigurationFormService
{
    /** @var Packetery */
    private $module;

    /**
     * @param Packetery $module
     */
    public function __construct($module)
    {
        $this->module = $module;
    }

    /**
     * @param UserPermissionHelper $userPermissionHelper
     * @return array<string, bool|string>
     * @throws ApiClientException
     * @throws \ReflectionException|DatabaseException
     */
    public function handleConfigurationSubmit(UserPermissionHelper $userPermissionHelper)
    {
        $output = '';
        $error = false;

        if (!$userPermissionHelper->hasPermission(UserPermissionHelper::SECTION_CONFIG, UserPermissionHelper::PERMISSION_EDIT)) {
            return [
                'output' => $this->module->displayError('You do not have permission to save configuration.'),
                'error' => true,
            ];
        }

        $confOptions = $this->module->getConfigurationOptions();
        $packeteryOptions = $this->module->diContainer->get(Options::class);

        foreach ($confOptions as $option => $optionConf) {
            $value = (string)Tools::getValue($option);
            $configValue = $packeteryOptions->formatOption($option, $value);
            $errorMessage = $packeteryOptions->validate($option, $configValue);
            if ($errorMessage !== false) {
                $output .= $this->module->displayError($errorMessage);
                $error = true;
            } else {
                ConfigHelper::update($option, $configValue);
            }
        }

        $this->handlePaymentMethodsSubmit();

        return [
            'output' => $output,
            'error' => $error,
        ];
    }

    /**
     * @throws \ReflectionException
     * @throws DatabaseException
     */
    private function handlePaymentMethodsSubmit()
    {
        $paymentRepository = $this->module->diContainer->get(PaymentRepository::class);
        $paymentList = $paymentRepository->getListPayments();

        if ($paymentList) {
            foreach ($paymentList as $payment) {
                if (Tools::getIsset('payment_cod_' . $payment['module_name'])) {
                    $paymentRepository->setOrInsert(1, $payment['module_name']);
                } else {
                    $paymentRepository->setOrInsert(0, $payment['module_name']);
                }
            }
        }
    }
}
