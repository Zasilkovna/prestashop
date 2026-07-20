<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\ApiCarrier;

use Packetery\ApiCarrier\ApiCarrierEntity;
use PHPUnit\Framework\TestCase;

class ApiCarrierEntityTest extends TestCase
{
    public function testFromDbRowCastsStringValuesToTypedProperties(): void
    {
        $carrier = ApiCarrierEntity::fromDbRow([
            'id' => '106',
            'name' => 'CZ Packeta pickup points',
            'currency' => 'CZK',
            'is_pickup_points' => '1',
            'country' => 'cz',
            'disallows_cod' => '0',
            'requires_size' => '1',
        ]);

        $this->assertSame('106', $carrier->getId());
        $this->assertSame('CZ Packeta pickup points', $carrier->getName());
        $this->assertSame('CZK', $carrier->getCurrency());
        $this->assertTrue($carrier->isPickupPoints());
        $this->assertSame('cz', $carrier->getCountry());
        $this->assertFalse($carrier->disallowsCod());
        $this->assertTrue($carrier->requiresSize());
    }
}
