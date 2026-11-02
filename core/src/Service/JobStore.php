<?php
namespace Mmi\TestKit\Service;

use Mmi\TestKit\Model\RunJob;

class JobStore
{
	/** @var string */
	private $dir;

	public function __construct($dir)
	{
		$this->dir = rtrim($dir, '/');
		if (!is_dir($this->dir)) {
			mkdir($this->dir, 0777, true);
		}
	}

	public function create($data)
	{
		if (empty($data['id'])) {
			$data['id'] = date('Ymd-His').'-'.substr(uniqid('', true), -6);
		}
		$job = RunJob::fromArray($data);
		$job->status = $this->status($job);
		file_put_contents($this->path($job->id), json_encode($job->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
		return $job;
	}

	/**
	 * @param int $limit
	 * @return RunJob[]
	 */
	public function all($limit = 20)
	{
		$files = glob($this->dir.'/*.json');
		if (empty($files)) {
			return array();
		}
		usort($files, function ($a, $b) {
			return filemtime($b) <=> filemtime($a);
		});
		$jobs = array();
		foreach (array_slice($files, 0, $limit) as $file) {
			$data = json_decode((string) file_get_contents($file), true);
			if (is_array($data)) {
				$job = RunJob::fromArray($data);
				$job->status = $this->status($job);
				$jobs[] = $job;
			}
		}
		return $jobs;
	}

	public function get($id)
	{
		if (!$this->validId($id)) {
			return null;
		}
		$file = $this->path($id);
		if (!is_file($file)) {
			return null;
		}
		$data = json_decode((string) file_get_contents($file), true);
		if (!is_array($data)) {
			return null;
		}
		$job = RunJob::fromArray($data);
		$job->status = $this->status($job);
		return $job;
	}

	public function log(RunJob $job, $maxBytes = 20000)
	{
		if (!is_file($job->log)) {
			return '';
		}
		$content = (string) file_get_contents($job->log);
		if (strlen($content) > $maxBytes) {
			$content = '…'.substr($content, -$maxBytes);
		}
		return $content;
	}

	public function status(RunJob $job)
	{
		if (!empty($job->log) && is_file($job->log)) {
			$content = (string) file_get_contents($job->log);
			if (preg_match('/__EXIT=(\d+)/', $content, $m)) {
				return ((int) $m[1] === 0) ? 'done' : 'failed';
			}
		}
		return 'running';
	}

	private function path($id)
	{
		return $this->dir.'/'.$id.'.json';
	}

	private function validId($id)
	{
		return is_string($id) && preg_match('/^[A-Za-z0-9_.-]+$/', $id) === 1;
	}
}
