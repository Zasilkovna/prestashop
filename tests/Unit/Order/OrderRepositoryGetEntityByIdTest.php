<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Order;

use Packetery\Order\OrderRepository;
use Packetery\Tools\DbTools;
use PHPUnit\Framework\TestCase;

class OrderRepositoryGetEntityByIdTest extends TestCase
{
    public function testReturnsEntityWhenRowFound(): void
    {
        $dbTools = $this->createStub(DbTools::class);
        $dbTools->method('getRow')->willReturn([
            'id_branch' => '10',
            'name_branch' => 'Praha 1',
            'id_carrier' => '3',
            'is_cod' => '1',
            'is_ad' => '0',
            'currency_branch' => 'CZK',
            'is_carrier' => '0',
            'carrier_pickup_point' => 'PP-1',
            'tracking_number' => 'Z1',
            'weight' => '1.500000',
            'length' => '10',
            'height' => '5',
            'width' => '7',
            'zip' => '11000',
            'city' => 'Praha',
            'street' => 'Hlavni',
            'house_number' => '1',
        ]);

        $repository = new OrderRepository(new \Db(), $dbTools);

        $order = $repository->getEntityById(42);

        $this->assertNotNull($order);
        $this->assertSame(10, $order->getIdBranch());
        $this->assertSame('Praha 1', $order->getNameBranch());
    }

    public function testReturnsNullWhenRowMissing(): void
    {
        $dbTools = $this->createStub(DbTools::class);
        $dbTools->method('getRow')->willReturn(false);

        $repository = new OrderRepository(new \Db(), $dbTools);

        $this->assertNull($repository->getEntityById(999));
    }
}
