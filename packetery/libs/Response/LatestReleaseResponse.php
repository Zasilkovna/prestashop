<?php

namespace Packetery\Response;

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
     * @var string
     */
    private $releaseNotes;

    /**
     * @param string $version
     * @param string $downloadUrl
     * @param string $releaseNotes
     */
    public function __construct($version, $downloadUrl, $releaseNotes)
    {
        $this->version = $version;
        $this->downloadUrl = $downloadUrl;
        $this->releaseNotes = $releaseNotes;
    }

    public function getReleaseNotes(): string
    {
        return $this->releaseNotes;
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
