<?php

namespace Packetery\Exceptions;

if (!defined('_PS_VERSION_')) {
    exit;
}

class VersionCheckerException extends \Exception
{
    /**
     * @return self
     */
    public static function createForInvalidLatestReleaseResponse()
    {
        return new self('Invalid response from GitHub latest module releases endpoint.');
    }
}
