<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Carrier;

use Packetery\Carrier\CarrierRepository;
use Packetery\Tools\DbTools;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CarrierRepositoryHasActivePairedCarrierTest extends TestCase
{
    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function queryResultProvider(): array
    {
        return [
            'row found' => ['1', true],
            'no row' => [false, false],
        ];
    }

    #[DataProvider('queryResultProvider')]
    public function testResultFollowsQuery(mixed $queryResult, bool $expected): void
    {
        $dbTools = $this->createStub(DbTools::class);
        $dbTools
            ->method('getValue')
            ->willReturn($queryResult);

        $repository = new CarrierRepository(new \Db(), $dbTools);

        $this->assertSame($expected, $repository->hasActivePairedCarrier());
    }

    public function testQueryCountsOnlyActiveNotDeletedPairedCarriers(): void
    {
        $dbTools = $this->createMock(DbTools::class);
        $dbTools
            ->expects($this->once())
            ->method('getValue')
            ->with($this->callback(static function (string $sql): bool {
                $normalizedSql = trim((string) preg_replace('/\s+/', ' ', $sql));
                $dbPrefix = _DB_PREFIX_;

                return str_contains($normalizedSql, "FROM `{$dbPrefix}packetery_address_delivery` `pad`")
                    && str_contains($normalizedSql, "`pad` JOIN `{$dbPrefix}carrier` `c`")
                    && str_contains($normalizedSql, 'WHERE `c`.`active` = 1 AND `c`.`deleted` = 0')
                    && !str_contains($normalizedSql, 'LEFT JOIN');
            }))
            ->willReturn('1');

        $repository = new CarrierRepository(new \Db(), $dbTools);

        $this->assertTrue($repository->hasActivePairedCarrier());
    }
}
