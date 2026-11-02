<?php
namespace Mmi\TestKit\Service;

use Mmi\TestKit\Manifest;

class RunService
{
	/** @var array */
	private $config;

	/** @var JobStore */
	private $jobs;

	public function __construct($config)
	{
		$this->config = $config;
		$this->jobs = new JobStore($config['job_dir']);
	}

	/**
	 * @return array<string, string> nom => chemin
	 */
	public function manifests()
	{
		$out = array();
		foreach (glob(rtrim($this->config['manifest_dir'], '/').'/*.yml') as $file) {
			$out[basename($file)] = $file;
		}
		ksort($out);
		return $out;
	}

	/**
	 * @param string $manifestName
	 * @return Manifest
	 */
	public function manifest($manifestName)
	{
		$file = $this->manifestFile($manifestName);
		return Manifest::load($file);
	}

	/**
	 * @param string   $manifestName
	 * @param string[] $subset
	 * @return \Mmi\TestKit\Model\RunJob
	 */
	public function launch($manifestName, $subset = array())
	{
		$manifestFile = $this->manifestFile($manifestName);
		$manifest = Manifest::load($manifestFile);
		$envName = $manifest->envName();
		if (!isset($this->config['envs'][$envName])) {
			throw new \RuntimeException('Environnement non configuré : '.$envName);
		}
		$env = $this->config['envs'][$envName];
		$root = realpath(isset($env['root']) ? $env['root'] : '');
		if (!$root || !is_file($root.'/master.inc.php')) {
			throw new \RuntimeException('Répertoire htdocs invalide pour '.$envName);
		}

		$phpVersion = isset($manifest->data['env']['php']) ? (string) $manifest->data['env']['php'] : (isset($env['php']) ? (string) $env['php'] : '');
		$phpBin = 'php'.preg_replace('/[^0-9.]/', '', $phpVersion);
		$available = trim((string) @shell_exec('command -v '.escapeshellarg($phpBin).' 2>/dev/null'));
		if ($available === '') {
			throw new \RuntimeException('Binaire PHP introuvable : '.$phpBin);
		}

		$allowed = array();
		foreach ($manifest->suites() as $suite) {
			if (isset($suite['id'])) {
				$allowed[] = (string) $suite['id'];
			}
		}
		$subset = array_values(array_intersect(array_map('strval', $subset), $allowed));

		$id = date('Ymd-His').'-'.substr(uniqid('', true), -6);
		$log = rtrim($this->config['job_dir'], '/').'/'.$id.'.log';
		if (!is_dir(dirname($log))) {
			mkdir(dirname($log), 0777, true);
		}

		$cmd = $phpBin.' '.escapeshellarg($this->config['base_path'].'/bin/mmi-test')
			.' --manifest='.escapeshellarg($manifestFile)
			.' --dolibarr-root='.escapeshellarg($root)
			.' --report-dir='.escapeshellarg($this->config['report_dir']);
		if (!empty($subset)) {
			$cmd .= ' --subset='.escapeshellarg(implode(',', $subset));
		}

		$shell = $cmd.'; printf "\\n__EXIT=%s\\n" "$?"';
		$background = 'nohup sh -c '.escapeshellarg($shell).' >> '.escapeshellarg($log).' 2>&1 & echo $!';
		$out = array();
		$rc = 0;
		exec($background, $out, $rc);
		if ($rc !== 0) {
			throw new \RuntimeException('Impossible de lancer le run (exec code '.$rc.')');
		}
		$pid = isset($out[0]) ? (int) $out[0] : 0;

		return $this->jobs->create(array(
			'id' => $id,
			'manifest' => basename($manifestFile),
			'env' => $envName,
			'root' => $root,
			'php' => $phpVersion,
			'subset' => $subset,
			'log' => $log,
			'pid' => $pid,
			'started_at' => date('c'),
		));
	}

	public function jobs($limit = 20)
	{
		return $this->jobs->all($limit);
	}

	public function job($id)
	{
		return $this->jobs->get($id);
	}

	public function log($job)
	{
		return $this->jobs->log($job);
	}

	private function manifestFile($manifestName)
	{
		$name = basename((string) $manifestName);
		if (!preg_match('/^[A-Za-z0-9_.-]+\.yml$/', $name)) {
			throw new \RuntimeException('Manifeste invalide');
		}
		$file = realpath(rtrim($this->config['manifest_dir'], '/').'/'.$name);
		$base = realpath($this->config['manifest_dir']);
		if (!$file || !$base || strpos($file, $base.'/') !== 0 || !is_file($file)) {
			throw new \RuntimeException('Manifeste introuvable');
		}
		return $file;
	}
}
