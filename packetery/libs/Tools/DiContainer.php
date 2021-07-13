<?php

namespace Packetery\Tools;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionException;

class DiContainer
{
    private $factories;
    private $services;

    /**
     * @param string $class
     * @return mixed|object
     * @throws ReflectionException
     */
    public function get($class)
    {
        if (!isset($this->services[$class])) {
            $this->services[$class] = $this->create($class);
        }
        return $this->services[$class];
    }

    /**
     * @param string $class
     * @param Closure $factory
     */
    public function register($class, Closure $factory)
    {
        $this->factories[$class] = $factory;
    }

    /**
     * @param string $class
     * @return object
     * @throws ReflectionException
     */
    private function create($class)
    {
        if (isset($this->factories[$class])) {
            return $this->factories[$class]();
        }

        $reflection = new ReflectionClass($class);
        $paramInstances = $this->getParamInstances($reflection);

        return $reflection->newInstanceArgs($paramInstances);
    }

    /**
     * @param ReflectionClass $reflection
     * @return array
     * @throws Exception
     */
    private function getParamInstances(ReflectionClass $reflection)
    {
        $constructorReflection = $reflection->getConstructor();
        if ($constructorReflection === null) {
            return [];
        }

        $instances = [];
        $params = $constructorReflection->getParameters();
        foreach ($params as $param) {
            $paramClass = $param->getClass();
            if ($paramClass === null) {
                throw new Exception('Param is not a class, extend this method if you need to support other types.');
            }
            $instances[] = $this->get($paramClass->name);
        }

        return $instances;
    }
}
