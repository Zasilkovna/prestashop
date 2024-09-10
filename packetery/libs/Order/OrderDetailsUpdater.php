<?php

namespace Packetery\Order;

use Packetery\Tools\Tools;
use Packetery;

class OrderDetailsUpdater
{
    /** @var Packetery */
    private $module;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @param Packetery $module
     * @param OrderRepository $orderRepository
     */
    public function __construct(Packetery $module, OrderRepository $orderRepository)
    {
        $this->module = $module;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param array $messages
     * @param array|bool|null|object $packeteryOrder
     * @param int $orderId
     * @return void
     * @throws Packetery\Exceptions\DatabaseException
     */
    public function orderUpdate(&$messages, $packeteryOrder, $orderId)
    {
        if (!Tools::isSubmit('order_update')) {
            return;
        }

        if ($packeteryOrder['exported']) {
            return;
        }

        $fieldsToUpdate = [];
        if (! $packeteryOrder['is_ad']) {
            $this->processPickupPointChange($fieldsToUpdate);
        } else {
            $countryDiffersMessage = $this->module->l('The selected delivery address is in a country other than the country of delivery of the order.', 'orderdetailsupdater');
            $this->processAddressChange($messages, $fieldsToUpdate,  $packeteryOrder, $countryDiffersMessage);
            // We need to fetch the Order with a country, so we can compare the change of address and see if it matches the original country selected for delivery.
            $packeteryOrder = $this->orderRepository->getOrderWithCountry($orderId);
        }

        $this->processDimensionsChange($messages, $fieldsToUpdate);
        if ($fieldsToUpdate) {
            foreach ($fieldsToUpdate as &$value) {
                if ((is_int($value) === false) || ($value !== null)) {
                    $value = $this->orderRepository->db->escape($value);
                }
            }
            unset($value);

            $isSuccess = $this->orderRepository->updateByOrder($fieldsToUpdate, $orderId, true);

            if ($isSuccess) {
                $messages[] = [
                    'text' => $this->module->l('Data has been successfully saved', 'orderdetailsupdater'),
                    'class' => 'success',
                ];
            } else {
                $messages[] = [
                    'text' => $this->module->l('Address could not be changed.', 'orderdetailsupdater'),
                    'class' => 'danger',
                ];
            }
        }

        if ((bool)$packeteryOrder['is_ad'] === false && $packeteryOrder['id_branch'] === null) {
            $messages[] = [
                'text' => $this->module->l(
                    'No pickup point selected for the order. It will not be possible to export the order to Packeta.', 'orderdetailsupdater'
                ),
                'class' => 'danger',
            ];
        }
    }

    /**
     * @param array $messages
     * @param array $fieldsToUpdate
     * @return void
     */
    public function processDimensionsChange(&$messages, array &$fieldsToUpdate)
    {
        $packageDimensions = [];
        $invalidFields = [];

        $translatedDimensions = [
            'length' => $this->module->l('length', 'orderdetailsupdater'),
            'height' => $this->module->l('height', 'orderdetailsupdater'),
            'width' => $this->module->l('width', 'orderdetailsupdater'),
        ];

        foreach ($translatedDimensions as $dimension => $translation) {
            $rawValue = Tools::getValue($dimension);

            $value = null;
            if ((string)(int)$rawValue === $rawValue) {
                $value = (int)$rawValue;
                $isValid = $value > 0;

            } elseif ($rawValue === '') {
                $isValid = true;

            } else {
                $isValid = false;
            }

            if ($isValid) {
                $packageDimensions[$dimension] = $value;
            } else {
                $invalidFields[] = $translation;
            }
        }

        if ($invalidFields !== []) {
            $fieldNamesList = implode(', ', $invalidFields);
            $messages[] = [
                'text' => sprintf(
                    $this->module->l('%s must be a number, greater than 0.', 'orderdetailsupdater'),
                    ucfirst($fieldNamesList)
                ),
                'class' => 'danger',
            ];

            return;
        }

        $fieldsToUpdate = array_merge($fieldsToUpdate, [
            'length' => $packageDimensions['length'],
            'height' => $packageDimensions['height'],
            'width' => $packageDimensions['width'],
        ]);
    }


    /**
     * @param array $fieldsToUpdate
     * @return void
     */
    public function processPickupPointChange(array &$fieldsToUpdate)
    {
        if (
            !Tools::getIsset('pickup_point') ||
            Tools::getValue('pickup_point') === ''
        ) {
            return;
        }

        $pickupPoint = json_decode(Tools::getValue('pickup_point'), false);

        if (!$pickupPoint) {
            return;
        }

        $fieldsToUpdate = array_merge($fieldsToUpdate, [
            'id_branch' => (int)$pickupPoint->id,
            'name_branch' => $pickupPoint->name,
            'currency_branch' => $pickupPoint->currency,
        ]);

        if ($pickupPoint->pickupPointType === 'external') {
            $fieldsToUpdate['is_carrier'] = 1;
            $fieldsToUpdate['id_branch'] = (int)$pickupPoint->carrierId;
            $fieldsToUpdate['carrier_pickup_point'] = $pickupPoint->carrierPickupPointId;
        }
    }

    /**
     * @param array  $messages
     * @param array  $fieldsToUpdate
     * @param array  $packeteryOrder
     * @param string $countryDiffersMessage
     * @return void
     */
    public function processAddressChange(array &$messages, array &$fieldsToUpdate, array $packeteryOrder, $countryDiffersMessage)
    {
        if (
            !Tools::getIsset('address') ||
            Tools::getValue('address') === ''
        ) {
            return;
        }

        $address = json_decode(Tools::getValue('address'), false);

        if (!$address) {
            return;
        }

        if ($address->country !== strtolower($packeteryOrder['ps_country'])) {
            $messages[] = [
                'text' => $countryDiffersMessage,
                'class' => 'danger',
            ];
            return;
        }

        $fieldsToUpdate = array_merge($fieldsToUpdate, [
            'is_ad' => 1,
            'country' => $address->country,
            'county' => $address->county,
            'zip' => $address->postcode,
            'city' => $address->city,
            'street' => $address->street,
            'house_number' => $address->houseNumber,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
        ]);
    }
}
