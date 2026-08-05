<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Returns;

use Packetery\Returns\PendingReturnsNotifier;
use Packetery\Returns\ReturnRepository;
use PHPUnit\Framework\TestCase;

class PendingReturnsNotifierTest extends TestCase
{
    public function testReturnsNullWhenNoReturnsAwaitApproval(): void
    {
        $returnRepository = $this->createStub(ReturnRepository::class);
        $returnRepository->method('countPending')->willReturn(0);

        $this->assertNull((new PendingReturnsNotifier($returnRepository))->getNotice('AdminOrders'));
    }

    public function testNoticeCarriesPendingCountAndLinkOnOtherPages(): void
    {
        $returnRepository = $this->createStub(ReturnRepository::class);
        $returnRepository->method('countPending')->willReturn(3);

        $notice = (new PendingReturnsNotifier($returnRepository))->getNotice('AdminOrders');

        $this->assertNotNull($notice);
        $this->assertSame(3, $notice->getPendingCount());
        $this->assertTrue($notice->hasLink());
    }

    public function testNoticeDropsLinkOnTheReturnsPage(): void
    {
        $returnRepository = $this->createStub(ReturnRepository::class);
        $returnRepository->method('countPending')->willReturn(2);

        $notice = (new PendingReturnsNotifier($returnRepository))->getNotice('PacketeryReturnGrid');

        $this->assertNotNull($notice);
        $this->assertSame(2, $notice->getPendingCount());
        $this->assertFalse($notice->hasLink());
    }
}
