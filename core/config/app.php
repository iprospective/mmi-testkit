<?php
$core = dirname(__DIR__);
$root = dirname($core);

return array(
	'base_path' => $core,
	'report_dir' => getenv('MMI_TESTKIT_REPORT_DIR') ?: $root.'/reports',
	'job_dir' => getenv('MMI_TESTKIT_JOB_DIR') ?: $root.'/var/jobs',
	'manifest_dir' => $root.'/plugins/dolibarr/manifests',
	'views_dir' => $core.'/views',
	'php_bin' => getenv('MMI_TESTKIT_PHP_BIN') ?: 'php',
	'envs' => is_file($root.'/plugins/dolibarr/config/envs.php') ? require $root.'/plugins/dolibarr/config/envs.php' : array(),
	'base_url' => getenv('MMI_TESTKIT_BASE_URL') ?: '',
);
