<?php

namespace Packetery\Cron\Tasks;

use Packetery;
use Packetery\Tools\ConfigHelper;

/**
 * Deletes labels if they are older than specified number of days.
 */
class DeleteLabels extends Base
{
    /** @var Packetery */
    public $module;

    /**
     * DeleteLabels constructor.
     *
     * @param Packetery $module
     */
    public function __construct(Packetery $module)
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
        $shiftDays = ConfigHelper::get('PACKETERY_LABEL_MAX_AGE_DAYS');
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
