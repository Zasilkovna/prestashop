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

use Packetery\Exceptions\CollectionPrintException;
use Packetery\Module\CompanyAddress;

class CollectionPrintHandler
{
    /** @var \Packetery */
    private $module;

    /** @var CollectionPrintService */
    private $collectionPrintService;

    /** @var BarcodeProvider */
    private $barcodeProvider;

    public function __construct(
        \Packetery $module,
        CollectionPrintService $collectionPrintService,
        BarcodeProvider $barcodeProvider
    ) {
        $this->module = $module;
        $this->collectionPrintService = $collectionPrintService;
        $this->barcodeProvider = $barcodeProvider;
    }

    /**
     * Returns template variables for the bulk action's auto-submit form, or empty array when there is nothing to render.
     *
     * @param int[] $orderIds
     *
     * @return array<string, string>
     */
    public function handleBulkAction(array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }

        $encodedIds = json_encode($orderIds);
        if ($encodedIds === false) {
            return [];
        }

        return [
            'collectionPrintFormPath' => __DIR__ . '/../../views/templates/admin/collectionPrintForm.tpl',
            'collectionPrintOrderIds' => $encodedIds,
            'collectionPrintUrl' => $this->module->getAdminLink(
                'PacketeryOrderGrid',
                ['action' => 'showCollectionPrint']
            ),
        ];
    }

    /**
     * @throws CollectionPrintException
     */
    public function renderPrint(string $rawIds): void
    {
        $errorMessage = $this->module->l('Failed to generate bill of delivery', 'packeteryordergridcontroller');

        $decoded = json_decode($rawIds, true);
        if (!is_array($decoded) || $decoded === []) {
            throw new CollectionPrintException($errorMessage);
        }

        $orderIds = array_map('intval', $decoded);

        $ordersData = $this->collectionPrintService->buildOrdersForPrint($orderIds);
        if ($ordersData['trackingNumbers'] === []) {
            throw new CollectionPrintException($errorMessage);
        }

        $barcodeData = $this->barcodeProvider->getBarcodeData($ordersData['trackingNumbers']);
        if ($barcodeData === null) {
            throw new CollectionPrintException($errorMessage);
        }

        $shopAddress = $this->module->getContext()->shop->getAddress();

        $smarty = $this->module->getContext()->smarty;
        $smarty->assign('collectionPrintCssUrl', $this->module->getPathUri() . 'views/css/collectionPrint.css');
        $smarty->assign('collectionPrintJsUrl', $this->module->getPathUri() . 'views/js/collectionPrint.js');
        $smarty->assign('barcodeImage', $barcodeData->barcodeImage);
        $smarty->assign('barcodeText', $barcodeData->barcodeText);
        $smarty->assign('orders', $ordersData['ordersForPrint']);
        $smarty->assign('orderCount', count($ordersData['ordersForPrint']));
        $smarty->assign('generatedAt', (new \DateTimeImmutable('now'))->format('d. m. Y'));

        $smarty->assign(
            'recipient',
            CompanyAddress::fromCountry(
                (int) $shopAddress->id_country,
                \Packetery::PACKETA_ADDRESS
            )->toArray()
        );

        $smarty->assign(
            'sender',
            [
                'name' => $shopAddress->company,
                'street' => $shopAddress->address1,
                'city' => $shopAddress->city,
                'zip' => $shopAddress->postcode,
            ]
        );

        header('Content-Type: text/html; charset=utf-8');
        echo $smarty->fetch(__DIR__ . '/../../views/templates/admin/collectionPrint.tpl');
        exit;
    }
}
