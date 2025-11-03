<?php

namespace Packetery\Order;

use Order;
use Packetery;
use Packetery\Exceptions\DatabaseException;
use Packetery\Exceptions\ExportException;
use PrestaShopDatabaseException;
use PrestaShopException;
use ReflectionException;

class CsvExporter
{
    /** @var Packetery */
    private $module;

    public function __construct(Packetery $module)
    {
        $this->module = $module;
    }

    /**
     * Get data for CSV Export
     *
     * @param array $order_ids - IDs of orders to be exported
     * @return array - Order data
     * @throws DatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     */
    private function collectOrdersDataForCsvExport(array $order_ids)
    {
        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->module->diContainer->get(OrderRepository::class);
        /** @var OrderExporter $orderExporter */
        $orderExporter = $this->module->diContainer->get(OrderExporter::class);
        $data = [];
        foreach ($order_ids as $order_id) {
            $order = new Order($order_id);
            try {
                $exportData = $orderExporter->prepareData($order);
            } catch (ExportException $exception) {
                continue;
            }

            $data[$order_id] = [
                'reserved' => '',
                'orderNumber' => $exportData['number'],
                'firstName' => $exportData['firstName'],
                'lastName' => $exportData['lastName'],
                'company' => $exportData['company'],
                'email' => $exportData['email'],
                'phone' => $exportData['phone'],
                'codValue' => $exportData['codValue'],
                'currency' => $exportData['currency'],
                'value' => $exportData['value'],
                'weight' => $exportData['weight'],
                'pickupPointOrCarrier' => $exportData['pickupPointOrCarrier'],
                'senderLabel' => $exportData['senderLabel'],
                'adultContent' => $exportData['adultContent'],
                'delayedDelivery' => '',
                'street' => '',
                'houseNumber' => '',
                'city' => '',
                'zip' => '',
                'carrierPickupPoint' => '',
                'width' => isset($exportData['size']['width']) ? $exportData['size']['width'] : '',
                'height' => isset($exportData['size']['length']) ? $exportData['size']['length'] : '',
                'depth' => isset($exportData['size']['height']) ? $exportData['size']['height'] : '',
                'note' => '',
                'allowPublicTracking' => '',
                'allowTrackingForUsers' => '',
                'romaniaCIFNumber' => '',
                'affiliateId' => PacketSubmitter::AFFILIATE_ID,
            ];
            foreach (['carrierPickupPoint', 'street', 'houseNumber', 'city', 'zip'] as $key) {
                if (isset($exportData[$key])) {
                    $data[$order_id][$key] = $exportData[$key];
                }
            }

            $orderRepository->setExported(true, $order_id);
        }

        return $data;
    }

    /**
     * @param array $orders
     * @throws DatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     */
    public function outputCsvExport(array $orders)
    {
        $orderData = $this->collectOrdersDataForCsvExport($orders);
        $date = date('Y-m-d');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="export_' . $date . '.csv"');
        $fp = fopen('php://output', 'wb');
        fputcsv($fp, ['version 8']);
        fputcsv($fp, []);
        foreach ($orderData as $line) {
            fputcsv($fp, $line);
        }
        fclose($fp);
    }
}
