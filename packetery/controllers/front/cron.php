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
		$shiftDays = Configuration::get('PACKETERY_CRON_DELETE_LABELS_SHIFT');
		if($shiftDays === false) {
			$this->writeMessage('Configuration can not be loaded.');
			return;
		}
		$shift = 60 * 60 * 24 * $shiftDays;
		$limit = time() - $shift;

		foreach($files as $label) {
			$labelName = basename($label);
			$fileTime = filemtime($label);
			if($fileTime === false) {
				$this->writeMessage('Failed to retrieve file time for label "' . $labelName . '".');
				continue;
			}

			if($fileTime < $limit) {
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

		if($this->validateToken($this->getToken()) === false) {
			$this->writeMessage('Invalid packetery cron token for task.');
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
		PrestaShopLogger::addLog('[packetery:cron]: ' . $message, 3, null, null, null, true);
	}

	/**
	 * @return string|null task passed by user
	 */
	private function getTask() {
		return \Packetery\Tools\Tools::getValue('task', null);
	}

	private function validateToken($token) {
		$storedToken = Configuration::get('PACKETERY_CRON_TOKEN');
		if($storedToken === false) {
			return false;
		}
		if($token === null) {
			return false;
		}

		return $storedToken === $token;
	}

	/**
	 * @return string|null
	 */
	private function getToken() {
		return \Packetery\Tools\Tools::getValue('token', null);
	}
}
