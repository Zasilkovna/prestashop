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

use Order;
use Packetery\Exceptions\DatabaseException;
use Packetery\Exceptions\ExportException;

class CsvExporter
{
    /** @var \Packetery */
    private $module;

    public function __construct(\Packetery $module)
    {
        $this->module = $module;
    }

    /**
     * Get data for CSV Export
     *
     * @param array $order_ids - IDs of orders to be exported
     *
     * @return array - Order data
     *
     * @throws DatabaseException
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @throws \ReflectionException
     */
    private function collectOrdersDataForCsvExport(array $order_ids)
    {
        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->module->diContainer->get(OrderRepository::class);
        /** @var OrderExporter $orderExporter */
        $orderExporter = $this->module->diContainer->get(OrderExporter::class);
        $data = [];
        foreach ($order_ids as $order_id) {
            $order = new \Order($order_id);
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
            ];
            foreach (['carrierPickupPoint', 'street', 'houseNumber', 'city', 'zip'] as $key) {
                if (!empty($exportData[$key])) {
                    $data[$order_id][$key] = $exportData[$key];
                }
            }

            $orderRepository->setExported(true, $order_id);
        }

        return $data;
    }

    /**
     * @param array $orders
     *
     * @throws DatabaseException
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @throws \ReflectionException
     */
    public function outputCsvExport(array $orders)
    {
        $orderData = $this->collectOrdersDataForCsvExport($orders);
        $date = date('Y-m-d');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="export_' . $date . '.csv"');
        $fp = fopen('php://output', 'wb');
        fputcsv($fp, ['version 6']);
        fputcsv($fp, []);
        foreach ($orderData as $line) {
            fputcsv($fp, $line);
        }
        fclose($fp);
    }
}
