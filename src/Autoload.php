<?php
/* MMI Dolibarr TestKit — autoload minimal, sans dépendance externe. */

spl_autoload_register(function ($class) {
	$prefix = 'Mmi\\TestKit\\';
	if (strpos($class, $prefix) !== 0) {
		return;
	}
	$relative = substr($class, strlen($prefix));
	if (strpos($relative, 'Workflows\\') === 0) {
		$file = dirname(__DIR__).'/workflows/'.str_replace('\\', '/', substr($relative, strlen('Workflows\\'))).'.php';
	} else {
		$file = __DIR__.'/'.str_replace('\\', '/', $relative).'.php';
	}
	if (is_file($file)) {
		require_once $file;
	}
});
