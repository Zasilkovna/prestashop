<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Cron\Tasks;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Exceptions\ApiClientException;
use Packetery\Exceptions\DatabaseException;
use Packetery\Order\ConsignPasswordProvider;
use Packetery\Order\ConsignPasswordSettings;
use Packetery\Order\OrderRepository;

class GetConsignPassword extends Base
{
    public const DEFAULT_MAX_ORDERS = 50;
    public const DEFAULT_MAX_ORDER_AGE_DAYS = 1;

    /** @var \Packetery */
    public $module;

    /** @var ConsignPasswordProvider */
    private $consignPasswordProvider;

    /** @var OrderRepository */
    private $orderRepository;

    public function __construct(
        \Packetery $module,
        ConsignPasswordProvider $consignPasswordProvider,
        OrderRepository $orderRepository
    ) {
        $this->module = $module;
        $this->consignPasswordProvider = $consignPasswordProvider;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return string[] informational messages to be displayed in the cron output
     */
    public function execute(int $maxOrders, int $maxOrderAgeDays): array
    {
        if (ConsignPasswordSettings::fromConfig()->isCron() === false) {
            return [
                $this->module->l(
                    'No packets were processed. This may be because the feature is disabled or consign passwords are retrieved immediately when a packet is submitted.',
                    'getconsignpassword'
                ),
            ];
        }

        if ($maxOrders <= 0) {
            $maxOrders = self::DEFAULT_MAX_ORDERS;
        }

        if ($maxOrderAgeDays <= 0) {
            $maxOrderAgeDays = self::DEFAULT_MAX_ORDER_AGE_DAYS;
        }

        try {
            $orders = $this->orderRepository->getOrdersMissingConsignPassword($maxOrders, $maxOrderAgeDays);
        } catch (DatabaseException $e) {
            return [$e->getMessage()];
        }

        if ($orders === []) {
            return [$this->module->l('No packets were processed.', 'getconsignpassword')];
        }

        try {
            $this->orderRepository->markConsignPasswordAttempts(array_column($orders, 'id_order'));
        } catch (DatabaseException $e) {
            return [$e->getMessage()];
        }

        foreach ($orders as $row) {
            $orderId = $row['id_order'];

            try {
                $consignPassword = $this->consignPasswordProvider->fetchFromApi($orderId, $row['tracking_number']);
                if ($consignPassword !== null) {
                    $this->orderRepository->setConsignPassword($orderId, $consignPassword);
                }
            } catch (ApiClientException $e) {
                continue;
            } catch (DatabaseException $e) {
                continue;
            }
        }

        return [];
    }
}
