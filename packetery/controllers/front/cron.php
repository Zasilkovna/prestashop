<?php

declare(strict_types=1);


class PacketeryCronModuleFrontController extends ModuleFrontController {
	/** @var bool If set to true, will be redirected to authentication page */
	public $auth = false;

	/** @var bool */
	public $ajax;

	/**
	 * Deletes labels if they are older than specified number of days.
	 */
	private function runDeleteLabels() {
		$files = glob(__DIR__ . '/../../labels/*.pdf', GLOB_NOSORT);
		$shiftDays = Configuration::get('PACKETERY_CRON_DELETE_LABELS_SHIFT', null, null, null, 7);
		if($shiftDays === false) {
			$this->writeMessage('Configuration can not be loaded.');
			return;
		}

		foreach($files as $label) {
			$labelName = basename($label);
			$creationTime = filectime($label);
			if($creationTime === false) {
				$this->writeMessage('Failed to retrieve creation time for label "' . $labelName . '".');
				continue;
			}

			$shift = 60 * 60 * 24 * $shiftDays;
			$limit = time() - $shift;
			if($creationTime < $limit) {
				$result = unlink($label);
				if($result === false) {
					$this->writeMessage('Failed to remove label "' . $labelName . '". Check permissions.');
					continue;
				}

				$this->writeMessage('Label "' . $labelName . '" was deleted.');
			}
		}
	}

	/**
	 * @return void
	 */
	public function display() {
		$this->ajax = 1;

		if(php_sapi_name() !== 'cli') {
			$this->writeMessage('Forbidden call.');
			exit;
		}

		$task = $this->getTask();
		if(!$task) {
			$this->writeMessage('Specify task to run.');
			exit;
		}

		$method = 'run' . ucfirst($task);
		if(method_exists($this, $method) === false) {
			$this->writeMessage('Task was not found.');
			exit;
		}

		call_user_func_array([$this, $method], []);
		exit;
	}

	/**
	 * @param string $message Message to be printed.
	 */
	private function writeMessage($message) {
		$this->ajaxRender("$message\n");
	}

	/**
	 * @return string|null task passed by CLI user
	 */
	private function getTask() {
		global $argv;

		if(isset($argv[1])) {
			return $argv[1];
		}

		return null;
	}
}
