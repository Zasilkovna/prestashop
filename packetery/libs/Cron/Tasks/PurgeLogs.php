<?php

namespace Packetery\Cron\Tasks;

use Packetery;
use Packetery\Exceptions\DatabaseException;
use Packetery\Log\LogRepository;
use Packetery\Tools\Tools;

class PurgeLogs extends Base
{
    const DEFAULT_LOG_EXPIRATION_DAYS = 30;

    /** @var Packetery */
    public $module;

    /** @var LogRepository */
    private $logRepository;

    /**
     * DownloadCarriers constructor.
     * @param Packetery $module
     * @throws \ReflectionException
     */
    public function __construct(Packetery $module)
    {
        $this->module = $module;
        $this->logRepository = $this->module->diContainer->get(LogRepository::class);
    }

    /**
     * @return string[]
     */
    public function execute()
    {
        $logExpirationDays = (int)Tools::getValue('log_expiration_days', self::DEFAULT_LOG_EXPIRATION_DAYS);
        try {
            $this->logRepository->purge($logExpirationDays);
        } catch (DatabaseException $e) {
            return [$e->getMessage()];
        }

        return [];
    }
}
