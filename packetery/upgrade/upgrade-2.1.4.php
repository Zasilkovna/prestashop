<?php

if (!defined('_PS_VERSION_')) {
	exit;
}

function upgrade_module_2_1_4($object) {

	return (
		$object->unregisterHook('displayFooter') &&
		$object->unregisterHook('displayBeforeCarrier'));
}
