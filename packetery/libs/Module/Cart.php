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

namespace Packetery\Module;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery;

class Cart
{
    /** Endpoint is called in PS 1.6 only. PS 1.6 does not have hook for carrier extra content.
     *
     * @return string
     *
     * @throws Packetery\Exceptions\DatabaseException
     * @throws \ReflectionException
     * @throws \SmartyException
     */
    public function packeteryCreateExtraContent()
    {
        $carrierId = \Tools::getValue('prestashop_carrier_id');

        $packetery = new \Packetery();
        $params = [
            'packetery' => [
                // TODO: fix address validation in PS 1.6
                'template' => 'views/templates/front/carrier-extra-content.tpl',
            ],
            'carrier' => [
                'id' => $carrierId,
            ],
            'cart' => \Context::getContext()->cart,
        ];

        return $packetery->hookDisplayCarrierExtraContent($params);
    }
}
