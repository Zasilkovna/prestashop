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

class CarrierFieldsResolver
{
    private const PICKUP_POINT_TYPE_INTERNAL = 'internal';
    private const PICKUP_POINT_TYPE_EXTERNAL = 'external';

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
}
