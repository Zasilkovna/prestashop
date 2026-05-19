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

use Packetery\Log\LogRepository;
use Packetery\Module\SoapApi;

class BarcodeProvider
{
    /** @var SoapApi */
    private $soapApi;

    /** @var LogRepository */
    private $logRepository;

    public function __construct(SoapApi $soapApi, LogRepository $logRepository)
    {
        $this->soapApi = $soapApi;
        $this->logRepository = $logRepository;
    }

    /**
     * @param string[] $trackingNumbers
     */
    public function getBarcodeData(array $trackingNumbers): ?BarcodeData
    {
        $shipmentResponse = $this->soapApi->createShipment($trackingNumbers);
        if ($shipmentResponse->hasFault()) {
            $this->logShipmentFault($trackingNumbers, $shipmentResponse->getFaultString());

            return null;
        }

        $barcode = $shipmentResponse->getBarcode();
        if ($barcode === '') {
            return null;
        }

        $barcodeResponse = $this->soapApi->barcodePng($barcode);
        if ($barcodeResponse->hasFault()) {
            $this->logBarcodeFault($barcode, $barcodeResponse->getFaultString());

            return null;
        }

        $image = $barcodeResponse->getImage();
        if ($image === '') {
            return null;
        }

        return new BarcodeData($barcode, base64_encode($image));
    }

    /**
     * @param string[] $trackingNumbers
     */
    private function logShipmentFault(array $trackingNumbers, ?string $faultMessage): void
    {
        $this->logRepository->insertRow(
            LogRepository::ACTION_COLLECTION_PRINT,
            [
                'request' => [
                    'packetIds' => $trackingNumbers,
                ],
                'response' => $faultMessage,
            ],
            LogRepository::STATUS_ERROR
        );
    }

    private function logBarcodeFault(string $barcode, ?string $faultMessage): void
    {
        $this->logRepository->insertRow(
            LogRepository::ACTION_COLLECTION_PRINT,
            [
                'request' => [
                    'barcode' => $barcode,
                ],
                'response' => $faultMessage,
            ],
            LogRepository::STATUS_ERROR
        );
    }
}
