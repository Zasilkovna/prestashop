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

use Packetery\Exceptions\DatabaseException;
use Packetery\SetupGuide\SetupGuide;
use Packetery\SetupGuide\SetupGuidePresenter;
use Packetery\SetupGuide\SetupGuideStateFactory;

class PacketeryHomeController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;

        parent::__construct();
    }

    /**
     * @throws SmartyException
     * @throws ReflectionException
     */
    public function initContent(): void
    {
        $this->context->smarty->assign([
            'setupGuide' => $this->getSetupGuideData(),
        ]);
        $this->content = $this->context->smarty->fetch(__DIR__ . '/../../views/templates/admin/home/home.tpl');

        parent::initContent();
    }

    /**
     * @param bool $isNewTheme
     */
    public function setMedia($isNewTheme = false): void
    {
        parent::setMedia($isNewTheme);

        $this->addCSS(
            "{$this->module->getPathUri()}views/css/home.css?v={$this->module->version}",
            'all',
            null,
            false
        );
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws ReflectionException
     */
    private function getSetupGuideData(): ?array
    {
        /** @var Packetery $module */
        $module = $this->module;

        try {
            $setupGuide = new SetupGuide($module->diContainer->get(SetupGuideStateFactory::class)->create());
        } catch (DatabaseException $exception) {
            $this->errors[] = $module->l('The setup guide cannot be shown because of a database error.', 'packeteryhomecontroller');

            return null;
        }

        return (new SetupGuidePresenter($module))->present($setupGuide);
    }
}
