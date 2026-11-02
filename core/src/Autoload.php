<?php
/* MMI TestKit — autoload core + plugins, sans dependance externe. */

spl_autoload_register(function ($class) {
	$prefix = 'Mmi\\TestKit\\';
	if (strpos($class, $prefix) !== 0) {
		return;
	}
	$relative = substr($class, strlen($prefix));
	$root = dirname(__DIR__, 2);
	$plugin = $root.'/plugins/dolibarr';
	if ($relative === 'Bootstrap' || $relative === 'Environment') {
		$file = $plugin.'/src/'.$relative.'.php';
	} elseif (strpos($relative, 'Fixtures\\') === 0) {
		$file = $plugin.'/src/'.str_replace('\\', '/', $relative).'.php';
	} elseif (strpos($relative, 'Workflows\\') === 0) {
		$file = $plugin.'/workflows/'.str_replace('\\', '/', substr($relative, strlen('Workflows\\'))).'.php';
	} else {
		$file = __DIR__.'/'.str_replace('\\', '/', $relative).'.php';
	}
	if (is_file($file)) {
		require_once $file;
	}
});
