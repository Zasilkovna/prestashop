<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Order;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Address\AddressTools;
use Packetery\Exceptions\ClaimRequestException;
use Packetery\Request\CreateClaimRequest;
use Packetery\Tools\ConfigHelper;

/**
 * Builds the claim request for an order.
 * PrestaShop object access is kept in create() so buildValidatedRequest() and buildRequest()
 * stay object-free and unit-testable.
 */
class ClaimRequestFactory
{
    /** @var OrderRepository */
    private $orderRepository;
    /** @var OrderExporter */
    private $orderExporter;
    /** @var OrderNumberResolver */
    private $orderNumberResolver;

    public function __construct(
        OrderRepository $orderRepository,
        OrderExporter $orderExporter,
        OrderNumberResolver $orderNumberResolver
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderExporter = $orderExporter;
        $this->orderNumberResolver = $orderNumberResolver;
    }

    /**
     * Reads the order objects and delegates validation and mapping to buildValidatedRequest.
     * The customer return form may override the contact: a non-null $emailOverride
     * / $phoneOverride replaces the order's e-mail / phone, otherwise the order address is used.
     *
     * @throws ClaimRequestException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function create(int $orderId, ?string $emailOverride = null, ?string $phoneOverride = null): CreateClaimRequest
    {
        $psOrder = new \Order($orderId);
        if (!\Validate::isLoadedObject($psOrder)) {
            throw new ClaimRequestException(ClaimFault::ORDER_NOT_FOUND, 'Order not found.');
        }

        $eshopId = (string) ConfigHelper::get(
            ConfigHelper::KEY_ESHOP_ID,
            (int) $psOrder->id_shop_group,
            (int) $psOrder->id_shop
        );

        $address = new \Address((int) $psOrder->id_address_delivery);
        $customer = $psOrder->getCustomer();

        $packeteryOrder = $this->orderRepository->getOrderWithCountry($orderId);
        if (is_array($packeteryOrder)) {
            // The value is null when the exchange rate for the order currency cannot be resolved
            [$currency, $value] = $this->orderExporter->findCurrencyAndTotalValue($psOrder, $packeteryOrder);
            $consignCountry = (string) ($packeteryOrder['ps_country'] ?? '');
        } else {
            // non-Packeta order: the return still goes via Packeta, so build it from the PrestaShop order
            $currency = $this->orderCurrencyIso($psOrder);
            $value = (float) $psOrder->getTotalPaid();
            $consignCountry = (string) AddressTools::getDeliveryCountryIso($psOrder);
        }

        $email = $emailOverride !== null ? $emailOverride : (string) $customer->email;
        if ($phoneOverride !== null) {
            $phoneMobile = $phoneOverride;
            $phone = '';
        } else {
            $phoneMobile = (string) $address->phone_mobile;
            $phone = (string) $address->phone;
        }

        return $this->buildValidatedRequest(
            $eshopId,
            $value,
            $currency,
            $this->orderNumberResolver->getPreferredOrderNumber($psOrder),
            $email,
            $phoneMobile,
            $phone,
            $consignCountry
        );
    }

    private function orderCurrencyIso(\Order $psOrder): ?string
    {
        $currency = new \Currency((int) $psOrder->id_currency);

        return \Validate::isLoadedObject($currency) ? (string) $currency->iso_code : null;
    }

    /**
     * Validates the resolved order inputs and builds the claim request,
     * free of PrestaShop objects so the fault paths stay unit-testable
     *
     * @throws ClaimRequestException
     */
    public function buildValidatedRequest(
        string $eshopId,
        ?float $value,
        ?string $currency,
        string $orderNumber,
        string $email,
        string $phoneMobile,
        string $phone,
        ?string $consignCountry
    ): CreateClaimRequest {
        if ($eshopId === '') {
            throw new ClaimRequestException(ClaimFault::ESHOP_ID_MISSING, 'Packetery eShop ID is not configured.');
        }
        if ($value === null) {
            throw new ClaimRequestException(ClaimFault::VALUE_UNRESOLVED, 'Order total value could not be resolved.');
        }
        if ($email === '') {
            throw new ClaimRequestException(ClaimFault::EMAIL_MISSING, 'Customer email is missing; the return cannot be created.');
        }
        // Phone is optional on the module side: it is sent as-is and the Packeta
        // API decides. The customer/e-shop can add it in the form if the API rejects a claim without it.

        return $this->buildRequest(
            $orderNumber,
            $email,
            $phoneMobile,
            $phone,
            $value,
            (string) $currency,
            $eshopId,
            $consignCountry !== null ? $consignCountry : ''
        );
    }

    /**
     * Maps the already-resolved order values to the claim request
     */
    public function buildRequest(
        string $orderNumber,
        string $email,
        string $phoneMobile,
        string $phone,
        float $value,
        string $currency,
        string $eshopId,
        string $consignCountry
    ): CreateClaimRequest {
        return new CreateClaimRequest(
            $orderNumber,
            $email !== '' ? $email : null,
            $this->resolvePhone($phoneMobile, $phone),
            $value,
            $currency,
            $eshopId,
            $consignCountry !== '' ? $consignCountry : null,
            $email !== '' // notify only when an email is present; buildValidatedRequest already enforces it
        );
    }

    private function resolvePhone(string $phoneMobile, string $phone): ?string
    {
        if ($phoneMobile !== '') {
            $resolved = trim($phoneMobile);
        } else {
            $resolved = trim($phone);
        }

        if ($resolved === '') {
            return null;
        }

        return $resolved;
    }
}
