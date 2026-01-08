<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ControllerWrapper
{
    /** @var \ControllerCore */
    private $controller;

    public function __construct(\ControllerCore $controller)
    {
        $this->controller = $controller;
    }

    /**
     * @param $name
     * @param $uri
     * @param array $params
     */
    public function registerJavascript($name, $uri, array $params)
    {
        if (Tools::version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
            $this->controller->addJS($uri);
        } else {
            $this->controller->registerJavascript($name, $uri, $params);
        }
    }

    /**
     * @param $name
     * @param $uri
     * @param array $params
     */
    public function registerStylesheet($name, $uri, array $params)
    {
        if (Tools::version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
            $this->controller->addCSS($uri, 'all', null, false);
        } else {
            $this->controller->registerStylesheet($name, $uri, $params);
        }
    }
}
