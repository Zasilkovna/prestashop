<?php

namespace Packetery\Order;

use Address;
use Currency;
use Order;
use Packetery;
use Packetery\Address\AddressTools;
use Packetery\Carrier\CarrierTools;
use Packetery\Exceptions\DatabaseException;
use Packetery\Exceptions\ExportException;
use Packetery\Payment\PaymentRepository;
use Packetery\Tools\ConfigHelper;
use ReflectionException;
use Tools;

class OrderExporter
{
    /** @var Packetery */
    private $module;

    /** @var Packetery\Weight\Calculator */
    private $weightCalculator;

    public function __construct(Packetery $module, Packetery\Weight\Calculator $weightCalculator)
    {
        $this->module = $module;
        $this->weightCalculator = $weightCalculator;
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
        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->module->diContainer->get(OrderRepository::class);
        $packeteryOrder = $orderRepository->getWithShopById($order->id);

        if (empty($packeteryOrder) || empty($packeteryOrder['id_branch'])) {
            throw new ExportException(
                $this->module->l('Unable to load information required to export order', 'orderexporter') .
                ' ' . $order->id
            );
        }

        list($exportCurrency, $total) = $this->getCurrencyAndTotalValue($order, $packeteryOrder);

        $isCod = $packeteryOrder['is_cod'];
        if ($isCod) {
            if ($packeteryOrder['price_cod'] === null) {
                $codValue = $total;
            } else {
                $codValue = $packeteryOrder['price_cod'];
            }

            if ($exportCurrency === 'CZK') {
                $codValue = ceil($codValue);
            } elseif ($exportCurrency === 'HUF') {
                $codValue = $this->roundUpMultiples($codValue);
            } else {
                $codValue = round($codValue, 2);
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

        $weight = $this->weightCalculator->getFinalWeight($packeteryOrder);
        $weight = (!$weight ? '' : $weight);

        $number = (string)(Packetery::ID_PREF_REF === ConfigHelper::get('PACKETERY_ID_PREFERENCE') ? $order->reference : $order->id);
        $senderLabel = (ConfigHelper::get('PACKETERY_ESHOP_ID', $packeteryOrder['id_shop_group'], $packeteryOrder['id_shop']) ?: '');
        $customer = $order->getCustomer();

        $size = [];
        $dimensions = ['length', 'height', 'width'];
        foreach ($dimensions as $dimension) {
            if (isset($packeteryOrder[$dimension])) {
                $size[$dimension] = (int)$packeteryOrder[$dimension];
            }
        }

        if ($packeteryOrder['age_verification_required'] === null) {
            $adultContent = $orderRepository->isOrderAdult($order->id);
        } else {
            $adultContent = (bool)$packeteryOrder['age_verification_required'];
        }
        if ($adultContent === true && CarrierTools::orderSupportsAgeVerification($packeteryOrder) === false) {
            throw new ExportException(
                sprintf($this->module->l('Order %s contains product only for adults, but the carrier does not support age verification.', 'orderexporter'), $order->id)
            );
        }

        $data = [
            'number' => $number,
            'currency' => $exportCurrency,
            'value' => $total,
            'codValue' => $codValue,
            'weight' => $weight,
            'size' => $size,

            'senderLabel' => $senderLabel,
            'pickupPointOrCarrier' => $packeteryOrder['id_branch'],
            'carrierPickupPoint' => $packeteryOrder['carrier_pickup_point'],

            'firstName' => ($address->firstname ?: $customer->firstname),
            'lastName' => ($address->lastname ?: $customer->lastname),
            'company' => $customer->company,
            'phone' => $phone,
            'email' => $customer->email,
            'adultContent' => $adultContent,
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
     *
     * @param float|int $n
     * @param int $x
     * @return float|int
     */
    public function roundUpMultiples($n, $x = 5)
    {
        return (ceil($n) % $x === 0) ? ceil($n) : round(($n + $x / 2) / $x) * $x;
    }

    /**
     * @param Order $order
     * @param array $packeteryOrder
     * @return array
     * @throws ExportException
     */
    public function getCurrencyAndTotalValue(Order $order, array $packeteryOrder)
    {
        $shippingCountryCurrency = $packeteryOrder['currency_branch'];

        if ($packeteryOrder['price_total'] === null) {
            $total = $order->total_paid;
            $defaultPackagePrice = ConfigHelper::get('PACKETERY_DEFAULT_PACKAGE_PRICE');
            if ($defaultPackagePrice > 0 && $total <= 0) {
                $total = $defaultPackagePrice;
            }
        } else {
            $total = $packeteryOrder['price_total'];
        }

        $orderCurrency = new Currency($order->id_currency);
        $exportCurrency = $orderCurrency->iso_code;
        if ($shippingCountryCurrency === null) {
            throw new ExportException(
                $this->module->l(
                    'Can\'t find currency of pickup point, order',
                    'orderexporter'
                ) . ' - ' . $order->id
            );
        }
        if (
            $orderCurrency->iso_code !== $shippingCountryCurrency &&
            (bool)ConfigHelper::get(ConfigHelper::KEY_USE_PS_CURRENCY_CONVERSION) === true
        ) {
            $exportCurrency = $shippingCountryCurrency;

            if ($packeteryOrder['price_total'] === null) {
                $paymentRepository = $this->module->diContainer->get(PaymentRepository::class);
                $total = $paymentRepository->getRateTotal($orderCurrency->iso_code, $shippingCountryCurrency, $total);
                if ($total === null) {
                    throw new ExportException(
                        $this->module->l(
                            'Unable to find the exchange rate in the PrestaShop currency settings for the destination country of the order',
                            'orderexporter'
                        ) . ': ' . $order->id
                    );
                }
            }
        }

        return [$exportCurrency, $total];
    }
}
