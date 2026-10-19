<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\ApiCarrier;

use Packetery\ApiCarrier\Downloader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DownloaderTest extends TestCase
{
    private const MANUAL = '1';
    private const NOT_MANUAL = false;
    private const SUCCESS = ['class' => 'success'];
    private const FAILURE = ['class' => 'danger'];

    /**
     * @return array<string, array{string|false, array{class: string}, bool}>
     */
    public static function manualRunProvider(): array
    {
        return [
            'manual update that succeeded' => [self::MANUAL, self::SUCCESS, true],
            'manual update that failed' => [self::MANUAL, self::FAILURE, false],
            'automatic update that succeeded' => [self::NOT_MANUAL, self::SUCCESS, false],
            'automatic update that failed' => [self::NOT_MANUAL, self::FAILURE, false],
        ];
    }

    /**
     * @param array{class: string} $result
     */
    #[DataProvider('manualRunProvider')]
    public function testIsSuccessfulManualRun(
        string|false $manual,
        array $result,
        bool $expected
    ): void {
        $this->assertSame($expected, Downloader::isSuccessfulManualRun($manual, $result));
    }
}
