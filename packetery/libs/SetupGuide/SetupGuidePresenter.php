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

use Packetery\Cron\Tasks\DownloadCarriers;
use Packetery\Cron\Tasks\GetConsignPassword;
use Packetery\Cron\Tasks\UpdatePacketStatus;
use Packetery\Module\ConfigurationPage;

/**
 * @phpstan-type Button array{label: string, url: string, isPrimary: bool}
 * @phpstan-type CronTask array{id: string, name: string, lastRun: string|null}
 * @phpstan-type Step array{
 *     id: string,
 *     number: int|null,
 *     title: string,
 *     description: string,
 *     location: string,
 *     isDone: bool,
 *     isInformative: bool,
 *     showOptionalLabel: bool,
 *     buttons: list<Button>,
 *     cronTasks: list<CronTask>
 * }
 */
class SetupGuidePresenter
{
    /** @var \Packetery */
    private $module;

    public function __construct(\Packetery $module)
    {
        $this->module = $module;
    }

    /**
     * @return array{
     *     doneCount: int,
     *     stepCount: int,
     *     progressPercent: int,
     *     steps: list<Step>
     * }
     */
    public function present(SetupGuide $setupGuide): array
    {
        $steps = [];
        foreach ($setupGuide->getStepIds() as $index => $stepId) {
            $isDone = $setupGuide->isStepDone($stepId);
            $texts = $this->getStepTexts($stepId);
            $steps[] = [
                'id' => $stepId,
                'number' => $index + 1,
                'title' => $texts['title'],
                'description' => $texts['description'],
                'location' => $texts['location'],
                'isDone' => $isDone,
                'isInformative' => false,
                'showOptionalLabel' => $setupGuide->isStepOptional($stepId) && !$isDone,
                'buttons' => $this->getStepButtons($setupGuide, $stepId, $isDone),
                'cronTasks' => $stepId === SetupGuideStep::CRON_JOBS ? $this->getCronTasks($setupGuide) : [],
            ];
        }
        $stepCount = count($steps);
        $doneCount = count(array_filter(array_column($steps, 'isDone')));
        $steps[] = $this->getAdultProductsStep();

        return [
            'doneCount' => $doneCount,
            'stepCount' => $stepCount,
            'progressPercent' => (int) round($doneCount / $stepCount * 100),
            'steps' => $steps,
        ];
    }

    /**
     * @return array{title: string, description: string, location: string}
     */
    private function getStepTexts(string $stepId): array
    {
        $configurationLocation = $this->decode($this->module->l('Packeta › Configuration', 'setupguidepresenter'));

        switch ($stepId) {
            case SetupGuideStep::BASIC_SETTINGS:
                return [
                    'title' => $this->decode($this->module->l('Basic module settings', 'setupguidepresenter')),
                    'description' => $this->decode($this->module->l('Enter the API password and the sender indication from the Packeta client section. Without them, the module cannot communicate with Packeta.', 'setupguidepresenter')),
                    'location' => $configurationLocation,
                ];
            case SetupGuideStep::CARRIERS_UPDATE:
                return [
                    'title' => $this->decode($this->module->l('Packeta carriers update', 'setupguidepresenter')),
                    'description' => $this->decode($this->module->l('The list of carriers is downloaded automatically after you enter the API password. You can also update it manually at any time – try it with the Manually update the list of carriers button.', 'setupguidepresenter')),
                    'location' => $this->decode($this->module->l('Packeta › Carrier settings', 'setupguidepresenter')),
                ];
            case SetupGuideStep::CARRIERS:
                return [
                    'title' => $this->decode($this->module->l('Carriers for Packeta', 'setupguidepresenter')),
                    'description' => $this->decode($this->module->l('Create carriers in PrestaShop (prices, zones, weight limits) and assign Packeta pickup points or an external carrier to them in Carrier settings.', 'setupguidepresenter')),
                    'location' => $this->decode($this->module->l('Shipping › Carriers · Packeta › Carrier settings', 'setupguidepresenter')),
                ];
            case SetupGuideStep::COD_PAYMENTS:
                return [
                    'title' => $this->decode($this->module->l('Cash on delivery payments', 'setupguidepresenter')),
                    'description' => $this->decode($this->module->l('Mark the payment methods that represent cash on delivery so that packets are submitted with the correct amount to collect.', 'setupguidepresenter')),
                    'location' => $configurationLocation,
                ];
            case SetupGuideStep::PACKET_STATUS_TRACKING:
                return [
                    'title' => $this->decode($this->module->l('Packet status tracking', 'setupguidepresenter')),
                    'description' => $this->decode($this->module->l('Keep track of the current status of your packets. The status is shown in the Packeta orders list.', 'setupguidepresenter')),
                    'location' => $configurationLocation,
                ];
            case SetupGuideStep::ORDER_STATUS_CHANGE:
                return [
                    'title' => $this->decode($this->module->l('Order status change', 'setupguidepresenter')),
                    'description' => $this->decode($this->module->l('Let the module change the order status based on the packet status, e.g. after the packet is delivered.', 'setupguidepresenter')),
                    'location' => $configurationLocation,
                ];
            case SetupGuideStep::CRON_JOBS:
                return [
                    'title' => $this->decode($this->module->l('CRON jobs', 'setupguidepresenter')),
                    'description' => $this->decode($this->module->l('Set up a cron job on your server that regularly calls the URL of each task. A task is checked after its first run.', 'setupguidepresenter')),
                    'location' => $configurationLocation,
                ];
            default:
                throw new \LogicException("Setup guide step without texts: {$stepId}");
        }
    }

    /**
     * @return list<Button>
     */
    private function getStepButtons(
        SetupGuide $setupGuide,
        string $stepId,
        bool $isDone
    ): array {
        $primaryUrl = $this->getPrimaryUrl($setupGuide, $stepId);
        if ($isDone) {
            return [$this->createButton($this->decode($this->module->l('Edit', 'setupguidepresenter')), $primaryUrl, false)];
        }

        $buttons = [];
        if ($stepId === SetupGuideStep::CARRIERS) {
            $buttons[] = $this->createButton(
                $this->decode($this->module->l('Create carrier', 'setupguidepresenter')),
                $this->module->getAdminLink('AdminCarriers'),
                false
            );
        }
        $buttons[] = $this->createButton($this->getPrimaryLabel($stepId), $primaryUrl, true);

        return $buttons;
    }

    private function getPrimaryUrl(SetupGuide $setupGuide, string $stepId): string
    {
        $packetStatusTrackingTab = [ConfigurationPage::TAB_PARAMETER => ConfigurationPage::PACKET_STATUS_TRACKING_TAB];

        switch ($stepId) {
            case SetupGuideStep::CARRIERS_UPDATE:
            case SetupGuideStep::CARRIERS:
                return $this->module->getAdminLink('PacketeryCarrierGrid');
            case SetupGuideStep::COD_PAYMENTS:
                $paymentModuleName = $setupGuide->getFirstPaymentModuleName();

                return $this->getConfigurationUrl([], $paymentModuleName === null ? '' : '#' . ConfigurationPage::PAYMENT_COD_ANCHOR_PREFIX . $paymentModuleName);
            case SetupGuideStep::PACKET_STATUS_TRACKING:
                return $this->getConfigurationUrl($packetStatusTrackingTab);
            case SetupGuideStep::ORDER_STATUS_CHANGE:
                return $this->getConfigurationUrl($packetStatusTrackingTab, '#' . ConfigurationPage::ORDER_STATUS_CHANGE_ANCHOR);
            case SetupGuideStep::CRON_JOBS:
                return $this->getConfigurationUrl([], '#' . ConfigurationPage::CRON_JOBS_ANCHOR);
            default:
                return $this->getConfigurationUrl();
        }
    }

    private function getPrimaryLabel(string $stepId): string
    {
        switch ($stepId) {
            case SetupGuideStep::CARRIERS_UPDATE:
                return $this->decode($this->module->l('Try it', 'setupguidepresenter'));
            case SetupGuideStep::CARRIERS:
                return $this->decode($this->module->l('Pair', 'setupguidepresenter'));
            case SetupGuideStep::CRON_JOBS:
                return $this->decode($this->module->l('Show URLs', 'setupguidepresenter'));
            default:
                return $this->decode($this->module->l('Set up', 'setupguidepresenter'));
        }
    }

    /**
     * @return Step
     */
    private function getAdultProductsStep(): array
    {
        return [
            'id' => SetupGuideStep::ADULT_PRODUCTS,
            'number' => null,
            'title' => $this->decode($this->module->l('Products for adults only', 'setupguidepresenter')),
            'description' => $this->decode($this->module->l('You can mark any product as intended for adults only on its product page. The recipient\'s age is then verified upon delivery of a packet containing such a product.', 'setupguidepresenter')),
            'location' => $this->decode($this->module->l('Catalog › Products › product page', 'setupguidepresenter')),
            'isDone' => false,
            'isInformative' => true,
            'showOptionalLabel' => false,
            'buttons' => [
                $this->createButton($this->decode($this->module->l('Go to products', 'setupguidepresenter')), $this->module->getAdminLink('AdminProducts'), false),
            ],
            'cronTasks' => [],
        ];
    }

    /**
     * @return list<CronTask>
     */
    private function getCronTasks(SetupGuide $setupGuide): array
    {
        $cronTasks = [];
        foreach ($setupGuide->getVisibleCronTasks() as $taskName => $lastRun) {
            $cronTasks[] = [
                'id' => $taskName,
                'name' => $this->getCronTaskName($taskName),
                'lastRun' => $lastRun === null ? null : date('Y-m-d H:i:s', $lastRun),
            ];
        }

        return $cronTasks;
    }

    private function getCronTaskName(string $taskName): string
    {
        switch ($taskName) {
            case DownloadCarriers::getTaskName():
                return $this->decode($this->module->l('Carrier list update', 'setupguidepresenter'));
            case UpdatePacketStatus::getTaskName():
                return $this->decode($this->module->l('Packet tracking status update', 'setupguidepresenter'));
            case GetConsignPassword::getTaskName():
                return $this->decode($this->module->l('Get consignment code', 'setupguidepresenter'));
            default:
                throw new \LogicException("Setup guide cron task without name: {$taskName}");
        }
    }

    /**
     * Module::l() escapes already, the template escapes again
     */
    private function decode(string $translatedText): string
    {
        return html_entity_decode($translatedText, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param array<string, string> $parameters
     */
    private function getConfigurationUrl(array $parameters = [], string $anchor = ''): string
    {
        return $this->module->getAdminLink('AdminModules', ['configure' => \Packetery::MODULE_SLUG] + $parameters, $anchor);
    }

    /**
     * @return Button
     */
    private function createButton(
        string $label,
        string $url,
        bool $isPrimary
    ): array {
        return [
            'label' => $label,
            'url' => $url,
            'isPrimary' => $isPrimary,
        ];
    }
}
