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
namespace Packetery\Order;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\AbstractFormService;
use Packetery\Module\Options;
use Packetery\PacketTracking\PacketStatusFactory;

class OrderStatusChangeFormService extends AbstractFormService
{
    const SUBMIT_ACTION_KEY = 'submitOrderStatusChangeSubmit';

    /** @var \Packetery */
    private $module;

    /** @var PacketStatusFactory */
    private $packetStatusFactory;

    public function __construct(\Packetery $module, PacketStatusFactory $packetStatusFactory, Options $options)
    {
        parent::__construct($options);
        $this->module = $module;
        $this->packetStatusFactory = $packetStatusFactory;
    }

    /**
     * @return string
     */
    public function getSubmitActionKey()
    {
        return self::SUBMIT_ACTION_KEY;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getConfigurationFormFields()
    {
        $orderStatuses = $this->getOrderStates();
        $orderStatusesChoices = [
            [
                'id' => null,
                'name' => $this->module->getTranslator()->trans('Order status', [], 'Modules.Packetery.Orderstatuschangeformservice'),
            ],
        ];
        foreach ($orderStatuses as $orderStatus) {
            $orderStatusesChoices[] = [
                'id' => $orderStatus['id'],
                'name' => $orderStatus['name'],
            ];
        }

        $packetStatusFields = [];
        $packetStatuses = $this->packetStatusFactory->getPacketStatuses();
        foreach ($packetStatuses as $packetStatus) {
            $packetStatusId = $packetStatus->getId();
            $packetStatusFields['PACKETERY_ORDER_STATUS_CHANGE_' . $packetStatusId] = [
                'type' => 'select',
                'label' => $packetStatus->getTranslatedCode(),
                'name' => 'PACKETERY_ORDER_STATUS_CHANGE_' . $packetStatusId,
                'options' => [
                    'query' => $orderStatusesChoices,
                    'id' => 'id',
                    'name' => 'name',
                ],
            ];
        }

        $fields = [];
        $fields['PACKETERY_ORDER_STATUS_CHANGE_ENABLED'] = [
            'type' => 'radio',
            'size' => 2,
            'label' => $this->module->getTranslator()->trans('Enabled', [], 'Modules.Packetery.Orderstatuschangeformservice'),
            'name' => 'PACKETERY_ORDER_STATUS_CHANGE_ENABLED',
            'values' => [
                [
                    'id' => 1,
                    'value' => 1,
                    'label' => $this->module->getTranslator()->trans('Yes', [], 'Modules.Packetery.Orderstatuschangeformservice'),
                ],
                [
                    'id' => 0,
                    'value' => 0,
                    'label' => $this->module->getTranslator()->trans('No', [], 'Modules.Packetery.Orderstatuschangeformservice'),
                ],
            ],
            'title' => $this->module->getTranslator()->trans('Enabled', [], 'Modules.Packetery.Orderstatuschangeformservice'),
            'required' => false,
            'defaultValue' => 0,
        ];

        return array_merge($fields, $packetStatusFields);
    }
}
