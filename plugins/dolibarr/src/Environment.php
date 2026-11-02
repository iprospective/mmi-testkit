<?php
namespace Mmi\TestKit;

class Environment
{
	/**
	 * Lit les métadonnées de l'environnement de test.
	 *
	 * Fichier attendu : <env>/.mmi-test-env.json, à côté de htdocs.
	 * Format : {branch, commit, built_at, db, php, source}.
	 *
	 * @param string $dolibarrRoot
	 * @return array
	 */
	public static function load($dolibarrRoot)
	{
		$root = rtrim($dolibarrRoot, '/');
		$envRoot = dirname($root);
		$data = array();

		$file = $envRoot.'/.mmi-test-env.json';
		if (is_file($file)) {
			$decoded = json_decode((string) file_get_contents($file), true);
			if (is_array($decoded)) {
				$data = $decoded;
			}
		}

		// Repli si un checkout git est présent.
		if (empty($data['commit']) && is_dir($envRoot.'/.git')) {
			$commit = trim((string) @shell_exec('git -C '.escapeshellarg($envRoot).' rev-parse HEAD 2>/dev/null'));
			$branch = trim((string) @shell_exec('git -C '.escapeshellarg($envRoot).' rev-parse --abbrev-ref HEAD 2>/dev/null'));
			$dirty = trim((string) @shell_exec('git -C '.escapeshellarg($envRoot).' status --porcelain 2>/dev/null'));
			$data['commit'] = $commit;
			$data['branch'] = $branch;
			$data['dirty'] = ($dirty !== '');
		}

		$data += array(
			'branch' => '',
			'commit' => '',
			'dirty' => false,
			'built_at' => '',
			'db' => '',
			'php' => PHP_VERSION,
			'source' => '',
		);

		return $data;
	}
}
