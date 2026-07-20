<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Returns;

use Packetery\Module\Helper;
use Packetery\Returns\CustomerReturnSectionProvider;
use Packetery\Returns\ReturnCreationGate;
use Packetery\Returns\ReturnEntity;
use Packetery\Returns\ReturnRepository;
use PHPUnit\Framework\TestCase;

class CustomerReturnSectionProviderTest extends TestCase
{
    private const ORDER_ID = 64;
    private const CLAIM_ID = '2850999578';

    public function testBuildReturnsConfirmationWhenActiveReturnExists(): void
    {
        $returnRepository = $this->createStub(ReturnRepository::class);
        $returnRepository->method('getActiveByOrderId')->willReturn(
            new ReturnEntity(1, self::ORDER_ID, self::CLAIM_ID, ReturnEntity::STATUS_CREATED, ReturnEntity::SOURCE_CUSTOMER, '2026-07-09 12:00:00')
        );

        // an existing active return short-circuits the eligibility gate
        $creationGate = $this->createMock(ReturnCreationGate::class);
        $creationGate->expects($this->never())->method('canCreate');

        $data = (new CustomerReturnSectionProvider($returnRepository, $creationGate))->build(self::ORDER_ID);

        $this->assertSame(CustomerReturnSectionProvider::STATE_CREATED, $data['returnState']);
        $this->assertSame(self::CLAIM_ID, $data['returnClaimId']);
        $this->assertSame(Helper::getTrackingUrl(self::CLAIM_ID), $data['returnTrackingUrl']);
    }

    public function testBuildReturnsFormWhenEligibleAndNoActiveReturn(): void
    {
        $returnRepository = $this->createStub(ReturnRepository::class);
        $returnRepository->method('getActiveByOrderId')->willReturn(null);

        $creationGate = $this->createStub(ReturnCreationGate::class);
        $creationGate->method('canCreate')->willReturn(true);

        $data = (new CustomerReturnSectionProvider($returnRepository, $creationGate))->build(self::ORDER_ID);

        $this->assertSame(CustomerReturnSectionProvider::STATE_FORM, $data['returnState']);
        $this->assertSame('', $data['returnClaimId']);
    }

    public function testBuildReturnsNoneWhenNotEligible(): void
    {
        $returnRepository = $this->createStub(ReturnRepository::class);
        $returnRepository->method('getActiveByOrderId')->willReturn(null);

        $creationGate = $this->createStub(ReturnCreationGate::class);
        $creationGate->method('canCreate')->willReturn(false);

        $data = (new CustomerReturnSectionProvider($returnRepository, $creationGate))->build(self::ORDER_ID);

        $this->assertSame(CustomerReturnSectionProvider::STATE_NONE, $data['returnState']);
    }

    public function testBuildIncludesReturnHistoryNewestFirst(): void
    {
        $returnRepository = $this->createStub(ReturnRepository::class);
        $returnRepository->method('getActiveByOrderId')->willReturn(
            new ReturnEntity(2, self::ORDER_ID, self::CLAIM_ID, ReturnEntity::STATUS_CREATED, ReturnEntity::SOURCE_CUSTOMER, '2026-07-10 10:00:00')
        );
        // getByOrderId returns oldest-first; the provider reverses it to newest-first for display
        $returnRepository->method('getByOrderId')->willReturn([
            new ReturnEntity(1, self::ORDER_ID, '', ReturnEntity::STATUS_REJECTED, ReturnEntity::SOURCE_CUSTOMER, '2026-07-09 08:00:00'),
            new ReturnEntity(2, self::ORDER_ID, self::CLAIM_ID, ReturnEntity::STATUS_CREATED, ReturnEntity::SOURCE_CUSTOMER, '2026-07-10 10:00:00'),
        ]);

        $data = (new CustomerReturnSectionProvider($returnRepository, $this->createStub(ReturnCreationGate::class)))->build(self::ORDER_ID);

        $this->assertCount(2, $data['returnHistory']);
        $this->assertSame(ReturnEntity::STATUS_CREATED, $data['returnHistory'][0]['status']);
        $this->assertSame(self::CLAIM_ID, $data['returnHistory'][0]['claimId']);
        $this->assertNotSame('', $data['returnHistory'][0]['trackingUrl']);
        $this->assertSame(ReturnEntity::STATUS_REJECTED, $data['returnHistory'][1]['status']);
        $this->assertSame('', $data['returnHistory'][1]['trackingUrl']);
    }

    public function testBuildReturnsPendingWhenReturnAwaitsApproval(): void
    {
        $returnRepository = $this->createStub(ReturnRepository::class);
        $returnRepository->method('getActiveByOrderId')->willReturn(null);
        // a pending return (no claim id yet) occupies the slot -> the form must stay hidden
        $returnRepository->method('getPendingByOrderId')->willReturn(
            new ReturnEntity(2, self::ORDER_ID, '', ReturnEntity::STATUS_PENDING, ReturnEntity::SOURCE_CUSTOMER, '2026-07-10 09:00:00')
        );

        // a pending return short-circuits the eligibility gate, just like an active one
        $creationGate = $this->createMock(ReturnCreationGate::class);
        $creationGate->expects($this->never())->method('canCreate');

        $data = (new CustomerReturnSectionProvider($returnRepository, $creationGate))->build(self::ORDER_ID);

        $this->assertSame(CustomerReturnSectionProvider::STATE_PENDING, $data['returnState']);
        $this->assertSame('', $data['returnClaimId']);
    }
}
