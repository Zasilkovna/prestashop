<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Returns;

use Packetery\Returns\ReturnOrderContextFactory;
use PHPUnit\Framework\TestCase;

class ReturnOrderContextFactoryTest extends TestCase
{
    public function testBuildContextMapsScalarsAndItems(): void
    {
        $context = (new ReturnOrderContextFactory())->buildContext(
            true,
            5,
            'cz',
            [1, 3],
            7,
            [
                ['value' => 100.0, 'weight' => 1.5, 'quantity' => 2, 'categoryIds' => [2, 4], 'virtual' => false],
                ['value' => 0.0, 'weight' => 0.0, 'quantity' => 1, 'categoryIds' => [], 'virtual' => true],
            ]
        );

        $this->assertTrue($context->isCustomerRegistered());
        $this->assertSame(5, $context->getDaysSinceDelivery());
        $this->assertSame('cz', $context->getDeliveryCountryIso());
        $this->assertSame([1, 3], $context->getCustomerGroupIds());
        $this->assertSame(7, $context->getCarrierReference());

        $items = $context->getItems();
        $this->assertCount(2, $items);
        $this->assertSame(100.0, $items[0]->getValue());
        $this->assertSame(1.5, $items[0]->getWeight());
        $this->assertSame(2, $items[0]->getQuantity());
        $this->assertSame([2, 4], $items[0]->getCategoryIds());
        $this->assertFalse($items[0]->isVirtual());
        $this->assertTrue($items[1]->isVirtual());
    }

    public function testBuildContextAllowsNullOptionalScalars(): void
    {
        $context = (new ReturnOrderContextFactory())->buildContext(
            false,
            null,
            null,
            [],
            null,
            []
        );

        $this->assertFalse($context->isCustomerRegistered());
        $this->assertNull($context->getDaysSinceDelivery());
        $this->assertNull($context->getDeliveryCountryIso());
        $this->assertSame([], $context->getCustomerGroupIds());
        $this->assertNull($context->getCarrierReference());
        $this->assertSame([], $context->getItems());
    }
}
