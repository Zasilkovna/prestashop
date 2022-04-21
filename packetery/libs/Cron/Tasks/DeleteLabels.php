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

    /** @var int Delete files older than $defaultNumberOfDays */
    public $defaultNumberOfDays = 7;

    /** @var int Delete number of files in one batch */
    public $defaultNumberOfFiles = 500;

    /** @var int set default limit to delete PDF labels equals 1 day */
    public $limit = 86400;

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

        $deleteNumberOfDays = Tools::getValue('number_of_days', $this->defaultNumberOfDays);
        $deleteNumberOfFiles = Tools::getValue('number_of_files', $this->defaultNumberOfFiles);

        $deleteNumberOfDays = $this->limit * $deleteNumberOfDays;
        $this->limit = time() - $deleteNumberOfDays;

        $files = array_filter($getLabels, function($a){
            return filemtime($a) < $this->limit;
        });

        $files = array_slice($files, 0, $deleteNumberOfFiles);

        foreach ($files as $label) {
            $fileTime = filemtime($label);
            if ($fileTime === false) {
                $errors['filemtime'] = $this->module->l(
                    'Failed to retrieve file time for some labels. Check file permissions.', 'DeleteLabels'
                );
                continue;
            }

            if (unlink($label) === false) {
                $errors['unlink'] = $this->module->l(
                    'Failed to remove some labels. Check file permissions.', 'DeleteLabels'
                );
                continue;
            }
        }

        return $errors;
    }
}
