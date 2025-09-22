<?php
/**
 * 2017 Zlab Solutions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
 *  @copyright Since 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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

    const EXCEPTION_MESSAGE = 'Param is not a class, extend this method if you need to support other types.';

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
            if (PHP_VERSION_ID >= 70100) {
                $paramType = $param->getType();
                if (!$paramType instanceof \ReflectionNamedType || $paramType->isBuiltin()) {
                    throw new \Exception(self::EXCEPTION_MESSAGE);
                }
                $className = $paramType->getName();
            } else {
                $paramClass = $param->getClass();
                if ($paramClass === null) {
                    throw new \Exception(self::EXCEPTION_MESSAGE);
                }
                $className = $paramClass->name;
            }

            $instances[] = $this->get($className);
        }

        return $instances;
    }
}
