<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Order;

use Packetery\Exceptions\DatabaseException;
use Packetery\Order\ClaimApiSender;
use Packetery\Order\ClaimFault;
use Packetery\Order\ClaimSubmitter;
use Packetery\Response\CreateClaimResponse;
use Packetery\Returns\ReturnApprovalPolicy;
use Packetery\Returns\ReturnEntity;
use Packetery\Returns\ReturnRepository;
use PHPUnit\Framework\TestCase;

class ClaimSubmitterTest extends TestCase
{
    private const ORDER_ID = 42;
    private const CLAIM_ID = 'Z9012';
    private const CLAIM_PASSWORD = 'secret';

    public function testCustomerReturnNeedingApprovalIsStoredPendingWithoutApiCall(): void
    {
        $apiSender = $this->createMock(ClaimApiSender::class);
        $apiSender->expects($this->never())->method('send');

        // the contact entered in the form is stored on the pending return for later approval
        $returnRepository = $this->createMock(ReturnRepository::class);
        $returnRepository
            ->expects($this->once())
            ->method('insertPending')
            ->with(self::ORDER_ID, ReturnEntity::SOURCE_CUSTOMER, 'buyer@example.com', '777888999')
            ->willReturn(true);
        $returnRepository->expects($this->never())->method('insert');

        $result = $this->submitter($apiSender, $returnRepository, $this->policy(true))
            ->submit(self::ORDER_ID, ReturnEntity::SOURCE_CUSTOMER, 'buyer@example.com', '777888999');

        $this->assertTrue($result->isPending());
    }

    public function testCustomerReturnIsCreatedWhenNoApprovalNeeded(): void
    {
        $apiSender = $this->createStub(ClaimApiSender::class);
        $apiSender->method('send')->willReturn($this->successResponse());

        // the source passed to submit() is the one persisted
        $returnRepository = $this->createMock(ReturnRepository::class);
        $returnRepository
            ->expects($this->once())
            ->method('insert')
            ->with(
                self::ORDER_ID,
                self::CLAIM_ID,
                self::CLAIM_PASSWORD,
                ReturnEntity::STATUS_CREATED,
                ReturnEntity::SOURCE_CUSTOMER
            )
            ->willReturn(true);
        $returnRepository->expects($this->never())->method('insertPending');

        $result = $this->submitter($apiSender, $returnRepository, $this->policy(false))
            ->submit(self::ORDER_ID, ReturnEntity::SOURCE_CUSTOMER);

        $this->assertTrue($result->isCreated());
    }

    public function testAdminReturnAlwaysGoesStraightThroughWithoutConsultingThePolicy(): void
    {
        $apiSender = $this->createStub(ClaimApiSender::class);
        $apiSender->method('send')->willReturn($this->successResponse());

        $returnRepository = $this->createMock(ReturnRepository::class);
        $returnRepository
            ->expects($this->once())
            ->method('insert')
            ->with(
                self::ORDER_ID,
                self::CLAIM_ID,
                self::CLAIM_PASSWORD,
                ReturnEntity::STATUS_CREATED,
                ReturnEntity::SOURCE_ADMIN
            )
            ->willReturn(true);
        $returnRepository->expects($this->never())->method('insertPending');

        // admin returns bypass approval entirely
        $policy = $this->createMock(ReturnApprovalPolicy::class);
        $policy->expects($this->never())->method('needsApproval');

        $result = $this->submitter($apiSender, $returnRepository, $policy)
            ->submit(self::ORDER_ID, ReturnEntity::SOURCE_ADMIN);

        $this->assertTrue($result->isCreated());
    }

    public function testApiFaultReturnsErrorAndDoesNotPersist(): void
    {
        $faultyResponse = new CreateClaimResponse();
        $faultyResponse->setFault('PacketAttributesFault');
        $faultyResponse->setFaultString('Invalid value.');

        $apiSender = $this->createStub(ClaimApiSender::class);
        $apiSender->method('send')->willReturn($faultyResponse);

        $returnRepository = $this->createMock(ReturnRepository::class);
        $returnRepository->expects($this->never())->method('insert');

        $result = $this->submitter($apiSender, $returnRepository, $this->policy(false))
            ->submit(self::ORDER_ID, ReturnEntity::SOURCE_ADMIN);

        $this->assertTrue($result->isError());
        $this->assertNotNull($result->getResponse());
        $this->assertTrue($result->getResponse()->hasFault());
    }

    public function testPersistFailureReportsClaimNotSavedOrphan(): void
    {
        $apiSender = $this->createStub(ClaimApiSender::class);
        $apiSender->method('send')->willReturn($this->successResponse());

        $returnRepository = $this->createStub(ReturnRepository::class);
        $returnRepository->method('insert')->willThrowException(new DatabaseException('write failed'));

        $result = $this->submitter($apiSender, $returnRepository, $this->policy(false))
            ->submit(self::ORDER_ID, ReturnEntity::SOURCE_ADMIN);

        $this->assertTrue($result->isError());
        $this->assertNotNull($result->getResponse());
        $this->assertSame(ClaimFault::CLAIM_NOT_SAVED, $result->getResponse()->getFault());
        $this->assertSame(
            'Return ' . self::CLAIM_ID . ' was created in Packeta but could not be saved to the order.',
            $result->getResponse()->getFaultString()
        );
    }

    private function successResponse(): CreateClaimResponse
    {
        $response = new CreateClaimResponse();
        $response->setId(self::CLAIM_ID);
        $response->setPassword(self::CLAIM_PASSWORD);

        return $response;
    }

    private function policy(bool $needsApproval): ReturnApprovalPolicy
    {
        $policy = $this->createStub(ReturnApprovalPolicy::class);
        $policy->method('needsApproval')->willReturn($needsApproval);

        return $policy;
    }

    private function submitter(
        ClaimApiSender $apiSender,
        ReturnRepository $returnRepository,
        ReturnApprovalPolicy $approvalPolicy
    ): ClaimSubmitter {
        return new ClaimSubmitter($apiSender, $returnRepository, $approvalPolicy);
    }
}
