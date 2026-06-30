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

class CollectionPrintService
{
    /** @var OrderRepository */
    private $orderRepository;

    /** @var CollectionPrintOrderViewFactory */
    private $orderViewFactory;

    /** @var OrderNumberResolver */
    private $orderNumberResolver;

    public function __construct(
        OrderRepository $orderRepository,
        CollectionPrintOrderViewFactory $orderViewFactory,
        OrderNumberResolver $orderNumberResolver
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderViewFactory = $orderViewFactory;
        $this->orderNumberResolver = $orderNumberResolver;
    }

    /**
     * @param int[] $orderIds
     *
     * @return array{
     *     ordersForPrint: array<int, array{
     *         index: int,
     *         trackingNumber: string,
     *         consignPassword: string|null,
     *         orderNumber: string,
     *         customerName: string,
     *         pickupPoint: string,
     *         created: string,
     *         cod: string,
     *         codCurrency: string
     *     }>,
     *     trackingNumbers: string[]
     * }
     */
    public function buildOrdersForPrint(array $orderIds): array
    {
        $ordersData = $this->orderRepository->getByIds($orderIds);
        $trackingNumbers = [];
        $ordersForPrint = [];
        $index = 1;

        foreach ($orderIds as $orderId) {
            if (!isset($ordersData[$orderId])) {
                continue;
            }
            $orderData = $ordersData[$orderId];

            $trackingNumber = (string) ($orderData['tracking_number'] ?? '');
            if ($trackingNumber === '') {
                continue;
            }

            $order = new \Order($orderId);
            $orderNumber = $this->orderNumberResolver->getPreferredOrderNumber($order);

            $trackingNumbers[] = $trackingNumber;
            $ordersForPrint[] = $this->orderViewFactory->create(
                $index,
                $order,
                new \Address($order->id_address_delivery),
                $orderData,
                $orderNumber
            );
            ++$index;
        }

        return [
            'ordersForPrint' => $ordersForPrint,
            'trackingNumbers' => $trackingNumbers,
        ];
    }
}
