<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Returns;

use Packetery\Exceptions\DatabaseException;
use Packetery\Order\ClaimApiSender;
use Packetery\Order\ClaimFault;
use Packetery\Response\CreateClaimResponse;
use Packetery\Returns\ReturnApprover;
use Packetery\Returns\ReturnEntity;
use Packetery\Returns\ReturnRepository;
use PHPUnit\Framework\TestCase;

class ReturnApproverTest extends TestCase
{
    private const RETURN_ID = 7;
    private const ORDER_ID = 42;
    private const CLAIM_ID = 'Z9012';
    private const CLAIM_PASSWORD = 'secret';

    public function testApproveSendsToPacketaAndMarksCreated(): void
    {
        $apiSender = $this->createStub(ClaimApiSender::class);
        $apiSender->method('send')->willReturn($this->successResponse());

        $returnRepository = $this->createMock(ReturnRepository::class);
        $returnRepository->method('getById')->willReturn($this->pendingReturn());
        $returnRepository
            ->expects($this->once())
            ->method('setClaimApproved')
            ->with(self::RETURN_ID, self::CLAIM_ID, self::CLAIM_PASSWORD)
            ->willReturn(true);

        $result = (new ReturnApprover($apiSender, $returnRepository))->approve(self::RETURN_ID);

        $this->assertTrue($result->isCreated());
    }

    public function testApproveSendsWithTheContactStoredOnThePendingReturn(): void
    {
        $return = new ReturnEntity(
            self::RETURN_ID,
            self::ORDER_ID,
            '',
            ReturnEntity::STATUS_PENDING,
            ReturnEntity::SOURCE_CUSTOMER,
            '2026-07-10 09:00:00',
            'stored@example.com',
            '605111222'
        );

        // the claim is built from the contact the customer entered when the return was created
        $apiSender = $this->createMock(ClaimApiSender::class);
        $apiSender
            ->expects($this->once())
            ->method('send')
            ->with(self::ORDER_ID, 'stored@example.com', '605111222')
            ->willReturn($this->successResponse());

        $returnRepository = $this->createStub(ReturnRepository::class);
        $returnRepository->method('getById')->willReturn($return);
        $returnRepository->method('setClaimApproved')->willReturn(true);

        $result = (new ReturnApprover($apiSender, $returnRepository))->approve(self::RETURN_ID);

        $this->assertTrue($result->isCreated());
    }

    public function testApproveOnNonPendingReturnIsRejectedWithoutApiCall(): void
    {
        $apiSender = $this->createMock(ClaimApiSender::class);
        $apiSender->expects($this->never())->method('send');

        $returnRepository = $this->createStub(ReturnRepository::class);
        $returnRepository->method('getById')->willReturn(
            new ReturnEntity(self::RETURN_ID, self::ORDER_ID, self::CLAIM_ID, ReturnEntity::STATUS_CREATED, ReturnEntity::SOURCE_CUSTOMER, '2026-07-10 09:00:00')
        );

        $result = (new ReturnApprover($apiSender, $returnRepository))->approve(self::RETURN_ID);

        $this->assertTrue($result->isError());
        $this->assertNull($result->getResponse());
    }

    public function testApproveKeepsReturnPendingOnApiFault(): void
    {
        $faultyResponse = new CreateClaimResponse();
        $faultyResponse->setFault('PacketAttributesFault');
        $faultyResponse->setFaultString('Invalid value.');

        $apiSender = $this->createStub(ClaimApiSender::class);
        $apiSender->method('send')->willReturn($faultyResponse);

        $returnRepository = $this->createMock(ReturnRepository::class);
        $returnRepository->method('getById')->willReturn($this->pendingReturn());
        $returnRepository->expects($this->never())->method('setClaimApproved');

        $result = (new ReturnApprover($apiSender, $returnRepository))->approve(self::RETURN_ID);

        $this->assertTrue($result->isError());
        $this->assertNotNull($result->getResponse());
        $this->assertTrue($result->getResponse()->hasFault());
    }

    public function testApprovePersistFailureReportsOrphan(): void
    {
        $apiSender = $this->createStub(ClaimApiSender::class);
        $apiSender->method('send')->willReturn($this->successResponse());

        $returnRepository = $this->createStub(ReturnRepository::class);
        $returnRepository->method('getById')->willReturn($this->pendingReturn());
        $returnRepository->method('setClaimApproved')->willThrowException(new DatabaseException('write failed'));

        $result = (new ReturnApprover($apiSender, $returnRepository))->approve(self::RETURN_ID);

        $this->assertTrue($result->isError());
        $this->assertNotNull($result->getResponse());
        $this->assertSame(ClaimFault::CLAIM_NOT_SAVED, $result->getResponse()->getFault());
    }

    public function testRejectMarksPendingReturnRejectedWithoutApiCall(): void
    {
        $apiSender = $this->createMock(ClaimApiSender::class);
        $apiSender->expects($this->never())->method('send');

        $returnRepository = $this->createMock(ReturnRepository::class);
        $returnRepository->method('getById')->willReturn($this->pendingReturn());
        $returnRepository
            ->expects($this->once())
            ->method('updateStatus')
            ->with(self::RETURN_ID, ReturnEntity::STATUS_REJECTED)
            ->willReturn(true);

        $this->assertTrue((new ReturnApprover($apiSender, $returnRepository))->reject(self::RETURN_ID));
    }

    public function testRejectOnNonPendingReturnDoesNothing(): void
    {
        $returnRepository = $this->createMock(ReturnRepository::class);
        $returnRepository->method('getById')->willReturn(null);
        $returnRepository->expects($this->never())->method('updateStatus');

        $this->assertFalse((new ReturnApprover($this->createStub(ClaimApiSender::class), $returnRepository))->reject(self::RETURN_ID));
    }

    private function pendingReturn(): ReturnEntity
    {
        return new ReturnEntity(self::RETURN_ID, self::ORDER_ID, '', ReturnEntity::STATUS_PENDING, ReturnEntity::SOURCE_CUSTOMER, '2026-07-10 09:00:00');
    }

    private function successResponse(): CreateClaimResponse
    {
        $response = new CreateClaimResponse();
        $response->setId(self::CLAIM_ID);
        $response->setPassword(self::CLAIM_PASSWORD);

        return $response;
    }
}
