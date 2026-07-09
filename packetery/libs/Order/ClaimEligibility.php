<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Order;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Carrier\CarrierTools;
use Packetery\PacketTracking\PacketStatus;

/**
 * Pure claim-availability logic (no PrestaShop objects),
 * used by both the grid icon and the server-side check.
 */
class ClaimEligibility
{
    public function canCreateClaim(
        ?string $trackingNumber,
        ?int $lastStatusCode,
        ?string $deliveryCountryIso,
        ?string $existingClaimId
    ): bool {
        if ($trackingNumber === null || $trackingNumber === '') {
            return false;
        }
        if ($lastStatusCode !== PacketStatus::DELIVERED) {
            return false;
        }
        if (
            $deliveryCountryIso === null
            || !in_array(strtoupper($deliveryCountryIso), CarrierTools::COUNTRIES_WITH_INTERNAL_PICKUP_POINTS, true)
        ) {
            return false;
        }

        return $existingClaimId === null || $existingClaimId === '';
    }

    public function canCancelClaim(?string $existingClaimId): bool
    {
        return $existingClaimId !== null && $existingClaimId !== '';
    }
}
