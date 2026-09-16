<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\PacketTracking;

use Packetery\PacketTracking\PacketTrackingRepository;
use Packetery\Tools\DbTools;
use PHPUnit\Framework\TestCase;

class PacketTrackingRepositoryInsertTest extends TestCase
{
    public function testStatusTextIsEscaped(): void
    {
        $dbTools = $this->createMock(DbTools::class);
        $dbTools->db = $this->createMock(\Db::class);
        $dbTools->db
            ->expects($this->once())
            ->method('escape')
            // html_ok = true; without it Db::escape also runs strip_tags(), the stored text would
            // differ from the API one and PacketStatusComparator would report a change every run
            ->with("A'postrophe Gherea", true)
            ->willReturn("A\\'postrophe Gherea");
        $dbTools
            ->expects($this->once())
            ->method('insert')
            ->with(
                PacketTrackingRepository::$tableName,
                $this->callback(static function (array $data): bool {
                    return $data['status_text'] === "A\\'postrophe Gherea";
                })
            )
            ->willReturn(true);

        $repository = new PacketTrackingRepository($dbTools);

        $repository->insert(4877, 'Z1234567890', '2026-09-10 10:00:00', 3, "A'postrophe Gherea");
    }
}
