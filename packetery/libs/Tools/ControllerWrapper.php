<?php

namespace Packetery\Tools;

class ControllerWrapper
{
    /** @var \ControllerCore */
    private $controller;

    public function __construct(\ControllerCore $controller) {
        $this->controller = $controller;
    }

    /**
     * @param $name
     * @param $uri
     * @param array $params
     */
    public function addJavascript($name, $uri, array $params) {
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
    public function registerStylesheet($name, $uri, array $params) {
        if (Tools::version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
            $this->controller->addCSS($uri);
        } else {
            $this->controller->registerStylesheet($name, $uri, $params);
        }
    }
}
