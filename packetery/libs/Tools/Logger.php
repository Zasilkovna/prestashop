<?php

/**
 * 2017 Zlab Solutions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
 *  @copyright Since 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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
