<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Returns;

use Packetery\Returns\ReturnEntity;
use Packetery\Returns\ReturnRepository;
use Packetery\Tools\DbTools;
use PHPUnit\Framework\TestCase;

class ReturnRepositoryTest extends TestCase
{
    public function testGetByOrderIdMapsRowsToEntities(): void
    {
        $dbTools = $this->createStub(DbTools::class);
        $dbTools->method('getRows')->willReturn([
            [
                'id_return' => '1',
                'id_order' => '42',
                'claim_id' => 'C1',
                'claim_password' => null,
                'status' => 'created',
                'source' => 'admin',
                'date_add' => '2026-07-09 10:00:00',
            ],
            [
                'id_return' => '2',
                'id_order' => '42',
                'claim_id' => 'C2',
                'claim_password' => 'pw',
                'status' => 'cancelled',
                'source' => 'customer',
                'date_add' => '2026-07-09 11:00:00',
            ],
        ]);

        $returns = (new ReturnRepository($dbTools))->getByOrderId(42);

        $this->assertCount(2, $returns);
        $this->assertContainsOnlyInstancesOf(ReturnEntity::class, $returns);
        $this->assertSame(1, $returns[0]->getIdReturn());
        $this->assertSame('C2', $returns[1]->getClaimId());
    }

    public function testGetByOrderIdReturnsEmptyArrayWhenNoRows(): void
    {
        $dbTools = $this->createStub(DbTools::class);
        $dbTools->method('getRows')->willReturn([]);

        $this->assertSame([], (new ReturnRepository($dbTools))->getByOrderId(999));
    }

    public function testGetByIdReturnsEntityWhenRowFound(): void
    {
        $dbTools = $this->createStub(DbTools::class);
        $dbTools->method('getRow')->willReturn([
            'id_return' => '3',
            'id_order' => '7',
            'claim_id' => 'C3',
            'claim_password' => null,
            'status' => 'created',
            'source' => 'admin',
            'date_add' => '2026-07-09 12:00:00',
        ]);

        $return = (new ReturnRepository($dbTools))->getById(3);

        $this->assertNotNull($return);
        $this->assertSame(3, $return->getIdReturn());
    }

    public function testGetByIdReturnsNullWhenRowMissing(): void
    {
        $dbTools = $this->createStub(DbTools::class);
        $dbTools->method('getRow')->willReturn(false);

        $this->assertNull((new ReturnRepository($dbTools))->getById(999));
    }

    public function testGetActiveByOrderIdReturnsEntityWhenActiveReturnExists(): void
    {
        $dbTools = $this->createStub(DbTools::class);
        $dbTools->db = new \Db();
        $dbTools->method('getRow')->willReturn([
            'id_return' => '8',
            'id_order' => '42',
            'claim_id' => 'C8',
            'claim_password' => null,
            'status' => 'created',
            'source' => 'admin',
            'date_add' => '2026-07-09 13:00:00',
        ]);

        $return = (new ReturnRepository($dbTools))->getActiveByOrderId(42);

        $this->assertNotNull($return);
        $this->assertSame(8, $return->getIdReturn());
        $this->assertSame('created', $return->getStatus());
    }

    public function testGetActiveByOrderIdReturnsNullWhenNoActiveReturn(): void
    {
        $dbTools = $this->createStub(DbTools::class);
        $dbTools->db = new \Db();
        $dbTools->method('getRow')->willReturn(false);

        $this->assertNull((new ReturnRepository($dbTools))->getActiveByOrderId(42));
    }

    public function testCountPendingReturnsCountAsInt(): void
    {
        $dbTools = $this->createStub(DbTools::class);
        $dbTools->db = new \Db();
        $dbTools->method('getValue')->willReturn('3');

        $this->assertSame(3, (new ReturnRepository($dbTools))->countPending());
    }

    public function testCountPendingReturnsZeroWhenNoPendingReturns(): void
    {
        $dbTools = $this->createStub(DbTools::class);
        $dbTools->db = new \Db();
        $dbTools->method('getValue')->willReturn(false);

        $this->assertSame(0, (new ReturnRepository($dbTools))->countPending());
    }

    public function testCountPendingQueriesOnlyPendingStatus(): void
    {
        $dbTools = $this->createMock(DbTools::class);
        $dbTools->db = new \Db();
        $dbTools->expects($this->once())
            ->method('getValue')
            ->with($this->logicalAnd($this->stringContains('COUNT(*)'), $this->stringContains('pending')))
            ->willReturn('2');

        $this->assertSame(2, (new ReturnRepository($dbTools))->countPending());
    }

    public function testInsertStoresTheConsentTimestampAndLanguage(): void
    {
        $data = $this->captureInsert(function (ReturnRepository $repository): void {
            $repository->insert(42, 'C1', 'pw', ReturnEntity::STATUS_CREATED, ReturnEntity::SOURCE_CUSTOMER, '2026-07-09 11:59:58', 'cs');
        });

        $this->assertSame('2026-07-09 11:59:58', $data['consent_at']);
        $this->assertSame('cs', $data['consent_language']);
    }

    public function testInsertStoresNullConsentWhenNoneWasGiven(): void
    {
        $data = $this->captureInsert(function (ReturnRepository $repository): void {
            $repository->insert(42, 'C1', 'pw', ReturnEntity::STATUS_CREATED, ReturnEntity::SOURCE_ADMIN);
        });

        $this->assertNull($data['consent_at']);
        $this->assertNull($data['consent_language']);
    }

    public function testInsertPendingStoresTheConsentTimestampAndLanguage(): void
    {
        $data = $this->captureInsert(function (ReturnRepository $repository): void {
            $repository->insertPending(42, ReturnEntity::SOURCE_CUSTOMER, 'a@b.cz', '777', '2026-07-09 11:59:58', 'sk');
        });

        $this->assertSame('2026-07-09 11:59:58', $data['consent_at']);
        $this->assertSame('sk', $data['consent_language']);
    }

    /**
     * @param callable(ReturnRepository): void $call
     *
     * @return array<string, mixed> the row passed to DbTools::insert()
     */
    private function captureInsert(callable $call): array
    {
        $captured = [];
        $dbTools = $this->createStub(DbTools::class);
        $dbTools->db = new \Db();
        $dbTools->method('insert')->willReturnCallback(static function ($table, $data) use (&$captured): bool {
            $captured = $data;

            return true;
        });

        $call(new ReturnRepository($dbTools));

        return $captured;
    }
}
