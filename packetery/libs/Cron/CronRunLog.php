<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Cron;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Cron\Tasks\DownloadCarriers;
use Packetery\Cron\Tasks\GetConsignPassword;
use Packetery\Cron\Tasks\UpdatePacketStatus;
use Packetery\Tools\ConfigHelper;

class CronRunLog
{
    /**
     * Ignores unknown tasks, the name comes from a public request
     *
     * @param mixed $taskName
     */
    public function record($taskName): void
    {
        $configKey = $this->getConfigKey($taskName);
        if ($configKey === null) {
            return;
        }

        ConfigHelper::update($configKey, time());
    }

    /**
     * @return array<string, int|null> last run by task name
     */
    public function getLastRuns(): array
    {
        $lastRuns = [];
        foreach ($this->getConfigKeys() as $taskName => $configKey) {
            $value = ConfigHelper::get($configKey);
            $lastRuns[$taskName] = is_numeric($value) ? (int) $value : null;
        }

        return $lastRuns;
    }

    /**
     * @param mixed $taskName
     */
    private function getConfigKey($taskName): ?string
    {
        $configKeys = $this->getConfigKeys();
        if (!is_string($taskName) || !isset($configKeys[$taskName])) {
            return null;
        }

        return $configKeys[$taskName];
    }

    /**
     * @return array<string, string> config key by task name
     */
    private function getConfigKeys(): array
    {
        return [
            DownloadCarriers::getTaskName() => ConfigHelper::KEY_CRON_LAST_RUN_DOWNLOAD_CARRIERS,
            UpdatePacketStatus::getTaskName() => ConfigHelper::KEY_CRON_LAST_RUN_UPDATE_PACKET_STATUS,
            GetConsignPassword::getTaskName() => ConfigHelper::KEY_CRON_LAST_RUN_GET_CONSIGN_PASSWORD,
        ];
    }
}
