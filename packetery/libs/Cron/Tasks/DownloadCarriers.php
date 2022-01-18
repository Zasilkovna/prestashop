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
     * DownloadCarriers constructor.
     * @param Packetery $module
     * @throws \ReflectionException
     */
    public function __construct(Packetery $module)
    {
        $this->module = $module;
        $this->downloader = $this->module->diContainer->get(Downloader::class);
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
