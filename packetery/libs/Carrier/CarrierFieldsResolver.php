<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Carrier;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\ApiCarrier\ApiCarrierRepository;

class CarrierFieldsResolver
{
    private const PICKUP_POINT_TYPE_INTERNAL = 'internal';
    private const PICKUP_POINT_TYPE_EXTERNAL = 'external';

    /** @var ApiCarrierRepository */
    private $apiCarrierRepository;

    /** @var CarrierTools */
    private $carrierTools;

    /** @var CarrierVendors */
    private $carrierVendors;

    public function __construct(
        ApiCarrierRepository $apiCarrierRepository,
        CarrierTools $carrierTools,
        CarrierVendors $carrierVendors
    ) {
        $this->apiCarrierRepository = $apiCarrierRepository;
        $this->carrierTools = $carrierTools;
        $this->carrierVendors = $carrierVendors;
    }

    /**
     * @param array{is_pickup_points: mixed} $apiCarrier
     * @param string $idBranch
     */
    public function getPickupPointType(array $apiCarrier, $idBranch): ?string
    {
        $isPickupPoints = (bool) $apiCarrier['is_pickup_points'];

        $pickupPointType = null;
        if ($isPickupPoints && $idBranch === \Packetery::ZPOINT) {
            $pickupPointType = self::PICKUP_POINT_TYPE_INTERNAL;
        } elseif ($isPickupPoints) {
            $pickupPointType = self::PICKUP_POINT_TYPE_EXTERNAL;
        }

        return $pickupPointType;
    }

    public function resolveAddressValidation(
        string $country,
        bool $isPickupPoints,
        ?string $addressValidation
    ): ?string {
        if ($isPickupPoints === true) {
            return null;
        }

        if ($this->supportsAddressValidation($country)) {
            return null;
        }

        return $addressValidation;
    }

    public function supportsAddressValidation(string $country): bool
    {
        return in_array(strtoupper($country), CarrierRepository::ADDRESS_VALIDATION_COUNTRIES, true);
    }

    /**
     * @param array|false|null $apiCarrier
     *
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function getPossibleVendors(array $carrierData, int $carrierId, $apiCarrier = null): array
    {
        if (!isset($carrierData['id_branch'])) {
            return [];
        }
        if ($apiCarrier === null) {
            $apiCarrier = $this->apiCarrierRepository->getById($carrierData['id_branch']);
        }
        if (!is_array($apiCarrier)) {
            return [];
        }

        if ($apiCarrier['id'] === \Packetery::PP_ALL || $apiCarrier['id'] === \Packetery::ZPOINT) {
            $countries = $this->carrierTools->getCountries($carrierId, 'iso_code');
        } else {
            $countries = [$apiCarrier['country']];
        }

        return $this->carrierVendors->getVendorsByCountries($countries);
    }

    /**
     * @param array|false $apiCarrier
     *
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function getDefaultAllowedVendors(array $carrierData, int $carrierId, $apiCarrier): ?string
    {
        if (!isset($carrierData['id_branch'])) {
            return null;
        }

        $allowedVendorsJson = null;
        if ($carrierData['id_branch'] === \Packetery::ZPOINT || $carrierData['id_branch'] === \Packetery::PP_ALL) {
            $possibleVendors = $this->getPossibleVendors($carrierData, $carrierId, $apiCarrier);
            $allowedVendorsArray = [];

            // Allow all by default
            foreach ($possibleVendors as $country => $vendors) {
                $allowedVendorsArray[$country] = array_column($vendors, 'group');
            }
            $allowedVendorsJson = (string) json_encode($allowedVendorsArray);
        }

        return $allowedVendorsJson;
    }
}
