<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Order;

use Packetery\Order\OrderEntity;
use PHPUnit\Framework\TestCase;

class OrderEntityTest extends TestCase
{
    public function testFromDbRowCastsStringValuesToTypedProperties(): void
    {
        $order = OrderEntity::fromDbRow([
            'id_branch' => '123',
            'name_branch' => 'Praha 1',
            'id_carrier' => '7',
            'is_cod' => '1',
            'is_ad' => '0',
            'currency_branch' => 'CZK',
            'is_carrier' => '1',
            'carrier_pickup_point' => 'PP-99',
            'tracking_number' => 'Z123456789',
            'weight' => '2.500000',
            'length' => '30',
            'height' => '10',
            'width' => '20',
            'zip' => '11000',
            'city' => 'Praha',
            'street' => 'Hlavni',
            'house_number' => '42',
        ]);

        $this->assertSame(123, $order->getIdBranch());
        $this->assertSame('Praha 1', $order->getNameBranch());
        $this->assertSame(7, $order->getIdCarrier());
        $this->assertTrue($order->isCod());
        $this->assertFalse($order->isAd());
        $this->assertSame('CZK', $order->getCurrencyBranch());
        $this->assertTrue($order->isCarrier());
        $this->assertSame('PP-99', $order->getCarrierPickupPoint());
        $this->assertSame('Z123456789', $order->getTrackingNumber());
        $this->assertSame(2.5, $order->getWeight());
        $this->assertSame(30, $order->getLength());
        $this->assertSame(10, $order->getHeight());
        $this->assertSame(20, $order->getWidth());
        $this->assertSame('11000', $order->getZip());
        $this->assertSame('Praha', $order->getCity());
        $this->assertSame('Hlavni', $order->getStreet());
        $this->assertSame('42', $order->getHouseNumber());
    }

    public function testFromDbRowKeepsNullableColumnsNull(): void
    {
        $order = OrderEntity::fromDbRow([
            'id_branch' => null,
            'name_branch' => null,
            'id_carrier' => '0',
            'is_cod' => '0',
            'is_ad' => '1',
            'currency_branch' => null,
            'is_carrier' => '0',
            'carrier_pickup_point' => null,
            'tracking_number' => null,
            'weight' => null,
            'length' => null,
            'height' => null,
            'width' => null,
            'zip' => null,
            'city' => null,
            'street' => null,
            'house_number' => null,
        ]);

        $this->assertNull($order->getIdBranch());
        $this->assertNull($order->getNameBranch());
        $this->assertSame(0, $order->getIdCarrier());
        $this->assertFalse($order->isCod());
        $this->assertTrue($order->isAd());
        $this->assertNull($order->getCurrencyBranch());
        $this->assertFalse($order->isCarrier());
        $this->assertNull($order->getCarrierPickupPoint());
        $this->assertNull($order->getTrackingNumber());
        $this->assertNull($order->getWeight());
        $this->assertNull($order->getLength());
        $this->assertNull($order->getHeight());
        $this->assertNull($order->getWidth());
        $this->assertNull($order->getZip());
        $this->assertNull($order->getCity());
        $this->assertNull($order->getStreet());
        $this->assertNull($order->getHouseNumber());
    }
}
