<?php

namespace Packetery\Order;

use Packetery\Module\SoapApi;
use Packetery\Tools\ConfigHelper;
use SoapClient;
use SoapFault;

class Labels
{
    const TYPE_PACKETA = 'packeta';
    const TYPE_CARRIER = 'carrier';

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    public function __construct(ConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    /**
     * @param array $packets
     * @param string $type
     * @param int $offset
     * @param array|null $packetsEnhanced
     * @return string|void
     */
    public function packetsLabelsPdf($packets, $type, $offset, $packetsEnhanced = null)
    {
        $client = new SoapClient(SoapApi::API_WSDL_URL);
        try {
            if ($type === self::TYPE_CARRIER) {
                $format = ConfigHelper::get('PACKETERY_CARRIER_LABEL_FORMAT');
                $pdf = $client->packetsCourierLabelsPdf($this->configHelper->getApiPass(), $packetsEnhanced, $offset, $format);
            } else {
                $format = ConfigHelper::get('PACKETERY_LABEL_FORMAT');
                $pdf = $client->packetsLabelsPdf($this->configHelper->getApiPass(), $packets, $format, $offset);
            }
            if ($pdf) {
                $file_name = 'zasilkovna_' . date("Y-m-d") . '-' . rand(1000, 9999) . '.pdf';
                file_put_contents(_PS_MODULE_DIR_ . 'packetery/labels/' . $file_name, $pdf);
                return $file_name;
            }

            echo "\n error \n";
            exit;
        } catch (SoapFault $e) {
            if (isset($e->faultstring)) {
                $error_msg = $e->faultstring;
                echo "\n$error_msg\n";
            }
            exit;
        }
    }
}
