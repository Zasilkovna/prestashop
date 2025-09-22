<?php
/**
 * 2017 Zlab Solutions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
 *  @copyright Since 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Packetery\Order;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Log\LogRepository;
use Packetery\Module\SoapApi;
use Packetery\Tools\ConfigHelper;

class Labels
{
    const TYPE_PACKETA = 'packeta';
    const TYPE_CARRIER = 'carrier';

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /** @var LogRepository */
    private $logRepository;

    /** @var \Packetery */
    private $module;

    public function __construct(
        ConfigHelper $configHelper,
        LogRepository $logRepository,
        \Packetery $module,
    ) {
        $this->logRepository = $logRepository;
        $this->configHelper = $configHelper;
        $this->module = $module;
    }

    /**
     * @param array $packets used for packeta labels
     * @param string $type
     * @param int $offset
     * @param array|null $packetsEnhanced used for carrier labels
     *
     * @return string|void
     */
    public function packetsLabelsPdf(array $packets, $type, $packetsEnhanced = null, $offset = 0)
    {
        $client = new \SoapClient(SoapApi::WSDL_URL);
        try {
            if ($type === self::TYPE_CARRIER) {
                $format = ConfigHelper::get('PACKETERY_CARRIER_LABEL_FORMAT');
                $pdf = $client->packetsCourierLabelsPdf($this->configHelper->getApiPass(), $packetsEnhanced, $offset, $format);
            } else {
                $format = ConfigHelper::get('PACKETERY_LABEL_FORMAT');
                $pdf = $client->packetsLabelsPdf($this->configHelper->getApiPass(), array_values($packets), $format, $offset);
            }
            if ($pdf) {
                foreach ($packets as $orderId => $packetNumber) {
                    $this->logRepository->insertRow(
                        LogRepository::ACTION_LABEL_PRINT,
                        [
                            'packetNumber' => $packetNumber,
                            'format' => $format,
                            'type' => $type,
                        ],
                        LogRepository::STATUS_SUCCESS,
                        $orderId
                    );
                }

                return $pdf;
            }

            foreach ($packets as $orderId => $packetNumber) {
                $this->logRepository->insertRow(
                    LogRepository::ACTION_LABEL_PRINT,
                    [
                        'packetNumber' => $packetNumber,
                        'format' => $format,
                        'type' => $type,
                    ],
                    LogRepository::STATUS_ERROR,
                    $orderId
                );
            }

            echo "\n error \n";
            exit;
        } catch (\SoapFault $e) {
            if (isset($e->faultstring)) {
                $error_msg = $e->faultstring;
                echo "\n$error_msg\n";
            }

            foreach ($packets as $orderId => $packetNumber) {
                $this->logRepository->insertRow(
                    LogRepository::ACTION_LABEL_PRINT,
                    [
                        'packetNumber' => $packetNumber,
                        'type' => $type,
                        'exception' => $e->getMessage(),
                    ],
                    LogRepository::STATUS_ERROR,
                    $orderId
                );
            }

            exit;
        }
    }
}
