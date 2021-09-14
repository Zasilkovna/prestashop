<?php

namespace Packetery\Cron\Tasks;

/**
 * Deletes labels if they are older than specified number of days.
 */
class DeleteLabels extends Base
{
    /** @var \Module */
    public $module;

    /** @var callback */
    private $onRenderMessage;

    /** @var callback */
    private $onRenderErrorMessage;

    /**
     * DeleteLabels constructor.
     *
     * @param \Module $module
     * @param callable $onRenderMessage
     * @param callable $onRenderErrorMessage
     */
    public function __construct(\Module $module, $onRenderMessage, $onRenderErrorMessage)
    {
        $this->module = $module;
        $this->onRenderMessage = $onRenderMessage;
        $this->onRenderErrorMessage = $onRenderErrorMessage;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $files = glob(PACKETERY_PLUGIN_DIR . '/labels/*.pdf', GLOB_NOSORT);
        $shiftDays = \Configuration::get('PACKETERY_LABEL_MAX_AGE_DAYS');
        if ($shiftDays === false) {
            call_user_func(
                $this->onRenderErrorMessage,
                $this->module->l('Configuration can not be loaded.', 'cron.DeleteLabels')
            );
            return;
        }

        $shift = 60 * 60 * 24 * $shiftDays;
        $limit = time() - $shift;

        foreach ($files as $label) {
            $labelName = basename($label);
            $fileTime = filemtime($label);
            if ($fileTime === false) {
                call_user_func(
                    $this->onRenderErrorMessage,
                    sprintf(
                        $this->module->l('Failed to retrieve file time for label "%s". Check file permissions.', 'cron.DeleteLabels'),
                        $labelName
                    )
                );
                continue;
            }

            if ($fileTime < $limit) {
                $result = unlink($label);
                if ($result === false) {
                    call_user_func(
                        $this->onRenderMessage,
                        sprintf(
                            $this->module->l('Failed to remove label "%s". Check file permissions.', 'cron.DeleteLabels'),
                            $labelName
                        )
                    );
                    continue;
                }
            }
        }
    }
}
