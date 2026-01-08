<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Cron\Tasks;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\ApiCarrier\Downloader;
use Packetery\Exceptions\DatabaseException;

class DownloadCarriers extends Base
{
    /** @var \Packetery */
    public $module;

    /** @var Downloader */
    private $downloader;

    /**
     * @param \Packetery $module
     * @param Downloader $downloader
     */
    public function __construct(\Packetery $module, Downloader $downloader)
    {
        $this->module = $module;
        $this->downloader = $downloader;
    }

    /**
     * @return string[]
     *
     * @throws DatabaseException
     */
    public function execute()
    {
        $result = $this->downloader->run();
        if ($result['class'] === 'danger') {
            return [$result['text']];
        }

        return [];
    }
}
