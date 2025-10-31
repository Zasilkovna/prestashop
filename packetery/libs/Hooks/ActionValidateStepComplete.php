<?php

namespace Packetery\Hooks;

use CartCore;
use Packetery;
use Packetery\Address\AddressTools;
use Packetery\ApiCarrier\ApiCarrierRepository;
use Packetery\Carrier\CarrierRepository;
use Packetery\Exceptions\DatabaseException;
use Packetery\Order\OrderRepository;
use Packetery\PickupPointValidate\PickupPointValidator;
use Packetery\Tools\ConfigHelper;
use PrestaShopLogger;

class ActionValidateStepComplete
{
    /** @var CarrierRepository */
    private $carrierRepository;

    /** @var OrderRepository */
    private $orderRepository;

    /** @var ApiCarrierRepository */
    private $apiCarrierRepository;

    /** @var PickupPointValidator */
    private $pickupPointValidator;

    /** @var Packetery */
    private $module;

    public function __construct(
        CarrierRepository $carrierRepository,
        OrderRepository $orderRepository,
        ApiCarrierRepository $apiCarrierRepository,
        PickupPointValidator $pickupPointValidator,
        Packetery $module
    ) {
        $this->carrierRepository = $carrierRepository;
        $this->orderRepository = $orderRepository;
        $this->apiCarrierRepository = $apiCarrierRepository;
        $this->pickupPointValidator = $pickupPointValidator;
        $this->module = $module;
    }

    /**
     * @throws DatabaseException
     */
    public function execute(array &$params): ?string
    {
        if (empty($params['cart'])) {
            PrestaShopLogger::addLog('Cart is not present in hook parameters.', 3, null, null, null, true);
            $params['completed'] = false;
            return $this->module->l('Order validation failed, shop owner can find more information in log.', 'ordervalidatestepcomplete');
        }

        /** @var CartCore $cart */
        $cart = $params['cart'];
        $packeteryCarrier = $this->carrierRepository->getPacketeryCarrierById((int)$cart->id_carrier);

        $orderData = $this->orderRepository->getByCart((int)$cart->id);

        $isExternalPickupPointCarrier = $this->apiCarrierRepository->isExternalPickupPointCarrier((int)$packeteryCarrier['id_branch']);

        if ($isExternalPickupPointCarrier === true && empty($orderData['id_branch'])) {
            $params['completed'] = false;
            return $this->module->l('Please select pickup point.', 'ordervalidatestepcomplete');
        }

        $isApiWidgetValidationModeEnabled = ConfigHelper::isApiWidgetValidationModeEnabled();

        if (
            $isApiWidgetValidationModeEnabled === true &&
            $this->isPickupPointCarrier($isExternalPickupPointCarrier, (string)$packeteryCarrier['id_branch']) &&
            is_array($orderData) === true &&
            $orderData !== [] &&
            (bool)$orderData['is_ad'] === false
        ) {
            $pickupPointValidationResponse = $this->pickupPointValidator->validate(
                $this->pickupPointValidator->createPickupPointValidateRequest($orderData, $cart, $packeteryCarrier)
            );

            if ($pickupPointValidationResponse->isValid() === false) {
                $params['completed'] = false;

                return $this->module->l('The selected Packeta pickup point could not be validated. Please select another.', 'ordervalidatestepcomplete');
            }
        }

        if ($packeteryCarrier['address_validation'] !== 'required') {
            $params['completed'] = true;
            return null;
        }

        if (!$orderData || !AddressTools::hasValidatedAddress($orderData)) {
            $params['completed'] = false;
            return $this->module->l('Please use widget to validate address.', 'ordervalidatestepcomplete');
        }

        $params['completed'] = true;

        return null;
    }

    public function isPickupPointCarrier(bool $isExternalPickupPointCarrier, string $idBranch): bool
    {
        return ($isExternalPickupPointCarrier === true || $idBranch === Packetery::PP_ALL || $idBranch === Packetery::ZPOINT);
    }
}
