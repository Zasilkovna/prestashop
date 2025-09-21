<?php

namespace Packetery\PacketTracking;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\AbstractFormService;
use Packetery\Module\Options;

class PacketStatusTrackingFormService extends AbstractFormService
{
    const SUBMIT_ACTION_KEY = 'submitPacketStatusTrackingSubmit';

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
        return [
            'PACKETERY_PACKET_STATUS_TRACKING_ENABLED' => [
                'type' => 'radio',
                'size' => 2,
                'label' => $this->module->getTranslator()->trans('Enabled', [], 'Modules.Packetery.Packetstatustrackingformservice'),
                'name' => 'PACKETERY_PACKET_STATUS_TRACKING_ENABLED',
                'values' => [
                    [
                        'id' => 1,
                        'value' => 1,
                        'label' => $this->module->getTranslator()->trans('Yes', [], 'Modules.Packetery.Packetstatustrackingformservice'),
                    ],
                    [
                        'id' => 0,
                        'value' => 0,
                        'label' => $this->module->getTranslator()->trans('No', [], 'Modules.Packetery.Packetstatustrackingformservice'),
                    ],
                ],
                'title' => $this->module->getTranslator()->trans('Enabled', [], 'Modules.Packetery.Packetstatustrackingformservice'),
                'required' => false,
                'defaultValue' => 0,
            ],
            'PACKETERY_PACKET_STATUS_TRACKING_MAX_PROCESSED_ORDERS' => [
                'type' => 'text',
                'label' => $this->module->getTranslator()->trans('Max processed orders', [], 'Modules.Packetery.Packetstatustrackingformservice'),
                'name' => 'PACKETERY_PACKET_STATUS_TRACKING_MAX_PROCESSED_ORDERS',
                'required' => true,
                'defaultValue' => '100',
                'validation' => 'isInt',
                'cast' => 'intval',
            ],
            'PACKETERY_PACKET_STATUS_TRACKING_MAX_ORDER_AGE_DAYS' => [
                'type' => 'text',
                'label' => $this->module->getTranslator()->trans('Max order age in days', [], 'Modules.Packetery.Packetstatustrackingformservice'),
                'name' => 'PACKETERY_PACKET_STATUS_TRACKING_MAX_ORDER_AGE_DAYS',
                'required' => true,
                'defaultValue' => '14',
                'validation' => 'isInt',
                'cast' => 'intval',
            ],
            'PACKETERY_PACKET_STATUS_TRACKING_ORDER_STATES' => [
                'type' => 'checkbox',
                'label' => $this->module->getTranslator()->trans('Order statuses', [], 'Modules.Packetery.Packetstatustrackingformservice'),
                'name' => 'PACKETERY_PACKET_STATUS_TRACKING_ORDER_STATES',
                'multiple' => true,
                'values' => [
                    'query' => $this->getOrderStates(),
                    'id' => 'id',
                    'name' => 'name',
                ],
            ],
            'PACKETERY_PACKET_STATUS_TRACKING_PACKET_STATUSES' => [
                'type' => 'checkbox',
                'label' => $this->module->getTranslator()->trans('Packet statuses', [], 'Modules.Packetery.Packetstatustrackingformservice'),
                'name' => 'PACKETERY_PACKET_STATUS_TRACKING_PACKET_STATUSES',
                'multiple' => true,
                'values' => [
                    'query' => $this->getPacketStatusChoices(),
                    'id' => 'id',
                    'name' => 'name',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getPacketStatusChoices()
    {
        $result = [];

        foreach ($this->packetStatusFactory->getPacketStatuses() as $packetStatus) {
            if ($packetStatus->isFinal() === true) {
                continue;
            }

            $result[] = [
                'id' => $packetStatus->getId(),
                'name' => $packetStatus->getTranslatedCode(),
            ];
        }

        return $result;
    }
}
