<?php
namespace Mmi\TestKit\Model;

class RunJob
{
	public $id;
	public $manifest;
	public $env;
	public $root;
	public $php;
	public $subset;
	public $log;
	public $pid;
	public $started_at;
	public $status;

	public static function fromArray($data)
	{
		$job = new self();
		foreach (array('id', 'manifest', 'env', 'root', 'php', 'subset', 'log', 'pid', 'started_at', 'status') as $key) {
			if (array_key_exists($key, $data)) {
				$job->$key = $data[$key];
			}
		}
		return $job;
	}

	public function toArray()
	{
		return array(
			'id' => $this->id,
			'manifest' => $this->manifest,
			'env' => $this->env,
			'root' => $this->root,
			'php' => $this->php,
			'subset' => $this->subset,
			'log' => $this->log,
			'pid' => $this->pid,
			'started_at' => $this->started_at,
			'status' => $this->status,
		);
	}
}
