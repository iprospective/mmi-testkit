<?php
// Routeur pour le serveur intégré PHP :
// php -S 127.0.0.1:8099 -t public public/router.php
if (PHP_SAPI === 'cli-server') {
	$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	$file = __DIR__.$path;
	if ($path !== '/' && is_file($file)) {
		return false;
	}
}
require __DIR__.'/index.php';
