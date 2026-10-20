<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Module;

use Packetery\Module\ConfigurationPage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfigurationPageTest extends TestCase
{
    protected function tearDown(): void
    {
        \Tools::$values = [];
    }

    /**
     * @return array<string, array{array<string, string>, bool}>
     */
    public static function packetStatusTrackingTabRequestProvider(): array
    {
        return [
            'tab requested' => [['packetery_tab' => 'packet_status_tracking'], true],
            'other tab requested' => [['packetery_tab' => 'returns'], false],
            'no tab requested' => [[], false],
        ];
    }

    /**
     * @param array<string, string> $requestValues
     */
    #[DataProvider('packetStatusTrackingTabRequestProvider')]
    public function testPacketStatusTrackingTabIsRequestedByTabParameter(array $requestValues, bool $expected): void
    {
        \Tools::$values = $requestValues;

        $this->assertSame($expected, ConfigurationPage::isPacketStatusTrackingTabRequested());
    }
}
