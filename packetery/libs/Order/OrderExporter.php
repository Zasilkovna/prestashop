<?php

namespace Packetery\Order;

use Address;
use Currency;
use Order;
use Packetery;
use Packetery\Address\AddressTools;
use Packetery\Exceptions\DatabaseException;
use Packetery\Exceptions\ExportException;
use Packetery\Tools\ConfigHelper;
use Packetery\Weight\Converter;
use Packeteryclass;
use ReflectionException;
use Tools;

class OrderExporter
{

    /** @var Packetery */
    private $module;

    public function __construct(Packetery $module)
    {
        $this->module = $module;
    }

    /**
     * @param Order $order
     * @return array
     * @throws ExportException
     * @throws DatabaseException
     * @throws ReflectionException
     */
    public function prepareData(Order $order)
    {
        $orderRepository = $this->module->diContainer->get(OrderRepository::class);
        $packeteryOrder = $orderRepository->getWithShopById($order->id);

        if (empty($packeteryOrder) || empty($packeteryOrder['id_branch'])) {
            throw new ExportException(
                $this->module->l('Unable to load information required to export order', 'orderexporter') .
                ' ' . $order->id
            );
        }

        $total = $order->total_paid;

        $orderCurrency = new Currency($order->id_currency);
        $targetCurrency = $packeteryOrder['currency_branch'];
        if ($orderCurrency->iso_code !== $targetCurrency) {
            $total = Packeteryclass::getRateTotal($orderCurrency->iso_code, $targetCurrency, $total, $orderRepository);
            if (!$total) {
                throw new ExportException(
                    $this->module->l(
                        'Can\'t find order currency rate between order and pickup point, order',
                        'orderexporter'
                    ) . ' - ' . $order->id
                );
            }
        }

        $isCod = $packeteryOrder['is_cod'];
        if ($isCod) {
            if ($targetCurrency === 'CZK') {
                $codValue = ceil($total);
            } elseif ($targetCurrency === 'HUF') {
                $codValue = $this->roundUpMultiples($total);
            } else {
                $codValue = round($total, 2);
            }
        } else {
            $codValue = 0;
        }

        $address = new Address($order->id_address_delivery);
        $phone = '';
        if (Tools::strlen($address->phone)) {
            $phone = trim($address->phone);
        }
        if (Tools::strlen($address->phone_mobile)) {
            $phone = trim($address->phone_mobile);
        }

        $weight = '';
        if ($packeteryOrder['weight'] !== null) {
            // used saved if set
            $weight = $packeteryOrder['weight'];
        } else if (Converter::isKgConversionSupported()) {
            $weight = Converter::getKilograms((float)$order->getTotalWeight());
        }

        $senderLabel = (ConfigHelper::get('PACKETERY_ESHOP_ID', $packeteryOrder['id_shop_group'], $packeteryOrder['id_shop']) ?: '');
        $customer = $order->getCustomer();
        $data = [
            'currency' => $targetCurrency,
            'value' => $total,
            'codValue' => $codValue,
            'weight' => $weight,

            'senderLabel' => $senderLabel,
            'pickupPointOrCarrier' => $packeteryOrder['id_branch'],
            'carrierPickupPoint' => $packeteryOrder['carrier_pickup_point'],

            'firstName' => ($address->firstname ?: $customer->firstname),
            'lastName' => ($address->lastname ?: $customer->lastname),
            'company' => $customer->company,
            'phone' => $phone,
            'email' => $customer->email,
        ];

        if ($packeteryOrder['is_ad']) {
            if (AddressTools::hasValidatedAddress($packeteryOrder)) {
                $data['zip'] = $packeteryOrder['zip'];
                $data['city'] = $packeteryOrder['city'];
                $data['street'] = $packeteryOrder['street'];
                $data['houseNumber'] = $packeteryOrder['house_number'];
            } else {
                $data['zip'] = str_replace(' ', '', $address->postcode);
                $data['city'] = $address->city;
                $data['street'] = $address->address1;
            }
        }

        return $data;
    }

    /**
     * Gets a value divisible by $x.
     * @param float|int $n
     * @param int $x
     * @return float|int
     */
    public function roundUpMultiples($n, $x = 5)
    {
        return (ceil($n) % $x === 0) ? ceil($n) : round(($n + $x / 2) / $x) * $x;
    }

}
