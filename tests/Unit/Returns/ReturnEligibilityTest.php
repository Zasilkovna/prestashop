<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Returns;

use Packetery\Returns\ReturnEligibility;
use Packetery\Returns\ReturnOrderContext;
use Packetery\Returns\ReturnOrderItem;
use Packetery\Returns\ReturnSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReturnEligibilityTest extends TestCase
{
    #[DataProvider('evaluateProvider')]
    public function testEvaluate(
        ReturnSettings $settings,
        ReturnOrderContext $context,
        bool $expectedReturnable
    ): void {
        $this->assertSame($expectedReturnable, (new ReturnEligibility())->evaluate($settings, $context));
    }

    /**
     * @return array<string, array{ReturnSettings, ReturnOrderContext, bool}>
     */
    public static function evaluateProvider(): array
    {
        return [
            'returnable: everything within limits' => [
                self::settings(),
                self::context(),
                true,
            ],
            'not returnable: service disabled' => [
                self::settings(enabled: false),
                self::context(),
                false,
            ],
            'not returnable: unregistered customer, unregistered not allowed' => [
                self::settings(allowUnregistered: false),
                self::context(customerRegistered: false),
                false,
            ],
            'returnable: unregistered customer allowed' => [
                self::settings(allowUnregistered: true),
                self::context(customerRegistered: false),
                true,
            ],
            'not returnable: past the return window' => [
                self::settings(windowDays: 14),
                self::context(daysSinceDelivery: 15),
                false,
            ],
            'returnable: unknown delivery date does not block' => [
                self::settings(windowDays: 14),
                self::context(daysSinceDelivery: null),
                true,
            ],
            'not returnable: delivery country not allowed' => [
                self::settings(),
                self::context(deliveryCountryIso: 'DE'),
                false,
            ],
            'returnable: country matched case-insensitively' => [
                self::settings(),
                self::context(deliveryCountryIso: 'cz'),
                true,
            ],
            'not returnable: delivery country missing' => [
                self::settings(),
                self::context(deliveryCountryIso: null),
                false,
            ],
            'not returnable: customer group not in whitelist' => [
                self::settings(allowedCustomerGroupIds: [9]),
                self::context(customerGroupIds: [3]),
                false,
            ],
            'returnable: empty group whitelist allows all' => [
                self::settings(allowedCustomerGroupIds: []),
                self::context(customerGroupIds: [3]),
                true,
            ],
            'not returnable: carrier not in whitelist' => [
                self::settings(allowedCarrierReferences: [99]),
                self::context(carrierReference: 5),
                false,
            ],
            'not returnable: order contains an excluded-category item' => [
                self::settings(excludedCategoryIds: [77]),
                self::context(items: [self::item(categoryIds: [10]), self::item(categoryIds: [77])]),
                false,
            ],
            'not returnable: order contains a virtual item and virtual excluded' => [
                self::settings(excludeVirtual: true),
                self::context(items: [self::item(), self::item(virtual: true)]),
                false,
            ],
            'returnable: virtual item present but virtual not excluded' => [
                self::settings(excludeVirtual: false),
                self::context(items: [self::item(virtual: true)]),
                true,
            ],
            'not returnable: order total value over the cap (quantity counts)' => [
                self::settings(maxItemValue: 100.0),
                self::context(items: [self::item(value: 40.0, quantity: 3)]), // 120 > 100
                false,
            ],
            'returnable: order total value within the cap across lines and quantities' => [
                self::settings(maxItemValue: 100.0),
                self::context(items: [
                    self::item(value: 20.0, quantity: 2), // 40
                    self::item(value: 25.0, quantity: 2), // 50
                ]), // total 90 <= 100
                true,
            ],
            'not returnable: order total weight over the cap (quantity counts)' => [
                self::settings(maxItemWeight: 5.0),
                self::context(items: [self::item(weight: 2.0, quantity: 3)]), // 6 > 5
                false,
            ],
        ];
    }

    private static function settings(
        bool $enabled = true,
        bool $allowUnregistered = true,
        int $windowDays = 14,
        array $excludedCategoryIds = [],
        bool $excludeVirtual = false,
        ?float $maxItemValue = null,
        ?float $maxItemWeight = null,
        array $allowedCustomerGroupIds = [],
        array $allowedCarrierReferences = [],
        array $allowedCountryIsos = ['CZ', 'SK', 'HU', 'RO'],
        bool $approvalRequired = false
    ): ReturnSettings {
        return new ReturnSettings(
            $enabled,
            $allowUnregistered,
            $windowDays,
            $excludedCategoryIds,
            $excludeVirtual,
            $maxItemValue,
            $maxItemWeight,
            $allowedCustomerGroupIds,
            $allowedCarrierReferences,
            $allowedCountryIsos,
            $approvalRequired
        );
    }

    /**
     * @param ReturnOrderItem[]|null $items
     */
    private static function context(
        bool $customerRegistered = true,
        ?int $daysSinceDelivery = 1,
        ?string $deliveryCountryIso = 'CZ',
        array $customerGroupIds = [3],
        ?int $carrierReference = 5,
        ?array $items = null
    ): ReturnOrderContext {
        return new ReturnOrderContext(
            $customerRegistered,
            $daysSinceDelivery,
            $deliveryCountryIso,
            $customerGroupIds,
            $carrierReference,
            $items ?? [self::item()]
        );
    }

    private static function item(
        float $value = 20.0,
        float $weight = 1.0,
        int $quantity = 1,
        array $categoryIds = [10],
        bool $virtual = false
    ): ReturnOrderItem {
        return new ReturnOrderItem($value, $weight, $quantity, $categoryIds, $virtual);
    }
}
