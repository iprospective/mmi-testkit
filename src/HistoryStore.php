<?php
namespace Mmi\TestKit;

class HistoryStore
{
	/** @var string */
	private $dir;

	public function __construct($dir)
	{
		$this->dir = rtrim($dir, '/');
	}

	/**
	 * Enregistre un run : JSON daté, latest.json et ligne JSONL.
	 *
	 * @param array $result
	 * @return string Chemin du fichier daté
	 */
	public function save($result)
	{
		$env = $this->envSlug($result['env']);
		$envDir = $this->dir.'/'.$env;
		if (!is_dir($envDir)) {
			mkdir($envDir, 0777, true);
		}

		$json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
		$file = $envDir.'/'.$result['run_id'].'.json';
		file_put_contents($file, $json);
		file_put_contents($envDir.'/latest.json', $json);
		file_put_contents($envDir.'/history.jsonl', json_encode($result, JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);

		return $file;
	}

	/**
	 * @param string $env
	 * @param int    $limit
	 * @return array<int, array>
	 */
	public function runs($env, $limit = 0)
	{
		$file = $this->dir.'/'.$this->envSlug($env).'/history.jsonl';
		if (!is_file($file)) {
			return array();
		}
		$runs = array();
		foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
			$decoded = json_decode($line, true);
			if (is_array($decoded)) {
				$runs[] = $decoded;
			}
		}
		if ($limit > 0) {
			$runs = array_slice($runs, -$limit);
		}
		return $runs;
	}

	/**
	 * @param string $env
	 * @return array|null
	 */
	public function latest($env)
	{
		$file = $this->dir.'/'.$this->envSlug($env).'/latest.json';
		if (!is_file($file)) {
			return null;
		}
		$decoded = json_decode((string) file_get_contents($file), true);
		return is_array($decoded) ? $decoded : null;
	}

	/**
	 * Diagnostic : premier échec, dernier succès, détail.
	 *
	 * @param string $env
	 * @return array
	 */
	public function diagnose($env)
	{
		$runs = $this->runs($env);
		if (empty($runs)) {
			return array('env' => $env, 'runs' => 0, 'failures' => array(), 'warnings' => array());
		}

		$latest = end($runs);
		$failures = array();
		$warnings = array();

		foreach ($latest['suites'] as $suite) {
			$suiteId = $suite['suite'];
			foreach ($suite['checks'] as $check) {
				if (empty($check['ok'])) {
					$failures[] = $this->trace($runs, $suiteId, $check, 'failure');
				}
			}
			foreach ($suite['warnings'] as $warning) {
				$warnings[] = array(
					'suite' => $suiteId,
					'label' => $warning['label'],
					'message' => $warning['message'],
				);
			}
		}

		return array(
			'env' => $env,
			'runs' => count($runs),
			'latest' => array(
				'run_id' => $latest['run_id'],
				'date' => $latest['date'],
				'commit' => $latest['code']['commit'],
				'branch' => $latest['code']['branch'],
				'checks' => $latest['summary']['checks'],
				'failed' => $latest['summary']['failed'],
				'warnings' => $latest['summary']['warnings'],
			),
			'failures' => $failures,
			'warnings' => $warnings,
		);
	}

	/**
	 * @param array  $runs
	 * @param string $suiteId
	 * @param array  $check
	 * @param string $kind
	 * @return array
	 */
	private function trace($runs, $suiteId, $check, $kind)
	{
		$firstFail = null;
		$lastPass = null;
		foreach ($runs as $run) {
			$current = $this->findCheck($run, $suiteId, $check['label']);
			if ($current === null) {
				continue;
			}
			if ($firstFail === null && empty($current['ok'])) {
				$firstFail = $run;
			}
			if (!empty($current['ok'])) {
				$lastPass = $run;
			}
		}

		return array(
			'suite' => $suiteId,
			'label' => $check['label'],
			'got' => $check['got'],
			'want' => $check['want'],
			'first_failure_commit' => $firstFail ? $firstFail['code']['commit'] : '',
			'first_failure_date' => $firstFail ? $firstFail['date'] : '',
			'last_pass_commit' => $lastPass ? $lastPass['code']['commit'] : '',
			'last_pass_date' => $lastPass ? $lastPass['date'] : '',
		);
	}

	/**
	 * @param array  $run
	 * @param string $suiteId
	 * @param string $label
	 * @return array|null
	 */
	private function findCheck($run, $suiteId, $label)
	{
		foreach ($run['suites'] as $suite) {
			if ($suite['suite'] !== $suiteId) {
				continue;
			}
			foreach ($suite['checks'] as $check) {
				if ($check['label'] === $label) {
					return $check;
				}
			}
		}
		return null;
	}

	/**
	 * @param string $env
	 * @return string
	 */
	private function envSlug($env)
	{
		$env = (string) $env;
		return preg_replace('/[^A-Za-z0-9_.-]+/', '_', $env);
	}
}
