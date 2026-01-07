<?php

namespace Packetery\Cron\Tasks;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Exceptions\DatabaseException;
use Packetery\Log\LogRepository;
use Packetery\Tools\Tools;

class PurgeLogs extends Base
{
    public const DEFAULT_LOG_EXPIRATION_DAYS = 30;

    /** @var \Packetery */
    public $module;

    /** @var LogRepository */
    private $logRepository;

    /**
     * @param \Packetery $module
     * @param LogRepository $logRepository
     */
    public function __construct(\Packetery $module, LogRepository $logRepository)
    {
        $this->module = $module;
        $this->logRepository = $logRepository;
    }

    /**
     * @return string[]
     */
    public function execute()
    {
        $logExpirationDays = (int) Tools::getValue('log_expiration_days', self::DEFAULT_LOG_EXPIRATION_DAYS);
        try {
            $this->logRepository->purge($logExpirationDays);
        } catch (DatabaseException $e) {
            return [$e->getMessage()];
        }

        return [];
    }
}
