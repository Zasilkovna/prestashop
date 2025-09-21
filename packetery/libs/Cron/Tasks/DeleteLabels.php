<?php

namespace Packetery\Cron\Tasks;

if (!defined('_PS_VERSION_')) {
    exit;
}

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
     * Delete files older than DEFAULT_NUMBER_OF_DAYS
     */
    const DEFAULT_NUMBER_OF_DAYS = 7;

    /**
     * Delete number of files in one batch
     */
    const DEFAULT_NUMBER_OF_FILES = 500;

    /** @var int set default limit to delete PDF labels equals 1 day */
    private $limit = 86400;

    /**
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

        $deleteNumberOfDays = (int)Tools::getValue('number_of_days', self::DEFAULT_NUMBER_OF_DAYS);
        $deleteNumberOfFiles = (int)Tools::getValue('number_of_files', self::DEFAULT_NUMBER_OF_FILES);

        if ($deleteNumberOfDays <= 0) {
            $deleteNumberOfDays = self::DEFAULT_NUMBER_OF_DAYS;
        }

        if ($deleteNumberOfFiles <= 0) {
            $deleteNumberOfFiles = self::DEFAULT_NUMBER_OF_FILES;
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
                $errors['filemtime'] = $this->module->getTranslator()->trans(
                    'Failed to retrieve file time for some labels. Check file permissions.',
                    [],
                    'Modules.Packetery.Deletelabels'
                );
                continue;
            }

            if (unlink($label) === false) {
                $errors['unlink'] = $this->module->getTranslator()->trans(
                    'Failed to remove some labels. Check file permissions.',
                    [],
                    'Modules.Packetery.Deletelabels'
                );
                continue;
            }
        }

        return $errors;
    }
}
