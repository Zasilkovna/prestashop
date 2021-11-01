<?php

namespace Packetery\Cron\Tasks;

/**
 * Deletes labels if they are older than specified number of days.
 */
class DeleteLabels extends Base
{
    /** @var \Module */
    public $module;

    /**
     * DeleteLabels constructor.
     *
     * @param \Module $module
     */
    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    /**
     * @return string[]
     */
    public function execute()
    {
        $errors = [];
        $files = glob(PACKETERY_PLUGIN_DIR . '/labels/*.pdf', GLOB_NOSORT);
        $shiftDays = \Configuration::get('PACKETERY_LABEL_MAX_AGE_DAYS');
        if ($shiftDays === false) {
            $errors[] = $this->module->l('Configuration can not be loaded.', 'DeleteLabels');
            return $errors;
        }

        $shift = 60 * 60 * 24 * $shiftDays;
        $limit = time() - $shift;

        foreach ($files as $label) {
            $fileTime = filemtime($label);
            if ($fileTime === false) {
                $errors['filemtime'] = $this->module->l(
                    'Failed to retrieve file time for some labels. Check file permissions.', 'DeleteLabels'
                );
                continue;
            }

            if ($fileTime < $limit) {
                $result = unlink($label);
                if ($result === false) {
                    $errors['unlink'] = $this->module->l(
                        'Failed to remove some labels. Check file permissions.', 'DeleteLabels'
                    );
                    continue;
                }
            }
        }

        return $errors;
    }
}
