<?php

declare(strict_types=1);

namespace Packetery\PickupPointValidate;

use CartCore;
use Exception;
use Packetery;
use Packetery\Address\AddressTools;
use Packetery\Carrier\CarrierTools;
use Packetery\Carrier\CarrierVendors;
use Packetery\Cart\CartService;
use Packetery\Log\LogRepository;
use Packetery\Request\PickupPointValidateRequest;
use Packetery\Response\PickupPointValidateResponse;
use Packetery\Tools\ConfigHelper;
use Packetery\Tools\Exception\InvalidApiKeyException;
use Packetery\Tools\HttpClientWrapper;

class PickupPointValidator
{
    /** @var LogRepository */
    private $logRepository;

    /** @var ConfigHelper */
    private $configHelper;

    /** @var Packetery */
    private $module;

    /** @var CartService */
    private $cartService;

    public function __construct(
        ConfigHelper $configHelper,
        LogRepository $logRepository,
        Packetery $module,
        CartRepository $cartRepository
    ) {
        $this->logRepository = $logRepository;
        $this->configHelper = $configHelper;
        $this->module = $module;
        $this->cartRepository = $cartRepository;
    }

    public function validate(PickupPointValidateRequest $request): PickupPointValidateResponse
    {
        try {
            $apiKey = $this->configHelper->getValidApiKey();
            $pickupPointValidate = PickupPointValidate::createWithValidApiKey($apiKey, $this->httpClient);
        } catch (InvalidApiKeyException $exception) {
            $record = [
                'errorMessage' => $this->module->l('API credentials are not set corretly.', 'pickuptointvalidate'),
            ];

            $this->logRepository->insertRow(logRepository::ACTION_PICKUP_POINT_VALIDATE, $record, 'error');

            return new PickupPointValidateResponse(true, []);
        }

        try {
            $pickupPointValidateResponse = $pickupPointValidate->validate($request);
            $record = [
                'request' => $request->getSubmittableData(),
                'errors' => $pickupPointValidateResponse->getErrors(),
            ];
            if ($pickupPointValidateResponse->isValid()) {
                $this->logRepository->insertRow(logRepository::ACTION_PICKUP_POINT_VALIDATE, $record, 'success');
            } else {
                $this->logRepository->insertRow(logRepository::ACTION_PICKUP_POINT_VALIDATE, $record, 'error');
            }
            return $pickupPointValidateResponse;
        } catch (Exception $exception) {
            $record = [
                'errorMessage' => $exception->getMessage(),
                'request' => $request->getSubmittableData(),
            ];

            $this->logRepository->insertRow(logRepository::ACTION_PICKUP_POINT_VALIDATE, $record, 'error');

            return new PickupPointValidateResponse(true, []);
        }
    }

    public function createPickupPointValidateRequest(
        array $orderData,
        CartCore $cart,
        array $packeteryCarrier
    ): PickupPointValidateRequest {
        $customerCountry = AddressTools::getCountryFromCart($cart);
        $externalCarrierId = CarrierTools::findExternalCarrierId($orderData);
        $externalCarrierId = $externalCarrierId !== null ? (string)$externalCarrierId : null;
        $resolvedCarrierId = $externalCarrierId ?? CarrierVendors::INTERNAL_PICKUP_POINT_CARRIER;

        $allowedVendors = null;
        if ($packeteryCarrier['allowed_vendors'] !== null) {
            $allowedVendors = json_decode($packeteryCarrier['allowed_vendors']);
        }

        $vendors = null;
        if ($resolvedCarrierId === CarrierVendors::INTERNAL_PICKUP_POINT_CARRIER && $allowedVendors !== null) {
            $vendors = [];
            foreach ($allowedVendors as $country => $vendorGroups) {
                foreach ($vendorGroups as $vendorGroup) {
                    $vendorOptions = [
                        'carrierId' => null,
                        'country' => $country,
                    ];
                    if ($vendorGroup !== CarrierVendors::VENDOR_GROUP_ZPOINT) {
                        $vendorOptions['group'] = $vendorGroup;
                    }
                    $vendors[] = $vendorOptions;
                }
            }
        }

        return new PickupPointValidateRequest(
            new ValidatedOptions(
                $customerCountry,
                $resolvedCarrierId,
                null,
                null,
                null,
                $this->cartService->isAgeVerificationRequired($cart),
                null,
                null,
                null,
                null,
                null,
                $vendors
            ),
            new ValidatedPoint(
                $externalCarrierId === null ? $orderData['id_branch'] : null,
                $externalCarrierId,
                $externalCarrierId !== null ? $orderData['carrier_pickup_point'] : null
            )
        );
    }
}
