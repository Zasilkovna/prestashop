<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Tests\Unit\Cron;

use Packetery\Cron\CronRunLog;
use Packetery\Cron\Tasks\DeleteLabels;
use Packetery\Cron\Tasks\DownloadCarriers;
use Packetery\Cron\Tasks\GetConsignPassword;
use Packetery\Cron\Tasks\PurgeLogs;
use Packetery\Cron\Tasks\UpdatePacketStatus;
use Packetery\Tools\ConfigHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CronRunLogTest extends TestCase
{
    private const LAST_RUN = 1760000000;

    protected function tearDown(): void
    {
        \Configuration::reset();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function loggedTaskProvider(): array
    {
        return [
            'carrier list update' => [DownloadCarriers::getTaskName()],
            'packet status update' => [UpdatePacketStatus::getTaskName()],
            'consignment code retrieval' => [GetConsignPassword::getTaskName()],
        ];
    }

    #[DataProvider('loggedTaskProvider')]
    public function testTaskThatDidNotRunHasNoLastRun(string $taskName): void
    {
        $this->assertNull($this->getLastRun(new CronRunLog(), $taskName));
    }

    #[DataProvider('loggedTaskProvider')]
    public function testRecordedRunIsReadBack(string $taskName): void
    {
        $cronRunLog = new CronRunLog();
        $before = time();

        $cronRunLog->record($taskName);

        $lastRun = $this->getLastRun($cronRunLog, $taskName);
        $this->assertIsInt($lastRun);
        $this->assertGreaterThanOrEqual($before, $lastRun);
        $this->assertLessThanOrEqual(time(), $lastRun);
    }

    #[DataProvider('loggedTaskProvider')]
    public function testRecordedRunDoesNotChangeOtherTasks(string $taskName): void
    {
        $cronRunLog = new CronRunLog();

        $cronRunLog->record($taskName);

        foreach (self::loggedTaskProvider() as [$otherTaskName]) {
            if ($otherTaskName === $taskName) {
                continue;
            }
            $this->assertNull($this->getLastRun($cronRunLog, $otherTaskName));
        }
    }

    public function testStoredValueIsReadAsTimestamp(): void
    {
        \Configuration::set(ConfigHelper::KEY_CRON_LAST_RUN_DOWNLOAD_CARRIERS, (string) self::LAST_RUN);

        $this->assertSame(self::LAST_RUN, $this->getLastRun(new CronRunLog(), DownloadCarriers::getTaskName()));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function ignoredTaskProvider(): array
    {
        return [
            'label deletion' => [DeleteLabels::getTaskName()],
            'log purge' => [PurgeLogs::getTaskName()],
            'unknown task' => ['UnknownTask'],
            'empty task' => [''],
            'array from request' => [[DownloadCarriers::getTaskName()]],
        ];
    }

    #[DataProvider('ignoredTaskProvider')]
    public function testTaskWithoutConfigKeyStoresNothing(mixed $taskName): void
    {
        $cronRunLog = new CronRunLog();

        $cronRunLog->record($taskName);

        foreach (self::loggedTaskProvider() as [$loggedTaskName]) {
            $this->assertNull($this->getLastRun($cronRunLog, $loggedTaskName));
        }
    }

    public function testNonNumericStoredValueHasNoLastRun(): void
    {
        \Configuration::set(ConfigHelper::KEY_CRON_LAST_RUN_DOWNLOAD_CARRIERS, 'invalid');

        $this->assertNull($this->getLastRun(new CronRunLog(), DownloadCarriers::getTaskName()));
    }

    private function getLastRun(CronRunLog $cronRunLog, string $taskName): ?int
    {
        $lastRuns = $cronRunLog->getLastRuns();
        $this->assertArrayHasKey($taskName, $lastRuns);

        return $lastRuns[$taskName];
    }
}
