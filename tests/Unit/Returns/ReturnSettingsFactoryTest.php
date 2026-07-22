<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Returns;

use Packetery\Carrier\CarrierTools;
use Packetery\Returns\ReturnSettingsFactory;
use Packetery\Tools\ConfigHelper;
use PHPUnit\Framework\TestCase;

class ReturnSettingsFactoryTest extends TestCase
{
    public function testDefaultsWhenNothingConfigured(): void
    {
        $settings = (new ReturnSettingsFactory())->fromRawValues([]);

        $this->assertFalse($settings->isEnabled());
        $this->assertFalse($settings->allowsUnregistered());
        $this->assertSame(14, $settings->getWindowDays());
        $this->assertSame([], $settings->getExcludedCategoryIds());
        $this->assertTrue($settings->excludesVirtual());
        $this->assertNull($settings->getMaxItemValue());
        $this->assertNull($settings->getMaxItemWeight());
        $this->assertSame([], $settings->getAllowedCustomerGroupIds());
        $this->assertSame([], $settings->getAllowedCarrierReferences());
        $this->assertSame(CarrierTools::COUNTRIES_WITH_INTERNAL_PICKUP_POINTS, $settings->getAllowedCountryIsos());
        $this->assertFalse($settings->isApprovalRequired());
    }

    public function testFalseValuesFromConfigTreatedAsUnset(): void
    {
        // ConfigHelper::get() returns false for keys that were never stored.
        $raw = array_fill_keys(
            [
                ConfigHelper::KEY_RETURNS_ENABLED,
                ConfigHelper::KEY_RETURNS_ALLOW_UNREGISTERED,
                ConfigHelper::KEY_RETURNS_WINDOW_DAYS,
                ConfigHelper::KEY_RETURNS_EXCLUDED_CATEGORIES,
                ConfigHelper::KEY_RETURNS_EXCLUDE_VIRTUAL,
                ConfigHelper::KEY_RETURNS_MAX_ITEM_VALUE,
                ConfigHelper::KEY_RETURNS_MAX_ITEM_WEIGHT,
                ConfigHelper::KEY_RETURNS_ALLOWED_GROUPS,
                ConfigHelper::KEY_RETURNS_ALLOWED_CARRIERS,
                ConfigHelper::KEY_RETURNS_ALLOWED_COUNTRIES,
            ],
            false
        );

        $settings = (new ReturnSettingsFactory())->fromRawValues($raw);

        $this->assertSame(14, $settings->getWindowDays());
        $this->assertNull($settings->getMaxItemValue());
        $this->assertSame([], $settings->getExcludedCategoryIds());
        $this->assertSame(CarrierTools::COUNTRIES_WITH_INTERNAL_PICKUP_POINTS, $settings->getAllowedCountryIsos());
    }

    public function testParsesConfiguredValues(): void
    {
        $settings = (new ReturnSettingsFactory())->fromRawValues([
            ConfigHelper::KEY_RETURNS_ENABLED => '1',
            ConfigHelper::KEY_RETURNS_ALLOW_UNREGISTERED => '1',
            ConfigHelper::KEY_RETURNS_WINDOW_DAYS => '30',
            ConfigHelper::KEY_RETURNS_EXCLUDED_CATEGORIES => (string) json_encode(['10' => 10, '20' => 20, '30' => 30]),
            ConfigHelper::KEY_RETURNS_EXCLUDE_VIRTUAL => '1',
            ConfigHelper::KEY_RETURNS_MAX_ITEM_VALUE => '99.90',
            ConfigHelper::KEY_RETURNS_MAX_ITEM_WEIGHT => '5',
            // ticked 3 and 5, unticked 9 (AbstractFormService stores an unticked box as false)
            ConfigHelper::KEY_RETURNS_ALLOWED_GROUPS => (string) json_encode(['3' => '1', '5' => '1', '9' => false]),
            ConfigHelper::KEY_RETURNS_ALLOWED_CARRIERS => (string) json_encode(['7' => '1']),
            ConfigHelper::KEY_RETURNS_ALLOWED_COUNTRIES => (string) json_encode(['CZ' => '1', 'SK' => '1', 'HU' => false]),
            ConfigHelper::KEY_RETURNS_APPROVE_FIRST => '1',
        ]);

        $this->assertTrue($settings->isEnabled());
        $this->assertTrue($settings->allowsUnregistered());
        $this->assertSame(30, $settings->getWindowDays());
        $this->assertSame([10, 20, 30], $settings->getExcludedCategoryIds());
        $this->assertTrue($settings->excludesVirtual());
        $this->assertSame(99.9, $settings->getMaxItemValue());
        $this->assertSame(5.0, $settings->getMaxItemWeight());
        $this->assertSame([3, 5], $settings->getAllowedCustomerGroupIds());
        $this->assertSame([7], $settings->getAllowedCarrierReferences());
        $this->assertSame(['CZ', 'SK'], $settings->getAllowedCountryIsos());
        $this->assertTrue($settings->isApprovalRequired());
    }

    public function testEmptyStringsFallBackToDefaults(): void
    {
        $settings = (new ReturnSettingsFactory())->fromRawValues([
            ConfigHelper::KEY_RETURNS_WINDOW_DAYS => '',
            ConfigHelper::KEY_RETURNS_MAX_ITEM_VALUE => '',
            ConfigHelper::KEY_RETURNS_MAX_ITEM_WEIGHT => '',
            ConfigHelper::KEY_RETURNS_ALLOWED_COUNTRIES => '',
        ]);

        $this->assertSame(14, $settings->getWindowDays());
        $this->assertNull($settings->getMaxItemValue());
        $this->assertNull($settings->getMaxItemWeight());
        $this->assertSame(CarrierTools::COUNTRIES_WITH_INTERNAL_PICKUP_POINTS, $settings->getAllowedCountryIsos());
    }
}
