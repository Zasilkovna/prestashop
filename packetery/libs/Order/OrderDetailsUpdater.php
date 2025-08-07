<?php

namespace Packetery\Order;

use Packetery;
use Packetery\Carrier\CarrierTools;
use Packetery\Tools\Tools;
use Packetery\Tools\UserPermissionHelper;

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
     * @return array|bool|null|object
     * @throws Packetery\Exceptions\DatabaseException
     */
    public function orderUpdate(&$messages, $packeteryOrder, $orderId)
    {
        if (!Tools::isSubmit('order_update')) {
            return $packeteryOrder;
        }

        if (!UserPermissionHelper::hasPermission(UserPermissionHelper::SECTION_ORDERS, UserPermissionHelper::PERMISSION_EDIT)) {
            $messages[] = [
                'text' => $this->module->l('You do not have permission to update order details.', 'orderdetailsupdater'),
                'class' => 'danger',
            ];
            return $packeteryOrder;
        }

        if ($packeteryOrder['exported']) {
            return $packeteryOrder;
        }

        $fieldsToUpdate = [];
        if (! $packeteryOrder['is_ad']) {
            $this->processPickupPointChange($fieldsToUpdate);
        } else {
            $countryDiffersMessage = $this->module->l('The selected delivery address is in a country other than the country of delivery of the order.', 'orderdetailsupdater');
            $this->processAddressChange($messages, $fieldsToUpdate, $packeteryOrder, $countryDiffersMessage);
            // We need to fetch the Order with a country, so we can compare the change of address and see if it matches the original country selected for delivery.
            $packeteryOrder = $this->orderRepository->getOrderWithCountry($orderId);
        }

        $this->processDimensionsAndPricesChange($messages, $fieldsToUpdate);
        if ($fieldsToUpdate) {
            if (
                !isset($fieldsToUpdate['age_verification_required']) &&
                CarrierTools::orderSupportsAgeVerification($packeteryOrder)
            ) {
                $fieldsToUpdate['age_verification_required'] = 0;
            }

            foreach ($fieldsToUpdate as &$value) {
                if (is_numeric($value) === false && $value !== null) {
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

                foreach ($fieldsToUpdate as $key => $value) {
                    $packeteryOrder[$key] = $value;
                }
            } else {
                $messages[] = [
                    'text' => $this->module->l('Order update failed.', 'orderdetailsupdater'),
                    'class' => 'danger',
                ];
            }
        }

        if ((bool)$packeteryOrder['is_ad'] === false && $packeteryOrder['id_branch'] === null) {
            $messages[] = [
                'text' => $this->module->l(
                    'No pickup point selected for the order. It will not be possible to export the order to Packeta.',
                    'orderdetailsupdater'
                ),
                'class' => 'danger',
            ];
        }

        return $packeteryOrder;
    }

    public function processDimensionsAndPricesChange(array &$messages, array &$fieldsToUpdate): void
    {
        $newFieldsToUpdate = $invalidInts = $invalidFloats = [];

        $inputConfigs = [
            'length' => [
                'translation' => $this->module->l('length', 'orderdetailsupdater'),
                'validation' => 'int',
            ],
            'height' => [
                'translation' => $this->module->l('height', 'orderdetailsupdater'),
                'validation' => 'int',
            ],
            'width' => [
                'translation' => $this->module->l('width', 'orderdetailsupdater'),
                'validation' => 'int',
            ],
            'age_verification_required' => [
                'translation' => $this->module->l('age verification', 'orderdetailsupdater'),
                'validation' => 'int',
            ],
            'price_total' => [
                'translation' => $this->module->l('packet value', 'orderdetailsupdater'),
                'validation' => 'float',
            ],
            'price_cod' => [
                'translation' => $this->module->l('COD value', 'orderdetailsupdater'),
                'validation' => 'float',
            ],
            'weight' => [
                'translation' => $this->module->l('weight', 'orderdetailsupdater'),
                'validation' => 'float',
            ],
        ];

        foreach ($inputConfigs as $inputName => $config) {
            $rawValue = Tools::getValue($inputName);
            if ($rawValue === '' || $rawValue === false) {
                continue;
            }

            $value = null;
            $isValid = false;
            if ($config['validation'] === 'int') {
                if (is_numeric($rawValue) && (string)(int)$rawValue === (string)$rawValue) {
                    $value = (int)$rawValue;
                    $isValid = $value > 0;
                }

                if ($isValid === false) {
                    $invalidInts[] = $config['translation'];
                }
            } elseif ($config['validation'] === 'float') {
                $rawValue = Tools::sanitizeFloatValue($rawValue);
                if (is_numeric($rawValue)) {
                    // Compatibility with decimal(20,6).
                    $value = round((float)$rawValue, 6);

                    $isValid = $value > 0;
                }

                if ($isValid === false) {
                    $invalidFloats[] = $config['translation'];
                }
            }

            if ($isValid === true) {
                $newFieldsToUpdate[$inputName] = $value;
            }
        }

        if ($invalidInts !== []) {
            $fieldNamesList = implode(', ', $invalidInts);
            $messages[] = [
                'text' => sprintf(
                    $this->module->l('%s must be a whole number, greater than 0.', 'orderdetailsupdater'),
                    ucfirst($fieldNamesList)
                ),
                'class' => 'danger',
            ];
        }
        if ($invalidFloats !== []) {
            $fieldNamesList = implode(', ', $invalidFloats);
            $messages[] = [
                'text' => sprintf(
                    $this->module->l('%s must be a number, greater than 0.', 'orderdetailsupdater'),
                    ucfirst($fieldNamesList)
                ),
                'class' => 'danger',
            ];
        }

        if ($invalidInts !== [] || $invalidFloats !== []) {
            return;
        }

        $fieldsToUpdate = array_merge($fieldsToUpdate, $newFieldsToUpdate);
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
