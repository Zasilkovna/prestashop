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

namespace Packetery\Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

class MessageManager
{
    const PREFIX = 'packetery_';

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
