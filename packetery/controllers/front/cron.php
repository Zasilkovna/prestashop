<?php

use Packetery\Cron\Tasks\DeleteLabels;

class PacketeryCronModuleFrontController extends ModuleFrontController
{
    /** @var bool If set to true, will be redirected to authentication page */
    public $auth = false;

    /** @var bool */
    public $ajax = true;

    /** @var bool */
    private $hasError = false;

    /**
     * @return void
     */
    public function display()
    {
        ignore_user_abort(true); // Ignore connection-closing by the client/user
        @ob_end_clean();

        $this->renderMessage($this->module->l('Cron started.', 'cron'));

        if ($this->validateToken() === false) {
            $this->renderErrorMessage($this->module->l('Invalid packetery cron token.', 'cron'));
            return;
        }

        $task = Tools::getValue('task', null);
        if (!$task) {
            $this->renderErrorMessage($this->module->l('Cron task to run was not specified.', 'cron'));
            return;
        }

        $this->renderMessage(
            sprintf(
                $this->module->l('Task "%s" is about to be executed.', 'cron'),
                $task
            )
        );

        switch ($task) {
            case DeleteLabels::getTaskName():
                (new DeleteLabels($this->module, [$this, 'renderErrorMessage']))->execute();
                break;
            default:
                $this->renderErrorMessage($this->module->l('Task was not found.', 'cron'));
        }

        if ($this->hasError) {
            $this->renderMessage($this->module->l('All rendered errors are persisted in Prestashop database.', 'cron'));
        }

        $this->renderMessage($this->module->l('Cron finished.', 'cron'));
        exit; // to avoid Prestashop calling ob_end_flush()
    }

    /**
     * @param string $message
     * @return void
     */
    public function renderMessage($message)
    {
        $templateFilePath = dirname(__FILE__) . '/../../views/templates/front/cron-message-row.tpl';
        $template = $this->context->smarty->createTemplate($templateFilePath, [
            'message' => $message
        ]);

        ob_start();
        echo $template->fetch();
        ob_end_flush();
    }

    /**
     * @param string $message
     * @return void
     */
    public function renderErrorMessage($message)
    {
        $this->hasError = true;
        $this->renderMessage($message);
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

        $storedToken = Configuration::get('PACKETERY_CRON_TOKEN');
        if ($storedToken === false) {
            return false;
        }

        return $storedToken === $token;
    }
}
