<?php

namespace Packetery\Cron\Tasks;

use Packetery;
use Packetery\Tools\Tools;

/**
 * Deletes labels if they are older than specified number of days.
 */
class DeleteLabels extends Base
{
    /** @var Packetery */
    public $module;

    /**
     * Delete files older than $defaultNumberOfDays
     */
    const defaultNumberOfDays = 7;

    /**
     * Delete number of files in one batch
     */
    const defaultNumberOfFiles = 500;

    /** @var int set default limit to delete PDF labels equals 1 day */
    private $limit = 86400;

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
        $getLabels = glob(PACKETERY_PLUGIN_DIR . '/labels/*.pdf', GLOB_NOSORT);

        $deleteNumberOfDays = (int)Tools::getValue('number_of_days', self::defaultNumberOfDays);
        $deleteNumberOfFiles = (int)Tools::getValue('number_of_files', self::defaultNumberOfFiles);

        if ($deleteNumberOfDays <= 0) {
            $errors['deleteNumberOfDays'] = $this->module->l(
                'Only positive number value for number_of_days parameter possible.',
                'DeleteLabels'
            );
            return $errors;
        }

        if ($deleteNumberOfFiles <= 0) {
            $errors['deleteNumberOfFiles'] = $this->module->l(
                'Only positive number value for number_of_files parameter possible.',
                'DeleteLabels'
            );
            return $errors;
        }

        $deleteNumberOfDays = $this->limit * $deleteNumberOfDays;
        $this->limit = time() - $deleteNumberOfDays;

        $files = array_filter($getLabels, function ($labelPath) {
            return filemtime($labelPath) < $this->limit;
        });

        $files = array_slice($files, 0, $deleteNumberOfFiles);

        foreach ($files as $label) {
            $fileTime = filemtime($label);
            if ($fileTime === false) {
                $errors['filemtime'] = $this->module->l(
                    'Failed to retrieve file time for some labels. Check file permissions.',
                    'DeleteLabels'
                );
                continue;
            }

            if (unlink($label) === false) {
                $errors['unlink'] = $this->module->l(
                    'Failed to remove some labels. Check file permissions.',
                    'DeleteLabels'
                );
                continue;
            }
        }

        return $errors;
    }
}
