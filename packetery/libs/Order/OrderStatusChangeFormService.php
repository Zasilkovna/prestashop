<?php

namespace Packetery\Order;

use Packetery;
use Packetery\AbstractFormService;
use Packetery\Module\Options;
use Packetery\PacketTracking\PacketStatusFactory;
use Packetery\Tools\UserPermissionHelper;

class OrderStatusChangeFormService extends AbstractFormService
{
    const SUBMIT_ACTION_KEY = 'submitOrderStatusChangeSubmit';

    /** @var Packetery */
    private $module;

    /** @var PacketStatusFactory */
    private $packetStatusFactory;

    public function __construct(Packetery $module, PacketStatusFactory $packetStatusFactory, Options $options, UserPermissionHelper $userPermissionHelper)
    {
        parent::__construct($options, $userPermissionHelper);
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
                'name' => $this->module->l('Order status', 'orderstatuschangeformservice'),
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
            'label' => $this->module->l('Enabled', 'orderstatuschangeformservice'),
            'name' => 'PACKETERY_ORDER_STATUS_CHANGE_ENABLED',
            'values' => [
                [
                    'id' => 1,
                    'value' => 1,
                    'label' => $this->module->l('Yes', 'orderstatuschangeformservice'),
                ],
                [
                    'id' => 0,
                    'value' => 0,
                    'label' => $this->module->l('No', 'orderstatuschangeformservice'),
                ],
            ],
            'title' => $this->module->l('Enabled', 'orderstatuschangeformservice'),
            'required' => false,
            'defaultValue' => 0,
        ];

        return array_merge($fields, $packetStatusFields);
    }
}
