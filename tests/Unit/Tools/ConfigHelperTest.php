<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Tools;

use Packetery\Tools\ConfigHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfigHelperTest extends TestCase
{
    private const GLOBAL_SHOP_IDS = [0, 0];

    protected function tearDown(): void
    {
        \Configuration::reset();
    }

    /**
     * @return array<string, array{string, array{int|null, int|null}}>
     */
    public static function shopIdsByKeyProvider(): array
    {
        return [
            'carrier list update last run is global' => [
                ConfigHelper::KEY_CRON_LAST_RUN_DOWNLOAD_CARRIERS,
                self::GLOBAL_SHOP_IDS,
            ],
            'packet status update last run is global' => [
                ConfigHelper::KEY_CRON_LAST_RUN_UPDATE_PACKET_STATUS,
                self::GLOBAL_SHOP_IDS,
            ],
            'consignment code retrieval last run is global' => [
                ConfigHelper::KEY_CRON_LAST_RUN_GET_CONSIGN_PASSWORD,
                self::GLOBAL_SHOP_IDS,
            ],
            'manual carrier update flag is global' => [
                ConfigHelper::KEY_CARRIERS_MANUALLY_UPDATED,
                self::GLOBAL_SHOP_IDS,
            ],
            'sender indication is saved for the shop context' => [
                ConfigHelper::KEY_ESHOP_ID,
                [null, null],
            ],
        ];
    }

    /**
     * @param array{int|null, int|null} $expectedShopIds
     */
    #[DataProvider('shopIdsByKeyProvider')]
    public function testUpdateSavesKeyForExpectedShops(string $key, array $expectedShopIds): void
    {
        ConfigHelper::update($key, '1');

        $this->assertSame($expectedShopIds, \Configuration::getUpdateShopIds($key));
    }
}
