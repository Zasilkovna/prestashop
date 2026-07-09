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
     * Reads the order objects and delegates validation and mapping to buildValidatedRequest
     *
     * @throws ClaimRequestException
     * @throws \Packetery\Exceptions\DatabaseException
     */
    public function create(int $orderId): CreateClaimRequest
    {
        $psOrder = new \Order($orderId);
        $packeteryOrder = $this->orderRepository->getOrderWithCountry($orderId);

        $eshopId = (string) ConfigHelper::get(
            ConfigHelper::KEY_ESHOP_ID,
            (int) $psOrder->id_shop_group,
            (int) $psOrder->id_shop
        );

        // The value is null when the exchange rate for the order currency cannot be resolved
        [$currency, $value] = is_array($packeteryOrder)
            ? $this->orderExporter->findCurrencyAndTotalValue($psOrder, $packeteryOrder)
            : [null, null];

        $customer = $psOrder->getCustomer();
        $address = new \Address((int) $psOrder->id_address_delivery);

        return $this->buildValidatedRequest(
            $packeteryOrder,
            $eshopId,
            $value,
            $currency,
            $this->orderNumberResolver->getPreferredOrderNumber($psOrder),
            (string) $customer->email,
            (string) $address->phone_mobile,
            (string) $address->phone
        );
    }

    /**
     * Validates the resolved order inputs and builds the claim request,
     * free of PrestaShop objects so the fault paths stay unit-testable
     *
     * @param mixed $packeteryOrder repository row (array), or false/null when not found
     *
     * @throws ClaimRequestException
     */
    public function buildValidatedRequest(
        $packeteryOrder,
        string $eshopId,
        ?float $value,
        ?string $currency,
        string $orderNumber,
        string $email,
        string $phoneMobile,
        string $phone
    ): CreateClaimRequest {
        if (!is_array($packeteryOrder)) {
            throw new ClaimRequestException(ClaimFault::ORDER_NOT_FOUND, 'Packetery order not found.');
        }
        if ($eshopId === '') {
            throw new ClaimRequestException(ClaimFault::ESHOP_ID_MISSING, 'Packetery eShop ID is not configured.');
        }
        if ($value === null) {
            throw new ClaimRequestException(ClaimFault::VALUE_UNRESOLVED, 'Order total value could not be resolved.');
        }
        if ($email === '') {
            throw new ClaimRequestException(ClaimFault::EMAIL_MISSING, 'Customer email is missing; the return cannot be created.');
        }
        if ($this->resolvePhone($phoneMobile, $phone) === null) {
            throw new ClaimRequestException(ClaimFault::PHONE_MISSING, 'Customer phone is missing; the return cannot be created.');
        }

        $consignCountry = (string) ($packeteryOrder['ps_country'] ?? '');

        return $this->buildRequest(
            $orderNumber,
            $email,
            $phoneMobile,
            $phone,
            $value,
            (string) $currency,
            $eshopId,
            $consignCountry
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
