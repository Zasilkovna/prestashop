<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Returns;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Order\ClaimCanceller;
use Packetery\Order\ClaimSubmitter;

/**
 * Processes the create/cancel-return actions submitted from the admin order detail.
 * Mirrors PacketCanceller::processOrderDetail: reads the POST, acts, and appends messages
 * (string or ['text' => ..., 'class' => 'success'|'danger']) for the order-detail template.
 */
class ReturnOrderDetailHandler
{
    /** @var \Packetery */
    private $module;
    /** @var ReturnCreationGate */
    private $creationGate;
    /** @var ClaimSubmitter */
    private $claimSubmitter;
    /** @var ClaimCanceller */
    private $claimCanceller;
    /** @var ReturnRepository */
    private $returnRepository;
    /** @var ReturnApprover */
    private $returnApprover;

    public function __construct(
        \Packetery $module,
        ReturnCreationGate $creationGate,
        ClaimSubmitter $claimSubmitter,
        ClaimCanceller $claimCanceller,
        ReturnRepository $returnRepository,
        ReturnApprover $returnApprover
    ) {
        $this->module = $module;
        $this->creationGate = $creationGate;
        $this->claimSubmitter = $claimSubmitter;
        $this->claimCanceller = $claimCanceller;
        $this->returnRepository = $returnRepository;
        $this->returnApprover = $returnApprover;
    }

    /**
     * @param array<int, string|array{text: string, class: string}> $messages
     *
     * @return array<int, string|array{text: string, class: string}>
     *
     * @throws \Packetery\Exceptions\DatabaseException
     * @throws \PrestaShopException
     */
    public function processOrderDetail(array $messages): array
    {
        if (\Tools::isSubmit('process_create_return')) {
            return $this->handleCreate($messages, (int) \Tools::getValue('id_order'));
        }

        if (\Tools::isSubmit('process_cancel_return')) {
            return $this->handleCancel($messages, (int) \Tools::getValue('id_return'));
        }

        if (\Tools::isSubmit('process_approve_return')) {
            return $this->handleApprove($messages, (int) \Tools::getValue('id_return'));
        }

        if (\Tools::isSubmit('process_reject_return')) {
            return $this->handleReject($messages, (int) \Tools::getValue('id_return'));
        }

        return $messages;
    }

    /**
     * @param array<int, string|array{text: string, class: string}> $messages
     *
     * @return array<int, string|array{text: string, class: string}>
     *
     * @throws \Packetery\Exceptions\DatabaseException
     * @throws \PrestaShopException
     */
    private function handleCreate(array $messages, int $orderId): array
    {
        if (!$this->creationGate->canCreate($orderId)) {
            // server-side gate: the button is hidden in this state, so this is a forged/stale call
            $messages[] = ['text' => $this->module->l('The return could not be created.', 'returnorderdetailhandler'), 'class' => 'danger'];

            return $messages;
        }

        // admin-created returns always go straight to Packeta (never pending), so only created/error apply
        $result = $this->claimSubmitter->submit($orderId, ReturnEntity::SOURCE_ADMIN);
        if ($result->isCreated()) {
            $messages[] = ['text' => $this->module->l('The return was successfully created.', 'returnorderdetailhandler'), 'class' => 'success'];

            return $messages;
        }

        $messages[] = ['text' => $this->module->l('The return could not be created. More information can be found in the log.', 'returnorderdetailhandler'), 'class' => 'danger'];

        return $messages;
    }

    /**
     * @param array<int, string|array{text: string, class: string}> $messages
     *
     * @return array<int, string|array{text: string, class: string}>
     *
     * @throws \Packetery\Exceptions\DatabaseException
     */
    private function handleCancel(array $messages, int $idReturn): array
    {
        $return = $this->returnRepository->getById($idReturn);
        if ($return === null || $return->getStatus() !== ReturnEntity::STATUS_CREATED) {
            // the cancel button is only rendered for active returns, so this is a forged/stale call
            $messages[] = ['text' => $this->module->l('The return could not be cancelled.', 'returnorderdetailhandler'), 'class' => 'danger'];

            return $messages;
        }

        $response = $this->claimCanceller->cancel($idReturn);
        if (!$response->hasFault()) {
            $messages[] = ['text' => $this->module->l('The return was successfully cancelled in Packeta.', 'returnorderdetailhandler'), 'class' => 'success'];

            return $messages;
        }

        $messages[] = ['text' => $this->module->l('The return could not be cancelled. More information can be found in the log.', 'returnorderdetailhandler'), 'class' => 'danger'];

        return $messages;
    }

    /**
     * @param array<int, string|array{text: string, class: string}> $messages
     *
     * @return array<int, string|array{text: string, class: string}>
     *
     * @throws \Packetery\Exceptions\DatabaseException
     */
    private function handleApprove(array $messages, int $idReturn): array
    {
        $result = $this->returnApprover->approve($idReturn);
        if ($result->isCreated()) {
            $messages[] = ['text' => $this->module->l('The return was approved and sent to Packeta.', 'returnorderdetailhandler'), 'class' => 'success'];

            return $messages;
        }

        $response = $result->getResponse();
        if ($response !== null && $response->hasFault()) {
            $messages[] = ['text' => $this->module->l('The return could not be approved. More information can be found in the log.', 'returnorderdetailhandler'), 'class' => 'danger'];

            return $messages;
        }

        // the approve button is only rendered for pending returns, so this is a forged/stale call
        $messages[] = ['text' => $this->module->l('The return could not be approved.', 'returnorderdetailhandler'), 'class' => 'danger'];

        return $messages;
    }

    /**
     * @param array<int, string|array{text: string, class: string}> $messages
     *
     * @return array<int, string|array{text: string, class: string}>
     *
     * @throws \Packetery\Exceptions\DatabaseException
     */
    private function handleReject(array $messages, int $idReturn): array
    {
        if ($this->returnApprover->reject($idReturn)) {
            $messages[] = ['text' => $this->module->l('The return was rejected.', 'returnorderdetailhandler'), 'class' => 'success'];

            return $messages;
        }

        // the reject button is only rendered for pending returns, so this is a forged/stale call
        $messages[] = ['text' => $this->module->l('The return could not be rejected.', 'returnorderdetailhandler'), 'class' => 'danger'];

        return $messages;
    }
}
