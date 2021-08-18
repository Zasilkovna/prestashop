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
        $files = glob(dirname(__FILE__) . '/../../labels/*.pdf', GLOB_NOSORT);
        $shiftDays = Configuration::get('PACKETERY_LABEL_MAX_AGE_DAYS');
        if ($shiftDays === false) {
            $this->logErrorMessage('Configuration can not be loaded.');
            return;
        }

        $shift = 60 * 60 * 24 * $shiftDays;
        $limit = time() - $shift;

        foreach ($files as $label) {
            $labelName = basename($label);
            $fileTime = filemtime($label);
            if ($fileTime === false) {
                $this->logErrorMessage('Failed to retrieve file time for label "' . $labelName . '".');
                continue;
            }

            if ($fileTime < $limit) {
                $result = unlink($label);
                if ($result === false) {
                    $this->logErrorMessage('Failed to remove label "' . $labelName . '". Check permissions.');
                    continue;
                }
            }
        }
    }

    /**
     * @return void
     */
    public function display()
    {
        $this->ajax = 1;

        if ($this->validateToken() === false) {
            $this->logErrorMessage('Invalid packetery cron token for task.');
            return;
        }

        $task = \Tools::getValue('task', null);
        if (!$task) {
            $this->logErrorMessage('Cron task to run was not specified.');
            return;
        }

        $method = 'run' . ucfirst($task);
        if (method_exists($this, $method) === false) {
            $this->logErrorMessage('Task was not found.');
            return;
        }

        call_user_func([$this, $method]);
    }

    /**
     * @param string $message
     */
    private function logErrorMessage($message)
    {
        PrestaShopLogger::addLog('[packetery:cron]: ' . $message, 3, null, null, null, true);
    }

    /**
     * @return bool
     */
    private function validateToken()
    {
        $token = \Tools::getValue('token');
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
