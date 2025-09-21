<?php

namespace Packetery\Response;

if (!defined('_PS_VERSION_')) {
    exit;
}

class LatestReleaseResponse
{
    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $downloadUrl;

    /**
     * @param string $version
     * @param string $downloadUrl
     */
    public function __construct($version, $downloadUrl)
    {
        $this->version = $version;
        $this->downloadUrl = $downloadUrl;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getDownloadUrl()
    {
        return $this->downloadUrl;
    }
}
