<?php

namespace Packetery\Cron\Tasks;

use ReflectionClass;

abstract class Base
{
    /**
     * @return string
     */
    public static function getTaskName()
    {
        $reflectionClass = new ReflectionClass(static::class);
        return $reflectionClass->getShortName();
    }
}
