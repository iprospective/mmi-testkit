<?php
$base = dirname(__DIR__);

return array(
	'base_path' => $base,
	'report_dir' => getenv('MMI_TESTKIT_REPORT_DIR') ?: $base.'/reports',
	'job_dir' => getenv('MMI_TESTKIT_JOB_DIR') ?: $base.'/var/jobs',
	'manifest_dir' => $base.'/manifests',
	'views_dir' => $base.'/views',
	'php_bin' => getenv('MMI_TESTKIT_PHP_BIN') ?: 'php',
	'envs' => is_file(__DIR__.'/envs.php') ? require __DIR__.'/envs.php' : array(),
	'base_url' => getenv('MMI_TESTKIT_BASE_URL') ?: '',
);
