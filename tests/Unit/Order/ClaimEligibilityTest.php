<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Order;

use Packetery\Order\ClaimEligibility;
use Packetery\PacketTracking\PacketStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ClaimEligibilityTest extends TestCase
{
    #[DataProvider('createClaimProvider')]
    public function testCreateClaimAvailability(
        ?string $trackingNumber,
        ?int $lastStatusCode,
        ?string $deliveryCountryIso,
        ?string $existingClaimId,
        bool $expected
    ): void {
        $eligibility = new ClaimEligibility();

        $this->assertSame(
            $expected,
            $eligibility->canCreateClaim(
                $trackingNumber,
                $lastStatusCode,
                $deliveryCountryIso,
                $existingClaimId
            )
        );
    }

    /**
     * @return array<string, array{?string, ?int, ?string, ?string, bool}>
     */
    public static function createClaimProvider(): array
    {
        return [
            'offered: delivered to a pickup-point country, no claim yet' => [
                'Z123',
                PacketStatus::DELIVERED,
                'CZ',
                null,
                true,
            ],
            'offered: pickup-point country matched case-insensitively' => [
                'Z123',
                PacketStatus::DELIVERED,
                'sk',
                null,
                true,
            ],
            'offered: delivered to HU' => [
                'Z123',
                PacketStatus::DELIVERED,
                'HU',
                null,
                true,
            ],
            'offered: delivered to RO' => [
                'Z123',
                PacketStatus::DELIVERED,
                'RO',
                null,
                true,
            ],
            'offered: empty stored claim treated as none' => [
                'Z123',
                PacketStatus::DELIVERED,
                'CZ',
                '',
                true,
            ],
            'hidden: parcel not submitted (no tracking number)' => [
                null,
                PacketStatus::DELIVERED,
                'CZ',
                null,
                false,
            ],
            'hidden: parcel not submitted (empty tracking number)' => [
                '',
                PacketStatus::DELIVERED,
                'CZ',
                null,
                false,
            ],
            'hidden: parcel not yet delivered' => [
                'Z123',
                PacketStatus::RECEIVED_DATA,
                'CZ',
                null,
                false,
            ],
            'hidden: packet status unknown' => [
                'Z123',
                null,
                'CZ',
                null,
                false,
            ],
            'hidden: delivery country has no pickup points' => [
                'Z123',
                PacketStatus::DELIVERED,
                'DE',
                null,
                false,
            ],
            'hidden: delivery country missing' => [
                'Z123',
                PacketStatus::DELIVERED,
                null,
                null,
                false,
            ],
            'hidden: a claim was already created' => [
                'Z123',
                PacketStatus::DELIVERED,
                'CZ',
                'Z999',
                false,
            ],
        ];
    }

    #[DataProvider('cancelClaimProvider')]
    public function testCancelClaimAvailability(?string $existingClaimId, bool $expected): void
    {
        $eligibility = new ClaimEligibility();

        $this->assertSame($expected, $eligibility->canCancelClaim($existingClaimId));
    }

    /**
     * @return array<string, array{?string, bool}>
     */
    public static function cancelClaimProvider(): array
    {
        return [
            'cancel offered: a claim exists' => ['Z999', true],
            'cancel hidden: no claim (null)' => [null, false],
            'cancel hidden: no claim (empty string)' => ['', false],
        ];
    }
}
