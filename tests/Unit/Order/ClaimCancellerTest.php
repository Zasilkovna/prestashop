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
use Packetery\Order\OrderRepository;
use Packetery\Response\CancelPacketResponse;
use PHPUnit\Framework\TestCase;

class ClaimCancellerTest extends TestCase
{
    private const ORDER_ID = 42;
    private const CLAIM_ID = 'Z9012';

    public function testCancellingClaimSuccess(): void
    {
        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->method('getById')
            ->willReturn(['claim_id' => self::CLAIM_ID]);
        $orderRepository
            ->expects($this->once())
            ->method('clearClaim')
            ->with(self::ORDER_ID)
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

        $canceller = new ClaimCanceller($soapApi, $orderRepository, $logRepository);
        $response = $canceller->cancel(self::ORDER_ID);

        $this->assertFalse($response->hasFault());
    }

    public function testCancellingClaimClearFailure(): void
    {
        $orderRepository = $this->createStub(OrderRepository::class);
        $orderRepository
            ->method('getById')
            ->willReturn(['claim_id' => self::CLAIM_ID]);
        $orderRepository
            ->method('clearClaim')
            ->willThrowException(new DatabaseException('write failed'));

        $soapApi = $this->createStub(SoapApi::class);
        $soapApi
            ->method('cancelPacket')
            ->willReturn(new CancelPacketResponse());

        // the API cancelled the return, so it is logged before the failing DB clear
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

        $canceller = new ClaimCanceller($soapApi, $orderRepository, $logRepository);
        $response = $canceller->cancel(self::ORDER_ID);

        $this->assertTrue($response->hasFault());
        $this->assertSame(ClaimFault::CLAIM_NOT_CLEARED, $response->getFault());
        $this->assertSame(
            'Return ' . self::CLAIM_ID . ' was cancelled in Packeta but could not be cleared from the order.',
            $response->getFaultString()
        );
    }

    public function testCancellingClaimApiFault(): void
    {
        $faultyResponse = new CancelPacketResponse();
        $faultyResponse->setFault('PacketIdFault');
        $faultyResponse->setFaultString('Unknown packet.');

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->method('getById')
            ->willReturn(['claim_id' => self::CLAIM_ID]);
        $orderRepository
            ->expects($this->never())
            ->method('clearClaim');

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

        $canceller = new ClaimCanceller($soapApi, $orderRepository, $logRepository);
        $response = $canceller->cancel(self::ORDER_ID);

        $this->assertTrue($response->hasFault());
    }
}
