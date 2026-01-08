<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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
