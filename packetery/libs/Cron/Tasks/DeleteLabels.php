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
    private $onRenderErrorMessage;

    /**
     * DeleteLabels constructor.
     *
     * @param \Module $module
     * @param callable $onRenderErrorMessage
     */
    public function __construct(\Module $module, $onRenderErrorMessage)
    {
        $this->module = $module;
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
                $this->module->l('Configuration can not be loaded.', 'DeleteLabels')
            );
            return;
        }

        $shift = 60 * 60 * 24 * $shiftDays;
        $limit = time() - $shift;

        $errorAggregator = [
            'filemtime' => false,
            'unlink' => false,
        ];

        foreach ($files as $label) {
            $fileTime = filemtime($label);
            if ($fileTime === false) {
                $errorAggregator['filemtime'] = true;
                continue;
            }

            if ($fileTime < $limit) {
                $result = unlink($label);
                if ($result === false) {
                    $errorAggregator['unlink'] = true;
                    continue;
                }
            }
        }

        if ($errorAggregator['filemtime']) {
            call_user_func(
                $this->onRenderErrorMessage,
                $this->module->l('Failed to retrieve file time for some labels. Check file permissions.', 'DeleteLabels')
            );
        }

        if ($errorAggregator['unlink']) {
            call_user_func(
                $this->onRenderErrorMessage,
                $this->module->l('Failed to remove some labels. Check file permissions.', 'DeleteLabels')
            );
        }
    }
}
