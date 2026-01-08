<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

class MessageManager
{
    public const PREFIX = 'packetery_';

    /**
     * We are working with one message, Cookie does not allow arrays.
     *
     * @param string $errorLevel
     * @param string $message
     *
     * @return void
     */
    public function setMessage($errorLevel, $message)
    {
        list($context, $key) = $this->getContextAndKey($errorLevel);
        $context->cookie->__set($key, $message);
    }

    /**
     * @param string $errorLevel
     *
     * @return string|false
     */
    public function getMessageClean($errorLevel)
    {
        list($context, $key) = $this->getContextAndKey($errorLevel);
        $message = $context->cookie->__get($key);
        $context->cookie->__unset($key);

        return $message;
    }

    /**
     * @param string $errorLevel
     *
     * @return array
     */
    private function getContextAndKey($errorLevel)
    {
        $context = \Context::getContext();
        $key = self::PREFIX . $errorLevel;

        return [$context, $key];
    }
}
