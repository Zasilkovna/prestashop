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
    /**
     * @return array<string, array{array{class: string}, bool}>
     */
    public static function runResultProvider(): array
    {
        return [
            'update that succeeded' => [['class' => 'success'], true],
            'update that failed' => [['class' => 'danger'], false],
        ];
    }

    /**
     * @param array{class: string} $result
     */
    #[DataProvider('runResultProvider')]
    public function testIsSuccessfulRun(array $result, bool $expected): void
    {
        $this->assertSame($expected, Downloader::isSuccessfulRun($result));
    }
}
