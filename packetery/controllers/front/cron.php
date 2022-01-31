<?php

use Packetery\Cron\Tasks\DeleteLabels;
use Packetery\Cron\Tasks\DownloadCarriers;
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     * @throws \Packetery\Exceptions\DatabaseException
     * @throws SmartyException
     */
    public function display()
    {
        ignore_user_abort(true); // Ignore connection-closing by the client/user

        if ($this->validateToken() === false) {
            $this->renderError($this->module->l('Invalid packetery cron token.', 'cron'));
            return;
        }

        $task = Tools::getValue('task', null);
        if (!$task) {
            $this->renderError($this->module->l('Cron task to run was not specified.', 'cron'));
            return;
        }

        switch ($task) {
            case DeleteLabels::getTaskName():
                $taskName = $this->module->l('Deleting labels', 'cron');
                break;
            case DownloadCarriers::getTaskName():
                $taskName = $this->module->l('Carrier list update', 'cron');
                break;
            default:
                $taskName = $task;
        }

        $this->renderMessage(
            '[' . $this->module->l('BEGIN', 'cron') . ']: ' .
            sprintf(
                $this->module->l('Task "%s" is about to be executed.', 'cron'),
                $taskName
            )
        );

        switch ($task) {
            case DeleteLabels::getTaskName():
                $errors = (new DeleteLabels($this->module))->execute();
                $this->renderErrors($errors);
                break;
            case DownloadCarriers::getTaskName():
                $errors = (new DownloadCarriers($this->module))->execute();
                $this->renderErrors($errors);
                break;
            default:
                $this->renderError($this->module->l('Task was not found.', 'cron'));
        }

        if ($this->hasError) {
            $this->renderMessage($this->module->l('All displayed errors are stored in the Prestashop database.', 'cron'));
        }

        $this->renderMessage(
            '[' . $this->module->l('END', 'cron') . ']: ' .
            sprintf(
                $this->module->l('Task "%s" was finished.', 'cron'),
                $taskName
            )
        );
    }

    /**
     * @param array $errors
     * @return void
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
     * @return void
     * @throws SmartyException
     */
    public function renderMessage($message)
    {
        $templateFilePath = __DIR__ . '/../../views/templates/front/cron-message-row.tpl';
        $template = $this->context->smarty->createTemplate($templateFilePath, [
            'message' => $message
        ]);

        // gzip compression forces browser to wait for all messages, no point in calling flush()
        echo $template->fetch();
    }

    /**
     * @param string $message
     * @return void
     * @throws SmartyException
     */
    public function renderError($message)
    {
        $this->hasError = true;
        $this->renderMessage('[' . $this->module->l('ERROR', 'cron') . ']: ' . $message);
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
