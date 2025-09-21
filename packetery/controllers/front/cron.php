<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Cron\Tasks\DeleteLabels;
use Packetery\Cron\Tasks\DownloadCarriers;
use Packetery\Cron\Tasks\PurgeLogs;
use Packetery\Cron\Tasks\UpdatePacketStatus;
use Packetery\Tools\ConfigHelper;

class PacketeryCronModuleFrontController extends ModuleFrontController
{
    /** @var bool If set to true, will be redirected to authentication page */
    public $auth = false;

    /** @var bool */
    public $ajax = true;

    /** @var bool */
    private $hasError = false;

    /** @var Packetery */
    public $module;

    /**
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     * @throws Packetery\Exceptions\DatabaseException
     * @throws SmartyException
     */
    public function display()
    {
        ignore_user_abort(true); // Ignore connection-closing by the client/user

        if ($this->validateToken() === false) {
            $this->renderError($this->module->getTranslator()->trans('Invalid packetery cron token.', [], 'Modules.Packetery.Packeterycronmodulefront'));

            return;
        }

        $task = Tools::getValue('task', null);
        if (!$task) {
            $this->renderError($this->module->getTranslator()->trans('Cron task to run was not specified.', [], 'Modules.Packetery.Packeterycronmodulefront'));

            return;
        }

        switch ($task) {
            case DeleteLabels::getTaskName():
                $taskName = $this->module->getTranslator()->trans('Deleting labels', [], 'Modules.Packetery.Packeterycronmodulefront');
                break;
            case DownloadCarriers::getTaskName():
                $taskName = $this->module->getTranslator()->trans('Carrier list update', [], 'Modules.Packetery.Packeterycronmodulefront');
                break;
            case UpdatePacketStatus::getTaskName():
                $taskName = $this->module->getTranslator()->trans('Packet tracking status update', [], 'Modules.Packetery.Packeterycronmodulefront');
                break;
            default:
                $taskName = $task;
        }

        $this->renderMessage(
            '[' . $this->module->getTranslator()->trans('BEGIN', [], 'Modules.Packetery.Packeterycronmodulefront') . ']: ' .
            sprintf(
                $this->module->getTranslator()->trans('Task "%s" is about to be executed.', [], 'Modules.Packetery.Packeterycronmodulefront'),
                $taskName
            )
        );

        switch ($task) {
            case DeleteLabels::getTaskName():
                $deleteLabels = $this->module->diContainer->get(DeleteLabels::class);
                $errors = $deleteLabels->execute();
                $this->renderErrors($errors);
                break;
            case DownloadCarriers::getTaskName():
                $downloadCarriers = $this->module->diContainer->get(DownloadCarriers::class);
                $errors = $downloadCarriers->execute();
                $this->renderErrors($errors);
                break;
            case PurgeLogs::getTaskName():
                $purgeLogs = $this->module->diContainer->get(PurgeLogs::class);
                $errors = $purgeLogs->execute();
                $this->renderErrors($errors);
                break;
            case UpdatePacketStatus::getTaskName():
                $updatePacketStatus = $this->module->diContainer->get(UpdatePacketStatus::class);
                $errors = $updatePacketStatus->execute();
                $this->renderErrors($errors);
                break;
            default:
                $this->renderError($this->module->getTranslator()->trans('Task was not found.', [], 'Modules.Packetery.Packeterycronmodulefront'));
        }

        if ($this->hasError) {
            $this->renderMessage($this->module->getTranslator()->trans('All displayed errors are stored in the Prestashop database.', [], 'Modules.Packetery.Packeterycronmodulefront'));
        }

        $this->renderMessage(
            '[' . $this->module->getTranslator()->trans('END', [], 'Modules.Packetery.Packeterycronmodulefront') . ']: ' .
            sprintf(
                $this->module->getTranslator()->trans('Task "%s" was finished.', [], 'Modules.Packetery.Packeterycronmodulefront'),
                $taskName
            )
        );
    }

    /**
     * @param array $errors
     *
     * @return void
     *
     * @throws SmartyException
     */
    public function renderErrors(array $errors)
    {
        foreach ($errors as $error) {
            $this->renderError($error);
        }
    }

    /**
     * @param string $message
     *
     * @return void
     *
     * @throws SmartyException
     */
    public function renderMessage($message)
    {
        $templateFilePath = __DIR__ . '/../../views/templates/front/cron-message-row.tpl';
        $template = $this->context->smarty->createTemplate($templateFilePath, [
            'message' => $message,
        ]);

        // gzip compression forces browser to wait for all messages, no point in calling flush()
        echo $template->fetch();
    }

    /**
     * @param string $message
     *
     * @return void
     *
     * @throws SmartyException
     */
    public function renderError($message)
    {
        $this->hasError = true;
        $this->renderMessage('[' . $this->module->getTranslator()->trans('ERROR', [], 'Modules.Packetery.Packeterycronmodulefront') . ']: ' . $message);
        PrestaShopLogger::addLog('[packetery:cron]: ' . $message, 3, null, null, null, true);
    }

    /**
     * @return bool
     */
    private function validateToken()
    {
        $token = Tools::getValue('token');
        if ($token === false) {
            return false;
        }

        $storedToken = ConfigHelper::get('PACKETERY_CRON_TOKEN');
        if ($storedToken === false) {
            return false;
        }

        return $storedToken === $token;
    }
}
