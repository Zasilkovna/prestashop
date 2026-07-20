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
use Packetery\Order\ClaimApiSender;
use Packetery\Order\ClaimFault;
use Packetery\Order\ClaimRequestFactory;
use Packetery\Request\CreateClaimRequest;
use Packetery\Response\CreateClaimResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ClaimApiSenderTest extends TestCase
{
    private const ORDER_ID = 42;
    private const CLAIM_ID = 'Z9012';
    private const CLAIM_PASSWORD = 'secret';

    #[DataProvider('provideIncompleteOrderData')]
    public function testIncompleteOrderNeverReachesTheApi(string $fault, string $faultString): void
    {
        $requestFactory = $this->createStub(ClaimRequestFactory::class);
        $requestFactory
            ->method('create')
            ->willThrowException(new ClaimRequestException($fault, $faultString));

        $soapApi = $this->createMock(SoapApi::class);
        $soapApi
            ->expects($this->never())
            ->method('createPacketClaimWithPassword');

        // pre-API validation never reaches the API, so it must not be written to the API log
        $logRepository = $this->createMock(LogRepository::class);
        $logRepository
            ->expects($this->never())
            ->method('insertRow');

        $response = $this->createSender($requestFactory, $soapApi, $logRepository)->send(self::ORDER_ID);

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
        ];
    }

    public function testApiFaultIsLogged(): void
    {
        $faultyResponse = new CreateClaimResponse();
        $faultyResponse->setFault('PacketAttributesFault');
        $faultyResponse->setFaultString('Invalid value.');

        $soapApi = $this->createStub(SoapApi::class);
        $soapApi->method('createPacketClaimWithPassword')->willReturn($faultyResponse);

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

        $response = $this->createSender($this->requestFactoryStub(), $soapApi, $logRepository)->send(self::ORDER_ID);

        $this->assertTrue($response->hasFault());
    }

    public function testMissingClaimIdIsAnApiError(): void
    {
        $soapApi = $this->createStub(SoapApi::class);
        $soapApi->method('createPacketClaimWithPassword')->willReturn(new CreateClaimResponse());

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

        $response = $this->createSender($this->requestFactoryStub(), $soapApi, $logRepository)->send(self::ORDER_ID);

        $this->assertTrue($response->hasFault());
        $this->assertSame(ClaimFault::NO_CLAIM_ID, $response->getFault());
    }

    public function testSuccessLogsAndReturnsClaim(): void
    {
        $successResponse = new CreateClaimResponse();
        $successResponse->setId(self::CLAIM_ID);
        $successResponse->setPassword(self::CLAIM_PASSWORD);

        $soapApi = $this->createStub(SoapApi::class);
        $soapApi->method('createPacketClaimWithPassword')->willReturn($successResponse);

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

        $response = $this->createSender($this->requestFactoryStub(), $soapApi, $logRepository)->send(self::ORDER_ID);

        $this->assertFalse($response->hasFault());
        $this->assertSame(self::CLAIM_ID, $response->getId());
        $this->assertSame(self::CLAIM_PASSWORD, $response->getPassword());
    }

    public function testFinalizeReturnsErrorAndSkipsPersistOnFault(): void
    {
        $faultyResponse = new CreateClaimResponse();
        $faultyResponse->setFault('PacketAttributesFault');
        $faultyResponse->setFaultString('Invalid value.');

        $persisted = false;
        $result = ClaimApiSender::finalize($faultyResponse, function () use (&$persisted): void {
            $persisted = true;
        });

        $this->assertTrue($result->isError());
        $this->assertFalse($persisted);
        $this->assertSame($faultyResponse, $result->getResponse());
    }

    public function testFinalizeReturnsCreatedWhenPersistSucceeds(): void
    {
        $successResponse = new CreateClaimResponse();
        $successResponse->setId(self::CLAIM_ID);

        $persisted = false;
        $result = ClaimApiSender::finalize($successResponse, function () use (&$persisted): void {
            $persisted = true;
        });

        $this->assertTrue($result->isCreated());
        $this->assertTrue($persisted);
    }

    public function testFinalizeReportsOrphanWhenPersistFails(): void
    {
        $successResponse = new CreateClaimResponse();
        $successResponse->setId(self::CLAIM_ID);

        $result = ClaimApiSender::finalize($successResponse, function (): void {
            throw new DatabaseException('write failed');
        });

        $this->assertTrue($result->isError());
        $this->assertNotNull($result->getResponse());
        $this->assertSame(ClaimFault::CLAIM_NOT_SAVED, $result->getResponse()->getFault());
        $this->assertSame(
            'Return ' . self::CLAIM_ID . ' was created in Packeta but could not be saved to the order.',
            $result->getResponse()->getFaultString()
        );
    }

    private function requestFactoryStub(): ClaimRequestFactory
    {
        $requestFactory = $this->createStub(ClaimRequestFactory::class);
        $requestFactory->method('create')->willReturn($this->createStub(CreateClaimRequest::class));

        return $requestFactory;
    }

    private function createSender(
        ClaimRequestFactory $requestFactory,
        SoapApi $soapApi,
        LogRepository $logRepository
    ): ClaimApiSender {
        return new ClaimApiSender($requestFactory, $soapApi, $logRepository);
    }
}
