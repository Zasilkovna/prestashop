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

class CollectionPrintOrderViewFactory
{
    /** @var OrderExporter */
    private $orderExporter;

    /** @var CodResolver */
    private $codResolver;

    public function __construct(OrderExporter $orderExporter, CodResolver $codResolver)
    {
        $this->orderExporter = $orderExporter;
        $this->codResolver = $codResolver;
    }

    /**
     * @param array<string, mixed> $orderData
     *
     * @return array{
     *     index: int,
     *     trackingNumber: string,
     *     orderNumber: string,
     *     customerName: string,
     *     pickupPoint: string,
     *     created: string,
     *     cod: string,
     *     codCurrency: string
     * }
     */
    public function create(
        int $index,
        \Order $order,
        \Address $address,
        array $orderData,
        string $orderNumber
    ): array {
        [$codCurrency, $total] = $this->orderExporter->findCurrencyAndTotalValue($order, $orderData);
        $cod = $this->codResolver->roundCodByCurrency(
            $this->codResolver->resolveCodValue($orderData, (float) $total),
            $codCurrency
        );

        return [
            'index' => $index,
            'trackingNumber' => (string) ($orderData['tracking_number'] ?? ''),
            'orderNumber' => $orderNumber,
            'customerName' => trim(sprintf('%s %s', $address->firstname, $address->lastname)),
            'pickupPoint' => (string) ($orderData['name_branch'] ?? ''),
            'created' => $this->formatCreated($order->date_add),
            'cod' => number_format($cod, CodResolver::CURRENCY_DEFAULT_PRECISION, '.', ' '),
            'codCurrency' => $codCurrency,
        ];
    }

    private function formatCreated(string $dateString): string
    {
        $timestamp = strtotime($dateString);
        if ($timestamp === false) {
            return '';
        }

        return date('d. m.', $timestamp);
    }
}
