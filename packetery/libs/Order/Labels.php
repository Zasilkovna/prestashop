<?php

namespace Packetery\Order;

use Packetery\Module\SoapApi;
use Packetery\Tools\ConfigHelper;
use SoapClient;
use SoapFault;

class Labels
{
    public function packetsLabelsPdf($packets, $apiPassword, $offset)
    {
        $client = new SoapClient(SoapApi::API_WSDL_URL);
        $format = ConfigHelper::get('PACKETERY_LABEL_FORMAT');
        try {
            $pdf = $client->packetsLabelsPdf($apiPassword, $packets, $format, $offset);
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
