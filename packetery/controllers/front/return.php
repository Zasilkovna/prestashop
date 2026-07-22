<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Address\AddressTools;
use Packetery\Order\ClaimSubmitter;
use Packetery\Order\ReturnSubmissionResult;
use Packetery\Returns\CustomerReturnSectionProvider;
use Packetery\Returns\ReturnEntity;

/**
 * Customer return front controller.
 * Registered customer (C1): the return UI is inline in the account order detail; this controller
 * only processes the create POST and redirects back (PRG). Guest (C2): a standalone page where an
 * order number + matching e-mail unlock the same create action.
 */
class PacketeryReturnModuleFrontController extends ModuleFrontController
{
    /** @var bool guests reach the same controller, so authentication is handled per action */
    public $auth = false;

    /** @var string lookup|confirm|created|pending */
    private $guestView = 'lookup';
    /** @var string */
    private $guestError = '';

    /** @var string warning-level notice (e.g. the order cannot be returned), shown with the lookup form */
    private $guestNotice = '';
    /** @var string */
    private $guestReference = '';
    /** @var string */
    private $guestEmail = '';
    /** @var string */
    private $guestPhone = '';
    /** @var string */
    private $guestClaimId = '';
    /** @var string */
    private $guestTrackingUrl = '';

    /**
     * @throws PrestaShopException
     */
    public function postProcess(): void
    {
        if (Tools::isSubmit('submitPacketeryReturn')) {
            $this->processRegisteredCreate();

            return;
        }

        if (Tools::isSubmit('submitPacketeryReturnLookup') || Tools::isSubmit('submitPacketeryReturnGuestCreate')) {
            $this->processGuest();
        }
    }

    /**
     * @throws PrestaShopException
     */
    public function initContent(): void
    {
        // registered customers use the inline section in the account order detail, not a standalone page
        if ($this->context->customer->isLogged()) {
            $orderId = (int) Tools::getValue('id_order');
            if ($orderId > 0) {
                $this->redirectToOrderDetail($orderId, null);
            }

            Tools::redirect($this->context->link->getPageLink('history', true));
        }

        parent::initContent();
        $this->assignGuestView();
        $this->setTemplate('module:packetery/views/templates/front/return/guest.tpl');
    }

    /**
     * Registered inline-box create: verify ownership, create, and redirect back to the order detail.
     *
     * @throws PrestaShopException
     */
    private function processRegisteredCreate(): void
    {
        $orderId = (int) Tools::getValue('id_order');
        if (Tools::getValue('token') !== Tools::getToken(false) || $this->resolveOwnedOrder($orderId) === null) {
            $this->redirectToOrderDetail($orderId, 'error');

            return;
        }

        try {
            // STATE_FORM = eligible and no active return yet; guards eligibility and a duplicate on re-POST
            if ($this->buildSection($orderId)['returnState'] !== CustomerReturnSectionProvider::STATE_FORM) {
                $this->redirectToOrderDetail($orderId, 'error');

                return;
            }

            $result = $this->createReturn($orderId);
        } catch (Exception $exception) {
            $this->redirectToOrderDetail($orderId, 'error');

            return;
        }

        $this->redirectToOrderDetail($orderId, $this->flashForResult($result));
    }

    /**
     * Maps a submission outcome to the order-detail flash key.
     */
    private function flashForResult(ReturnSubmissionResult $result): string
    {
        if ($result->isCreated()) {
            return 'created';
        }

        if ($result->isPending()) {
            return 'pending';
        }

        return 'error';
    }

    /**
     * Guest lookup and create on the standalone page: an order number + matching e-mail are the credential.
     */
    private function processGuest(): void
    {
        $this->guestReference = trim((string) Tools::getValue('packetery_order_reference'));
        $this->guestEmail = trim((string) Tools::getValue('packetery_email'));

        if (Tools::getValue('token') !== Tools::getToken(false)) {
            $this->guestError = $this->getModule()->l('Invalid security token.', 'return');

            return;
        }

        $order = $this->findOrderByReferenceAndEmail($this->guestReference, $this->guestEmail);
        if ($order === null) {
            $this->guestError = $this->getModule()->l('No order matches this number and e-mail address.', 'return');

            return;
        }

        $orderId = (int) $order->id;

        // Prefill the phone in the confirm form: what the customer just typed, else the order address.
        $postedPhone = trim((string) Tools::getValue('packetery_phone'));
        if ($postedPhone !== '') {
            $this->guestPhone = $postedPhone;
        } else {
            $address = new Address((int) $order->id_address_delivery);
            if (Validate::isLoadedObject($address)) {
                $this->guestPhone = AddressTools::resolveContactPhone($address);
            }
        }

        try {
            $section = $this->buildSection($orderId);

            // create only from the form state (eligible, no active return); guards a duplicate on re-POST
            if (Tools::isSubmit('submitPacketeryReturnGuestCreate') && $section['returnState'] === CustomerReturnSectionProvider::STATE_FORM) {
                $result = $this->createReturn($orderId);
                if ($result->isError()) {
                    $this->guestError = $this->getModule()->l('The return could not be created. Please check your e-mail and phone number and try again, or contact the e-shop.', 'return');
                }
                $section = $this->buildSection($orderId);
            }

            $this->applyGuestView($section);
        } catch (Exception $exception) {
            $this->guestError = $this->getModule()->l('The return could not be prepared.', 'return');
        }
    }

    /**
     * @param array{returnState: string, returnClaimId: string, returnTrackingUrl: string, returnHistory: list<array{claimId: string, status: string, trackingUrl: string, dateAdd: string}>} $section
     */
    private function applyGuestView(array $section): void
    {
        if ($section['returnState'] === CustomerReturnSectionProvider::STATE_CREATED) {
            $this->guestView = 'created';
            $this->guestClaimId = $section['returnClaimId'];
            $this->guestTrackingUrl = $section['returnTrackingUrl'];

            return;
        }

        if ($section['returnState'] === CustomerReturnSectionProvider::STATE_PENDING) {
            $this->guestView = 'pending';

            return;
        }

        if ($section['returnState'] === CustomerReturnSectionProvider::STATE_FORM) {
            $this->guestView = 'confirm';

            return;
        }

        // the order exists but cannot be returned; keep the lookup form (guestView stays 'lookup') so the
        // customer can try another order number without going back. This is a notice (not a data error like
        // an unmatched lookup), so it uses the warning style.
        $this->guestNotice = $this->getModule()->l('This order is not eligible for a return.', 'return');
    }

    private function assignGuestView(): void
    {
        $this->context->smarty->assign([
            'guestView' => $this->guestView,
            'guestError' => $this->guestError,
            'guestNotice' => $this->guestNotice,
            'guestReference' => $this->guestReference,
            'guestEmail' => $this->guestEmail,
            'guestPhone' => $this->guestPhone,
            'guestClaimId' => $this->guestClaimId,
            'guestTrackingUrl' => $this->guestTrackingUrl,
            'returnActionUrl' => $this->context->link->getModuleLink(Packetery::MODULE_SLUG, 'return'),
            'returnToken' => Tools::getToken(false),
        ]);
    }

    /**
     * @return array{returnState: string, returnClaimId: string, returnTrackingUrl: string, returnHistory: list<array{claimId: string, status: string, trackingUrl: string, dateAdd: string}>}
     *
     * @throws Packetery\Exceptions\DatabaseException
     * @throws PrestaShopException
     */
    private function buildSection(int $orderId): array
    {
        return $this->getModule()->diContainer->get(CustomerReturnSectionProvider::class)->build($orderId);
    }

    /**
     * Creates the return with the contact entered in the form: the customer may
     * edit the e-mail and add/change the phone. Values are passed through as entered (trimmed): a blank
     * e-mail is rejected, a blank phone is dropped. The factory falls back to the order contact only on
     * a null override, which happens on the approval path (pending rows store a blank contact as NULL).
     *
     * @throws Packetery\Exceptions\DatabaseException
     */
    private function createReturn(int $orderId): ReturnSubmissionResult
    {
        $email = trim((string) Tools::getValue('packetery_email'));
        $phone = trim((string) Tools::getValue('packetery_phone'));

        return $this->getModule()->diContainer->get(ClaimSubmitter::class)
            ->submit($orderId, ReturnEntity::SOURCE_CUSTOMER, $email, $phone);
    }

    /**
     * Loads the order only when it belongs to the logged-in customer (registered flow).
     */
    private function resolveOwnedOrder(int $orderId): ?Order
    {
        if ($orderId <= 0 || !$this->context->customer->isLogged()) {
            return null;
        }

        $order = new Order($orderId);
        if (!Validate::isLoadedObject($order) || (int) $order->id_customer !== (int) $this->context->customer->id) {
            return null;
        }

        return $order;
    }

    /**
     * Finds the order whose reference matches and whose customer e-mail matches (guest credential).
     */
    private function findOrderByReferenceAndEmail(string $reference, string $email): ?Order
    {
        if ($reference === '' || $email === '') {
            return null;
        }

        foreach (Order::getByReference($reference) as $orderRow) {
            $order = new Order((int) $orderRow->id);
            if (!Validate::isLoadedObject($order)) {
                continue;
            }

            $customer = new Customer((int) $order->id_customer);
            if (Validate::isLoadedObject($customer) && strtolower($customer->email) === strtolower($email)) {
                return $order;
            }
        }

        return null;
    }

    /**
     * @param string|null $status 'created', 'error', or null (shown as a flash in the order detail)
     *
     * @throws PrestaShopException
     */
    private function redirectToOrderDetail(int $orderId, ?string $status): void
    {
        $params = ['id_order' => $orderId];
        if ($status !== null) {
            $params['packetery_return'] = $status;
        }

        Tools::redirect($this->context->link->getPageLink('order-detail', true, null, $params));
    }

    private function getModule(): Packetery
    {
        /** @var Packetery $module */
        $module = $this->module;

        return $module;
    }
}
