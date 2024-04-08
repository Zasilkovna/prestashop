<?php

namespace Packetery\Order;

use Packetery\Tools\Tools;
use Packetery;

class OrderDetails
{
    /** @var Packetery */
    private $module;

    public function __construct(Packetery $module)
    {
        $this->module = $module;
    }

    public function processDimensionsChange(&$messages, array &$fieldsToUpdate)
    {
        $packageDimensions = [];
        $invalidFields = [];

        $size = ['length', 'height', 'width'];
        foreach ($size as $dimension) {
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
                $invalidFields[] = $dimension;
            }
        }

        if ($invalidFields !== []) {
            $translatedFieldNames = array_map(function ($fieldName) {
                return $this->module->l($fieldName, 'detailsform');
            }, $invalidFields);

            $fieldNamesList = implode(', ', $translatedFieldNames);

            $messages[] = [
                'text' => sprintf(
                    $this->module->l('%s must be a number, greater than 0.', 'detailsform'),
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
