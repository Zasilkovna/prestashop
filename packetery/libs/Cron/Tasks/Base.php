<?php

namespace Packetery\Cron\Tasks;

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
