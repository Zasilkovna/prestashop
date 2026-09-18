<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\PacketTracking;

use Packetery\Log\LogRepository;
use Packetery\Module\SoapApi;
use Packetery\Order\OrderRepository;
use Packetery\PacketTracking\PacketStatus;
use Packetery\PacketTracking\PacketStatusComparator;
use Packetery\PacketTracking\PacketStatusFactory;
use Packetery\PacketTracking\PacketTrackingCron;
use Packetery\PacketTracking\PacketTrackingRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PacketTrackingCronTest extends TestCase
{
    protected function setUp(): void
    {
        \Configuration::reset();
        \Configuration::set('PACKETERY_PACKET_STATUS_TRACKING_ENABLED', true);
        \Configuration::set('PACKETERY_PACKET_STATUS_TRACKING_ORDER_STATES', '{"3":"on"}');
        \Configuration::set('PACKETERY_PACKET_STATUS_TRACKING_PACKET_STATUSES', '{"3":"on"}');
        \Configuration::set('PACKETERY_PACKET_STATUS_TRACKING_MAX_ORDER_AGE_DAYS', 30);
        \Configuration::set('PACKETERY_PACKET_STATUS_TRACKING_MAX_PROCESSED_ORDERS', 100);
        \Configuration::set('PACKETERY_ORDER_STATUS_CHANGE_ENABLED', false);
    }

    /**
     * A failing API call used to skip the timestamp, so the order kept returning to the front of the
     * queue — this is what a cancelled packet does, its tracking number is gone but its statuses stay.
     */
    public function testTimestampIsStoredEvenWhenApiCallFails(): void
    {
        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->method('getOrdersByStateAndLastUpdate')
            ->willReturn([['id_order' => 4877, 'tracking_number' => '']]);
        $orderRepository
            ->expects($this->once())
            ->method('setLastUpdateTrackingStatus')
            ->with($this->isInstanceOf(\DateTimeImmutable::class), 4877);

        $soapApi = $this->createStub(SoapApi::class);
        $soapApi->method('getPacketTracking')->willReturn("Incorrect packet ID ''.");

        $this->createCron($orderRepository, $soapApi)->run();
    }

    public function testTimestampIsStoredWhenNothingChanged(): void
    {
        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->method('getOrdersByStateAndLastUpdate')
            ->willReturn([['id_order' => 4877, 'tracking_number' => 'Z1']]);
        $orderRepository
            ->expects($this->once())
            ->method('setLastUpdateTrackingStatus')
            ->with($this->isInstanceOf(\DateTimeImmutable::class), 4877);

        $soapApi = $this->createStub(SoapApi::class);
        $soapApi->method('getPacketTracking')->willReturn(
            $this->createApiResponse([$this->createRecord('2026-09-10T10:00:00', 3)])
        );

        $comparator = $this->createStub(PacketStatusComparator::class);
        $comparator->method('isDifferenceBetweenApiAndDatabase')->willReturn(false);

        $trackingRepository = $this->createStub(PacketTrackingRepository::class);
        $trackingRepository->method('getPacketStatusesByOrderId')->willReturn([]);

        $this->createCron($orderRepository, $soapApi, $comparator, $trackingRepository)->run();
    }

    /**
     * Guards the wiring, not just the helper: with end() the run picks the event the API happened to
     * return last, which here is the older, untracked one, and the order is skipped.
     */
    public function testRunActsOnTheNewestEventRatherThanTheLastOneReturned(): void
    {
        $orderRepository = $this->createStub(OrderRepository::class);
        $orderRepository
            ->method('getOrdersByStateAndLastUpdate')
            ->willReturn([['id_order' => 4877, 'tracking_number' => 'Z1']]);

        $soapApi = $this->createStub(SoapApi::class);
        $soapApi->method('getPacketTracking')->willReturn(
            $this->createApiResponse([
                $this->createRecord('2026-09-10T10:15:00', 3),
                $this->createRecord('2026-09-10T10:00:00', 2),
            ])
        );

        $trackingRepository = $this->createMock(PacketTrackingRepository::class);
        $trackingRepository
            ->expects($this->once())
            ->method('getPacketStatusesByOrderId')
            ->with(4877)
            ->willReturn([]);

        $this->createCron($orderRepository, $soapApi, null, $trackingRepository)->run();
    }

    /**
     * @param array{0: string, 1: int}[] $records
     */
    #[DataProvider('latestRecordProvider')]
    public function testLatestRecordIsPickedByDateTime(array $records, int $expectedStatusCode): void
    {
        $cron = $this->createCron(
            $this->createStub(OrderRepository::class),
            $this->createStub(SoapApi::class)
        );

        $apiRecords = [];
        foreach ($records as [$dateTime, $statusCode]) {
            $apiRecords[] = $this->createRecord($dateTime, $statusCode);
        }

        $latest = (new \ReflectionClass($cron))
            ->getMethod('getLatestRecord')
            ->invokeArgs($cron, [$apiRecords, [PacketStatus::DELIVERED, PacketStatus::RETURNED]]);

        $this->assertSame($expectedStatusCode, $latest->statusCode);
    }

    /**
     * @return array<string, array{0: array{0: string, 1: int}[], 1: int}>
     */
    public static function latestRecordProvider(): array
    {
        return [
            'chronological order' => [
                [['2026-09-10T10:00:00', 2], ['2026-09-10T10:15:00', 3]],
                3,
            ],
            'newest event arrives first' => [
                [['2026-09-10T10:15:00', 3], ['2026-09-10T10:00:00', 2]],
                3,
            ],
            'equal timestamps prefer the final status' => [
                [['2026-09-10T10:15:00', 3], ['2026-09-10T10:15:00', PacketStatus::DELIVERED]],
                PacketStatus::DELIVERED,
            ],
            'equal timestamps keep the first when neither is final' => [
                [['2026-09-10T10:15:00', 3], ['2026-09-10T10:15:00', 2]],
                3,
            ],
            'record without a timestamp never wins' => [
                [['', 2], ['2026-09-11T10:00:00', 3]],
                3,
            ],
            'single record' => [
                [['2026-09-10T10:15:00', 5]],
                5,
            ],
        ];
    }

    private function createRecord(string $dateTime, int $statusCode): \stdClass
    {
        $record = new \stdClass();
        $record->dateTime = $dateTime;
        $record->statusCode = $statusCode;
        $record->statusText = 'text';

        return $record;
    }

    /**
     * @param \stdClass[] $records
     */
    private function createApiResponse(array $records): \stdClass
    {
        $response = new \stdClass();
        $response->record = $records;

        return $response;
    }

    private function createCron(
        OrderRepository $orderRepository,
        SoapApi $soapApi,
        ?PacketStatusComparator $comparator = null,
        ?PacketTrackingRepository $trackingRepository = null
    ): PacketTrackingCron {
        $packetStatusFactory = $this->createStub(PacketStatusFactory::class);
        $packetStatusFactory->method('getPacketStatuses')->willReturn([
            new PacketStatus(PacketStatus::DELIVERED, 'delivered', 'Delivered', true),
            new PacketStatus(PacketStatus::RETURNED, 'returned', 'Returned to sender', true),
        ]);

        return new PacketTrackingCron(
            new \Packetery(),
            $orderRepository,
            $soapApi,
            $trackingRepository ?? $this->createStub(PacketTrackingRepository::class),
            $comparator ?? $this->createStub(PacketStatusComparator::class),
            $this->createStub(LogRepository::class),
            $packetStatusFactory
        );
    }
}
