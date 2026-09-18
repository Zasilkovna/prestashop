<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Order;

use Packetery\Order\OrderRepository;
use Packetery\Tools\DbTools;
use PHPUnit\Framework\TestCase;

class OrderRepositoryGetOrdersByStateAndLastUpdateTest extends TestCase
{
    private function captureSql(): string
    {
        $capturedSql = '';
        $dbTools = $this->createStub(DbTools::class);
        $dbTools
            ->method('getRows')
            ->willReturnCallback(static function (string $sql) use (&$capturedSql) {
                $capturedSql = $sql;

                return [];
            });

        $repository = new OrderRepository(new \Db(), $dbTools);
        $repository->getOrdersByStateAndLastUpdate([3, 4], [7, 10, 11, 999], 100, new \DateTimeImmutable('2026-01-01'));

        return $capturedSql;
    }

    public function testFinalStatusConditionIsBracketed(): void
    {
        $sql = $this->captureSql();

        $this->assertStringContainsString(
            '(`pps`.`status_code` IS NULL OR `pps`.`status_code` NOT IN (7,10,11,999))',
            $sql,
            'Without the brackets the OR detaches the remaining conditions and pulls in unrelated orders.'
        );
    }

    public function testPacketStatusCodeIsNotSelected(): void
    {
        $sql = $this->captureSql();

        $selectPart = substr($sql, 0, (int) strpos($sql, 'FROM'));

        $this->assertStringNotContainsString(
            '`pps`.`status_code`',
            $selectPart,
            'Selecting it defeats DISTINCT when two events share the newest timestamp.'
        );
    }
}
