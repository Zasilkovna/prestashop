<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Module;

use Packetery\Module\Helper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    private const BOX_CONSIGNMENT_GUIDE_URL_CS = 'https://docs.packeta.com/cs/guides/box-consignment';
    private const BOX_CONSIGNMENT_GUIDE_URL_EN = 'https://docs.packeta.com/guides/box-consignment';

    /**
     * @param string|false $language
     */
    #[DataProvider('boxConsignmentGuideUrlProvider')]
    public function testGetBoxConsignmentGuideUrlReturnsLocaleAwareUrl(
        $language,
        string $expectedUrl
    ): void {
        $this->assertSame(
            $expectedUrl,
            Helper::getBoxConsignmentGuideUrl($language)
        );
    }

    /**
     * @return array<string, array{string|false, string}>
     */
    public static function boxConsignmentGuideUrlProvider(): array
    {
        return [
            'czech returns the CZ guide' => [
                'cs',
                self::BOX_CONSIGNMENT_GUIDE_URL_CS,
            ],
            'other language falls back to EN' => [
                'sk',
                self::BOX_CONSIGNMENT_GUIDE_URL_EN,
            ],
            'missing language falls back to EN' => [
                false,
                self::BOX_CONSIGNMENT_GUIDE_URL_EN,
            ],
        ];
    }
}
