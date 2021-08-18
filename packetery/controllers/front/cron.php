<?php

class PacketeryCronModuleFrontController extends ModuleFrontController
{
    /** @var bool If set to true, will be redirected to authentication page */
    public $auth = false;

    /** @var bool */
    public $ajax;

    /**
     * Deletes labels if they are older than specified number of days.
     */
    private function runDeleteLabels()
    {
        $this->renderMessage(sprintf($this->module->l('Task "%s" is about to be executed.','cron'), Tools::getValue('task')));

        $files = glob(dirname(__FILE__) . '/../../labels/*.pdf', GLOB_NOSORT);
        $shiftDays = Configuration::get('PACKETERY_LABEL_MAX_AGE_DAYS');
        if ($shiftDays === false) {
            $this->logErrorMessage($this->module->l('Configuration can not be loaded.','cron'));
            return;
        }

        $shift = 60 * 60 * 24 * $shiftDays;
        $limit = time() - $shift;

        foreach ($files as $label) {
            $labelName = basename($label);
            $fileTime = filemtime($label);
            if ($fileTime === false) {
                $this->logErrorMessage(sprintf($this->module->l('Failed to retrieve file time for label "%s".','cron'), $labelName));
                continue;
            }

            if ($fileTime < $limit) {
                $result = unlink($label);
                if ($result === false) {
                    $this->logErrorMessage(sprintf($this->module->l('Failed to remove label "%s". Check permissions.','cron'), $labelName));
                    continue;
                }

                $this->renderMessage(sprintf($this->module->l('Label "%s" was removed.','cron'), $labelName));
            }
        }

        $this->renderMessage(sprintf($this->module->l('Task "%s" finished.','cron'), Tools::getValue('task')));
    }

    /**
     * @return void
     */
    public function display()
    {
        $this->ajax = 1;
        $this->renderMessage($this->module->l('Cron started.','cron'));

        if ($this->validateToken() === false) {
            $this->logErrorMessage($this->module->l('Invalid packetery cron token.','cron'));
            return;
        }

        $task = Tools::getValue('task', null);
        if (!$task) {
            $this->logErrorMessage($this->module->l('Cron task to run was not specified.','cron'));
            return;
        }

        switch ($task) {
            case 'deleteLabels':
                $this->runDeleteLabels();
                break;
            default:
                $this->logErrorMessage($this->module->l('Task was not found.','cron'));
        }

        $this->renderMessage($this->module->l('Cron finished.','cron'));
    }

    /**
     * @param string $message
     * @throws \PrestaShopException
     */
    private function renderMessage($message)
    {
        $templateFilePath = dirname(__FILE__) . '/../../views/templates/front/cron-message-row.tpl';
        $template = $this->context->smarty->createTemplate($templateFilePath, [
            'message' => $message
        ]);
        echo $template->fetch();
    }

    /**
     * @param string $message
     */
    private function logErrorMessage($message)
    {
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
