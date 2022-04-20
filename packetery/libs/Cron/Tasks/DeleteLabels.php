<?php

namespace Packetery\Cron\Tasks;

use Packetery;
use Packetery\Tools\ConfigHelper;
use Packetery\Tools\Tools;

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
        $deleteMaxNumberOfFiles = (int)Tools::getValue('number_of_files');
        $deleteNumberOfDays = Tools::getValue('number_of_days');

        if ($deleteNumberOfDays) {
            $shiftDays = $deleteNumberOfDays;
        }

        if ($shiftDays === false) {
            $errors[] = $this->module->l('Configuration can not be loaded.', 'DeleteLabels');
            return $errors;
        }

        $shift = 60 * 60 * 24 * $shiftDays;
        $limit = time() - $shift;

        $deletedFiles = 0;
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
                } else {
                    $deletedFiles++;
                    if ($deleteMaxNumberOfFiles === $deletedFiles) {
                        break;
                    }
                }
            }
        }

        return $errors;
    }
}
