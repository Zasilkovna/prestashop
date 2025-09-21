<?php

declare(strict_types=1);

namespace Packetery\Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Link;
use Packetery;
use Packetery\Exceptions\CheckoutControllerUrlException;

class CheckoutControllerUrlProvider
{
    /** @var Packetery */
    public $module;

    /**
     * @param Packetery $module
     */
    public function __construct(Packetery $module)
    {
        $this->module = $module;
    }

    /**
     * @throws CheckoutControllerUrlException
     */
    private function getUrl(): string
    {
        $context = $this->module->getContext();
        if (!isset($context->link) || !$context->link instanceof Link) {
            throw new CheckoutControllerUrlException('Packetery: property link of Context is not set.');
        }

        $controllerUrl = $context->link->getModuleLink(Packetery::MODULE_SLUG, 'checkout');
        if ($controllerUrl === '') {
            throw new CheckoutControllerUrlException('Packetery: getModuleLink returned empty string.');
        }

        return $controllerUrl;
    }

    /**
     * Retrieves full URL with action parameter, respects friendly URLs and multistore settings.
     *
     * @throws CheckoutControllerUrlException
     */
    public function getPath(): string
    {
        $checkoutControllerPath = $this->getUrl();

        if (strpos($checkoutControllerPath, '?') !== false) {
            $checkoutControllerPath .= '&action=';
        } else {
            $checkoutControllerPath .= '?action=';
        }

        return $checkoutControllerPath;
    }
}
