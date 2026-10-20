<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\SetupGuide;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Carrier\CarrierRepository;
use Packetery\Cron\CronRunLog;
use Packetery\Exceptions\DatabaseException;
use Packetery\Order\ConsignPasswordSettings;
use Packetery\Payment\PaymentRepository;
use Packetery\Tools\ConfigHelper;

class SetupGuideStateFactory
{
    /** @var CarrierRepository */
    private $carrierRepository;

    /** @var PaymentRepository */
    private $paymentRepository;

    /** @var CronRunLog */
    private $cronRunLog;

    public function __construct(
        CarrierRepository $carrierRepository,
        PaymentRepository $paymentRepository,
        CronRunLog $cronRunLog
    ) {
        $this->carrierRepository = $carrierRepository;
        $this->paymentRepository = $paymentRepository;
        $this->cronRunLog = $cronRunLog;
    }

    /**
     * Reads the configuration and the payment modules of the current shop context, the carriers of all shops
     *
     * @throws DatabaseException
     */
    public function create(): SetupGuideState
    {
        $payments = $this->paymentRepository->getListPayments();

        return new SetupGuideState(
            $this->isConfigValueSet(ConfigHelper::KEY_APIPASS),
            $this->isConfigValueSet(ConfigHelper::KEY_ESHOP_ID),
            (bool) ConfigHelper::get(ConfigHelper::KEY_CARRIERS_MANUALLY_UPDATED),
            $this->carrierRepository->hasActivePairedCarrier(),
            $this->hasCodPayment($payments),
            $payments === [] ? null : $payments[0]['module_name'],
            (bool) ConfigHelper::get(ConfigHelper::KEY_PACKET_STATUS_TRACKING_ENABLED),
            (bool) ConfigHelper::get(ConfigHelper::KEY_ORDER_STATUS_CHANGE_ENABLED),
            ConsignPasswordSettings::fromConfig()->isCron(),
            $this->cronRunLog->getLastRuns()
        );
    }

    private function isConfigValueSet(string $key): bool
    {
        return (string) ConfigHelper::get($key) !== '';
    }

    /**
     * @param list<array{name: string, is_cod: int, module_name: string}> $payments
     */
    private function hasCodPayment(array $payments): bool
    {
        foreach ($payments as $payment) {
            if ($payment['is_cod'] === 1) {
                return true;
            }
        }

        return false;
    }
}
