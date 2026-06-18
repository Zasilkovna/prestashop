<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Weight;

use Configuration;
use Packetery\Weight\Converter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConverterTest extends TestCase
{
    private const PRECISION = 10;

    protected function tearDown(): void
    {
        Configuration::reset();
    }

    #[DataProvider('supportedUnitsProvider')]
    public function testGetKilogramsConvertsFromSupportedUnit(
        string $unit,
        float $value,
        float $expectedKilograms
    ): void {
        Configuration::set('PS_WEIGHT_UNIT', $unit);

        $this->assertEquals(
            round($expectedKilograms, self::PRECISION),
            round(Converter::getKilograms($value), self::PRECISION)
        );
    }

    /**
     * @return array<string, array{string, float, float}>
     */
    public static function supportedUnitsProvider(): array
    {
        return [
            'kilograms pass through' => [
                'kg',
                2.5,
                2.5,
            ],
            'grams to kilograms' => [
                'g',
                1500.0,
                1.5,
            ],
            'pounds to kilograms' => [
                'lb',
                1.0,
                0.45359237,
            ],
            'ounces to kilograms' => [
                'oz',
                1.0,
                0.0283495231,
            ],
        ];
    }

    public function testGetKilogramsReturnsNullForUnsupportedUnit(): void
    {
        Configuration::set('PS_WEIGHT_UNIT', 'stone');

        $this->assertNull(Converter::getKilograms(5.0));
    }

    public function testIsKgConversionSupportedForKnownUnit(): void
    {
        Configuration::set('PS_WEIGHT_UNIT', 'kg');

        $this->assertTrue(Converter::isKgConversionSupported());
    }

    public function testIsKgConversionSupportedForUnknownUnit(): void
    {
        Configuration::set('PS_WEIGHT_UNIT', 'stone');

        $this->assertFalse(Converter::isKgConversionSupported());
    }
}
