<?php
namespace Mmi\TestKit\Service;

use Mmi\TestKit\HistoryStore;

class ReportService
{
	/** @var array */
	private $config;

	/** @var HistoryStore */
	private $store;

	public function __construct($config)
	{
		$this->config = $config;
		$this->store = new HistoryStore($config['report_dir']);
	}

	/**
	 * @return array<string, array>
	 */
	public function envs()
	{
		$envs = array();
		foreach ($this->config['envs'] as $name => $info) {
			$envs[$name] = array_merge(array('name' => $name), $info);
		}
		$dir = $this->config['report_dir'];
		if (is_dir($dir)) {
			foreach (scandir($dir) as $entry) {
				if ($entry === '.' || $entry === '..' || !is_dir($dir.'/'.$entry)) {
					continue;
				}
				if (!isset($envs[$entry])) {
					$envs[$entry] = array('name' => $entry, 'label' => $entry, 'root' => '', 'php' => '');
				}
			}
		}
		ksort($envs);
		return $envs;
	}

	public function runs($env, $limit = 50)
	{
		return $this->store->runs($env, $limit);
	}

	public function latest($env)
	{
		return $this->store->latest($env);
	}

	public function diagnose($env)
	{
		return $this->store->diagnose($env);
	}

	public function report($env, $runId)
	{
		if (!$this->valid($env) || !$this->valid($runId)) {
			return null;
		}
		$file = rtrim($this->config['report_dir'], '/').'/'.$env.'/'.$runId.'.json';
		if (!is_file($file)) {
			return null;
		}
		$data = json_decode((string) file_get_contents($file), true);
		return is_array($data) ? $data : null;
	}

	public function anomalies()
	{
		$file = $this->config['base_path'].'/docs/anomalies.md';
		return is_file($file) ? (string) file_get_contents($file) : '';
	}

	public function valid($value)
	{
		return is_string($value) && preg_match('/^[A-Za-z0-9_.-]+$/', $value) === 1;
	}
}
