<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Order;

use Packetery\Exceptions\ClaimRequestException;
use Packetery\Exceptions\DatabaseException;
use Packetery\Log\LogRepository;
use Packetery\Module\SoapApi;
use Packetery\Order\ClaimFault;
use Packetery\Order\ClaimRequestFactory;
use Packetery\Order\ClaimSubmitter;
use Packetery\Order\OrderRepository;
use Packetery\Request\CreateClaimRequest;
use Packetery\Response\CreateClaimResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ClaimSubmitterTest extends TestCase
{
    private const ORDER_ID = 42;
    private const CLAIM_ID = 'Z9012';
    private const CLAIM_PASSWORD = 'secret';

    #[DataProvider('provideIncompleteOrderData')]
    public function testCreatingClaimIncompleteOrder(string $fault, string $faultString): void
    {
        $requestFactory = $this->createStub(ClaimRequestFactory::class);
        $requestFactory
            ->method('create')
            ->willThrowException(new ClaimRequestException($fault, $faultString));

        $soapApi = $this->createMock(SoapApi::class);
        $soapApi
            ->expects($this->never())
            ->method('createPacketClaimWithPassword');

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects($this->never())
            ->method('setClaim');

        // pre-API validation never reaches the API, so it must not be written to the API log
        $logRepository = $this->createMock(LogRepository::class);
        $logRepository
            ->expects($this->never())
            ->method('insertRow');

        $response = $this->createSubmitter($requestFactory, $soapApi, $orderRepository, $logRepository)
            ->submit(self::ORDER_ID);

        $this->assertTrue($response->hasFault());
        $this->assertSame($fault, $response->getFault());
        $this->assertSame($faultString, $response->getFaultString());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function provideIncompleteOrderData(): array
    {
        return [
            'order not found' => [ClaimFault::ORDER_NOT_FOUND, 'Packetery order not found.'],
            'eshop id missing' => [ClaimFault::ESHOP_ID_MISSING, 'Packetery eShop ID is not configured.'],
            'order value unresolved' => [ClaimFault::VALUE_UNRESOLVED, 'Order total value could not be resolved.'],
            'email missing' => [ClaimFault::EMAIL_MISSING, 'Customer email is missing; the return cannot be created.'],
            'phone missing' => [ClaimFault::PHONE_MISSING, 'Customer phone is missing; the return cannot be created.'],
        ];
    }

    public function testCreatingClaimApiFault(): void
    {
        $faultyResponse = new CreateClaimResponse();
        $faultyResponse->setFault('PacketAttributesFault');
        $faultyResponse->setFaultString('Invalid value.');

        $requestFactory = $this->createStub(ClaimRequestFactory::class);
        $requestFactory
            ->method('create')
            ->willReturn($this->createStub(CreateClaimRequest::class));

        $soapApi = $this->createStub(SoapApi::class);
        $soapApi
            ->method('createPacketClaimWithPassword')
            ->willReturn($faultyResponse);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects($this->never())
            ->method('setClaim');

        $logRepository = $this->createMock(LogRepository::class);
        $logRepository
            ->expects($this->once())
            ->method('insertRow')
            ->with(
                LogRepository::ACTION_CLAIM_CREATION,
                [
                    'fault' => 'PacketAttributesFault',
                    'faultString' => 'Invalid value.',
                ],
                LogRepository::STATUS_ERROR,
                self::ORDER_ID
            );

        $response = $this->createSubmitter($requestFactory, $soapApi, $orderRepository, $logRepository)
            ->submit(self::ORDER_ID);

        $this->assertTrue($response->hasFault());
    }

    public function testCreatingClaimMissingClaimId(): void
    {
        $requestFactory = $this->createStub(ClaimRequestFactory::class);
        $requestFactory
            ->method('create')
            ->willReturn($this->createStub(CreateClaimRequest::class));

        $soapApi = $this->createStub(SoapApi::class);
        $soapApi
            ->method('createPacketClaimWithPassword')
            ->willReturn(new CreateClaimResponse());

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects($this->never())
            ->method('setClaim');

        $logRepository = $this->createMock(LogRepository::class);
        $logRepository
            ->expects($this->once())
            ->method('insertRow')
            ->with(
                LogRepository::ACTION_CLAIM_CREATION,
                [
                    'fault' => ClaimFault::NO_CLAIM_ID,
                    'faultString' => 'Packeta API returned a response without a return number.',
                ],
                LogRepository::STATUS_ERROR,
                self::ORDER_ID
            );

        $response = $this->createSubmitter($requestFactory, $soapApi, $orderRepository, $logRepository)
            ->submit(self::ORDER_ID);

        $this->assertTrue($response->hasFault());
        $this->assertSame(ClaimFault::NO_CLAIM_ID, $response->getFault());
    }

    public function testCreatingClaimSuccess(): void
    {
        $successResponse = new CreateClaimResponse();
        $successResponse->setId(self::CLAIM_ID);
        $successResponse->setPassword(self::CLAIM_PASSWORD);

        $requestFactory = $this->createStub(ClaimRequestFactory::class);
        $requestFactory
            ->method('create')
            ->willReturn($this->createStub(CreateClaimRequest::class));

        $soapApi = $this->createStub(SoapApi::class);
        $soapApi
            ->method('createPacketClaimWithPassword')
            ->willReturn($successResponse);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects($this->once())
            ->method('setClaim')
            ->with(self::ORDER_ID, self::CLAIM_ID, self::CLAIM_PASSWORD)
            ->willReturn(true);

        $logRepository = $this->createMock(LogRepository::class);
        $logRepository
            ->expects($this->once())
            ->method('insertRow')
            ->with(
                LogRepository::ACTION_CLAIM_CREATION,
                ['packetClaimId' => self::CLAIM_ID],
                LogRepository::STATUS_SUCCESS,
                self::ORDER_ID
            );

        $response = $this->createSubmitter($requestFactory, $soapApi, $orderRepository, $logRepository)
            ->submit(self::ORDER_ID);

        $this->assertFalse($response->hasFault());
    }

    public function testCreatingClaimPersistFailure(): void
    {
        $successResponse = new CreateClaimResponse();
        $successResponse->setId(self::CLAIM_ID);
        $successResponse->setPassword(self::CLAIM_PASSWORD);

        $requestFactory = $this->createStub(ClaimRequestFactory::class);
        $requestFactory
            ->method('create')
            ->willReturn($this->createStub(CreateClaimRequest::class));

        $soapApi = $this->createStub(SoapApi::class);
        $soapApi
            ->method('createPacketClaimWithPassword')
            ->willReturn($successResponse);

        $orderRepository = $this->createStub(OrderRepository::class);
        $orderRepository
            ->method('setClaim')
            ->willThrowException(new DatabaseException('write failed'));

        // the API created the return, so its number is logged before the failing DB write
        $logRepository = $this->createMock(LogRepository::class);
        $logRepository
            ->expects($this->once())
            ->method('insertRow')
            ->with(
                LogRepository::ACTION_CLAIM_CREATION,
                ['packetClaimId' => self::CLAIM_ID],
                LogRepository::STATUS_SUCCESS,
                self::ORDER_ID
            );

        $response = $this->createSubmitter($requestFactory, $soapApi, $orderRepository, $logRepository)
            ->submit(self::ORDER_ID);

        $this->assertTrue($response->hasFault());
        $this->assertSame(ClaimFault::CLAIM_NOT_SAVED, $response->getFault());
        $this->assertSame(
            'Return ' . self::CLAIM_ID . ' was created in Packeta but could not be saved to the order.',
            $response->getFaultString()
        );
    }

    private function createSubmitter(
        ClaimRequestFactory $requestFactory,
        SoapApi $soapApi,
        OrderRepository $orderRepository,
        LogRepository $logRepository
    ): ClaimSubmitter {
        return new ClaimSubmitter($requestFactory, $soapApi, $orderRepository, $logRepository);
    }
}
