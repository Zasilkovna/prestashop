<?php

namespace Packetery\Cron\Tasks;

if (!defined('_PS_VERSION_')) {
    exit;
}

abstract class Base
{
    /**
     * @return string
     */
    public static function getTaskName()
    {
        $reflectionClass = new \ReflectionClass(static::class);

        return $reflectionClass->getShortName();
    }
}
