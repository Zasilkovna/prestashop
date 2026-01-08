<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\LogWrapper;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PrestashopLogWrapper
{
    public const LEVEL_INFO = 1;
    public const LEVEL_ERROR = 3;

    /**
     * Add a log entry with default parameters
     *
     * @param string $message The log message
     * @param int $severity Log level (1=info, 2=warning, 3=error, 4=debug)
     * @param int|null $errorCode Error code (optional)
     * @param string|null $objectType Object type (optional)
     * @param int|null $objectId Object ID (optional)
     * @param bool $allowDuplicate Allow duplicate entries
     */
    public static function addLog(
        string $message,
        int $severity = self::LEVEL_INFO,
        ?int $errorCode = null,
        ?string $objectType = null,
        ?int $objectId = null,
        bool $allowDuplicate = true
    ): void {
        \PrestaShopLogger::addLog(
            $message,
            $severity,
            $errorCode,
            $objectType,
            $objectId,
            $allowDuplicate
        );
    }

    /**
     * Log an error with exception details
     */
    public static function logException(string $message, \Exception $exception): void
    {
        $fullMessage =
            "{$message} Error: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}";

        self::addLog(
            $fullMessage,
            self::LEVEL_ERROR
        );
    }
}
