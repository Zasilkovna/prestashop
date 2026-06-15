<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\DI;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Container
{
    /** @var array */
    private $factories;
    /** @var array */
    private $services;

    public const EXCEPTION_MESSAGE = 'Param is not a class, extend this method if you need to support other types.';

    /**
     * @param string $class
     *
     * @return mixed|object
     *
     * @throws \ReflectionException
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
     * @param \Closure $factory
     */
    public function register($class, \Closure $factory)
    {
        $this->factories[$class] = $factory;
    }

    /**
     * @param string $class
     *
     * @return object
     *
     * @throws \ReflectionException
     */
    private function create($class)
    {
        if (isset($this->factories[$class])) {
            return $this->factories[$class]();
        }

        $reflection = new \ReflectionClass($class);
        $paramInstances = $this->getParamInstances($reflection);

        return $reflection->newInstanceArgs($paramInstances);
    }

    /**
     * @param \ReflectionClass $reflection
     *
     * @return array
     *
     * @throws \Exception
     */
    private function getParamInstances(\ReflectionClass $reflection)
    {
        $constructorReflection = $reflection->getConstructor();
        if ($constructorReflection === null) {
            return [];
        }

        $instances = [];
        $params = $constructorReflection->getParameters();
        foreach ($params as $param) {
            $paramType = $param->getType();
            if (!$paramType instanceof \ReflectionNamedType || $paramType->isBuiltin()) {
                throw new \Exception(self::EXCEPTION_MESSAGE);
            }
            $className = $paramType->getName();

            $instances[] = $this->get($className);
        }

        return $instances;
    }
}
