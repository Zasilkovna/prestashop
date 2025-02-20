<?php

namespace Packetery\Cron\Tasks;

use Packetery;
use Packetery\Exceptions\DatabaseException;
use Packetery\ApiCarrier\Downloader;

class DownloadCarriers extends Base
{
    /** @var Packetery */
    public $module;

    /** @var Downloader */
    private $downloader;

    /**
     * @param Packetery $module
     * @param Downloader $downloader
     */
    public function __construct(Packetery $module, Downloader $downloader)
    {
        $this->module = $module;
        $this->downloader = $downloader;
    }

    /**
     * @return string[]
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
