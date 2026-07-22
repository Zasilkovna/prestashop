<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Order;

use Packetery\Exceptions\DatabaseException;
use Packetery\Log\LogRepository;
use Packetery\Module\SoapApi;
use Packetery\Order\ClaimCanceller;
use Packetery\Order\ClaimFault;
use Packetery\Response\CancelPacketResponse;
use Packetery\Returns\ReturnEntity;
use Packetery\Returns\ReturnRepository;
use PHPUnit\Framework\TestCase;

class ClaimCancellerTest extends TestCase
{
    private const RETURN_ID = 5;
    private const ORDER_ID = 42;
    private const CLAIM_ID = 'Z9012';

    private static function activeReturn(): ReturnEntity
    {
        return new ReturnEntity(
            self::RETURN_ID,
            self::ORDER_ID,
            self::CLAIM_ID,
            ReturnEntity::STATUS_CREATED,
            ReturnEntity::SOURCE_ADMIN,
            '2026-07-09 12:00:00'
        );
    }

    public function testCancellingClaimSuccess(): void
    {
        $returnRepository = $this->createMock(ReturnRepository::class);
        $returnRepository
            ->method('getById')
            ->willReturn(self::activeReturn());
        $returnRepository
            ->expects($this->once())
            ->method('updateStatus')
            ->with(self::RETURN_ID, ReturnEntity::STATUS_CANCELLED)
            ->willReturn(true);

        $soapApi = $this->createStub(SoapApi::class);
        $soapApi
            ->method('cancelPacket')
            ->willReturn(new CancelPacketResponse());

        $logRepository = $this->createMock(LogRepository::class);
        $logRepository
            ->expects($this->once())
            ->method('insertRow')
            ->with(
                LogRepository::ACTION_CLAIM_CANCELLING,
                ['packetClaimId' => self::CLAIM_ID],
                LogRepository::STATUS_SUCCESS,
                self::ORDER_ID
            );

        $canceller = new ClaimCanceller($soapApi, $returnRepository, $logRepository);
        $response = $canceller->cancel(self::RETURN_ID);

        $this->assertFalse($response->hasFault());
    }

    public function testCancellingClaimUpdateFailure(): void
    {
        $returnRepository = $this->createStub(ReturnRepository::class);
        $returnRepository
            ->method('getById')
            ->willReturn(self::activeReturn());
        $returnRepository
            ->method('updateStatus')
            ->willThrowException(new DatabaseException('write failed'));

        $soapApi = $this->createStub(SoapApi::class);
        $soapApi
            ->method('cancelPacket')
            ->willReturn(new CancelPacketResponse());

        // the API cancelled the return, so it is logged before the failing DB write
        $logRepository = $this->createMock(LogRepository::class);
        $logRepository
            ->expects($this->once())
            ->method('insertRow')
            ->with(
                LogRepository::ACTION_CLAIM_CANCELLING,
                ['packetClaimId' => self::CLAIM_ID],
                LogRepository::STATUS_SUCCESS,
                self::ORDER_ID
            );

        $canceller = new ClaimCanceller($soapApi, $returnRepository, $logRepository);
        $response = $canceller->cancel(self::RETURN_ID);

        $this->assertTrue($response->hasFault());
        $this->assertSame(ClaimFault::CLAIM_NOT_CLEARED, $response->getFault());
        $this->assertSame(
            'Return ' . self::CLAIM_ID . ' was cancelled in Packeta but could not be updated locally.',
            $response->getFaultString()
        );
    }

    public function testCancellingClaimApiFault(): void
    {
        $faultyResponse = new CancelPacketResponse();
        $faultyResponse->setFault('PacketIdFault');
        $faultyResponse->setFaultString('Unknown packet.');

        $returnRepository = $this->createMock(ReturnRepository::class);
        $returnRepository
            ->method('getById')
            ->willReturn(self::activeReturn());
        $returnRepository
            ->expects($this->never())
            ->method('updateStatus');

        $soapApi = $this->createStub(SoapApi::class);
        $soapApi
            ->method('cancelPacket')
            ->willReturn($faultyResponse);

        $logRepository = $this->createMock(LogRepository::class);
        $logRepository
            ->expects($this->once())
            ->method('insertRow')
            ->with(
                LogRepository::ACTION_CLAIM_CANCELLING,
                [
                    'fault' => 'PacketIdFault',
                    'faultString' => 'Unknown packet.',
                ],
                LogRepository::STATUS_ERROR,
                self::ORDER_ID
            );

        $canceller = new ClaimCanceller($soapApi, $returnRepository, $logRepository);
        $response = $canceller->cancel(self::RETURN_ID);

        $this->assertTrue($response->hasFault());
    }

    public function testCancellingMissingReturnFaultsWithoutApiCall(): void
    {
        $returnRepository = $this->createStub(ReturnRepository::class);
        $returnRepository->method('getById')->willReturn(null);

        $canceller = new ClaimCanceller($this->guardedSoapApi(), $returnRepository, $this->guardedLogRepository());
        $response = $canceller->cancel(self::RETURN_ID);

        $this->assertTrue($response->hasFault());
        $this->assertSame(ClaimFault::NO_CLAIM_ID, $response->getFault());
    }

    public function testCancellingNonCreatedReturnFaultsWithoutApiCall(): void
    {
        $pendingReturn = new ReturnEntity(
            self::RETURN_ID,
            self::ORDER_ID,
            '',
            ReturnEntity::STATUS_PENDING,
            ReturnEntity::SOURCE_CUSTOMER,
            '2026-07-09 12:00:00'
        );

        $returnRepository = $this->createStub(ReturnRepository::class);
        $returnRepository->method('getById')->willReturn($pendingReturn);

        $canceller = new ClaimCanceller($this->guardedSoapApi(), $returnRepository, $this->guardedLogRepository());
        $response = $canceller->cancel(self::RETURN_ID);

        $this->assertTrue($response->hasFault());
        $this->assertSame(ClaimFault::NO_CLAIM_ID, $response->getFault());
    }

    private function guardedSoapApi(): SoapApi
    {
        $soapApi = $this->createMock(SoapApi::class);
        $soapApi->expects($this->never())->method('cancelPacket');

        return $soapApi;
    }

    private function guardedLogRepository(): LogRepository
    {
        $logRepository = $this->createMock(LogRepository::class);
        $logRepository->expects($this->never())->method('insertRow');

        return $logRepository;
    }
}
