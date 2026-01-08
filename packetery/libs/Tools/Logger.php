<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Logger
{
    /** @var string */
    private $errorLogFilePath;

    public function __construct()
    {
        $this->errorLogFilePath = __DIR__ . '/../../packetery_error.log';
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public function logToFile($message)
    {
        if (
            (file_exists($this->errorLogFilePath) && !is_writable($this->errorLogFilePath))
            || (!file_exists($this->errorLogFilePath) && !is_writable(dirname($this->errorLogFilePath)))
        ) {
            return false;
        }

        $fileHandle = fopen($this->errorLogFilePath, 'ab');
        if (!$fileHandle) {
            return false;
        }
        $result = fwrite($fileHandle, date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL);
        if ($result === false) {
            return false;
        }
        fclose($fileHandle);

        return true;
    }
}
