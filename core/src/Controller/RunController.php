<?php
namespace Mmi\TestKit\Controller;

use Mmi\TestKit\Http\Csrf;
use Mmi\TestKit\Http\Response;
use Mmi\TestKit\Http\Session;
use Mmi\TestKit\Service\ReportService;
use Mmi\TestKit\Service\RunService;

class RunController extends BaseController
{
	public function index($request, $params)
	{
		$runs = new RunService($this->config);
		$manifests = $runs->manifests();
		$selected = $request->query('manifest', array_key_first($manifests));
		$suites = array();
		$error = '';
		if ($selected && isset($manifests[$selected])) {
			try {
				$manifest = $runs->manifest($selected);
				foreach ($manifest->suites() as $suite) {
					if (isset($suite['id'])) {
						$suites[] = $suite['id'];
					}
				}
			} catch (\Throwable $e) {
				$error = $e->getMessage();
			}
		} else {
			$selected = null;
		}

		$job = null;
		$log = '';
		$jobId = $request->query('job');
		if ($jobId) {
			$job = $runs->job($jobId);
			if ($job) {
				$log = $runs->log($job);
			}
		}

		$reports = new ReportService($this->config);
		return $this->render('run', array(
			'title' => 'Lancer des tests',
			'manifests' => $manifests,
			'selected' => $selected,
			'suites' => $suites,
			'envs' => $reports->envs(),
			'jobs' => $runs->jobs(15),
			'job' => $job,
			'log' => $log,
			'error' => $error,
			'csrf' => Csrf::token(),
		));
	}

	public function launch($request, $params)
	{
		if (!Csrf::validate($request->input('csrf'))) {
			Session::flash('error', 'Jeton CSRF invalide.');
			return $this->redirect('/run');
		}
		$manifest = (string) $request->input('manifest', '');
		$subset = $request->input('subset', array());
		if (!is_array($subset)) {
			$subset = array();
		}
		try {
			$runs = new RunService($this->config);
			$job = $runs->launch($manifest, $subset);
			Session::flash('success', 'Run lancé : '.$job->id);
			return $this->redirect('/run?job='.urlencode($job->id));
		} catch (\Throwable $e) {
			Session::flash('error', $e->getMessage());
			return $this->redirect('/run?manifest='.urlencode($manifest));
		}
	}

	public function log($request, $params)
	{
		$runs = new RunService($this->config);
		$job = $runs->job($params['id']);
		if (!$job) {
			return new Response('Job introuvable', 404);
		}
		$log = $runs->log($job);
		return new Response($log, 200, array('Content-Type' => 'text/plain; charset=UTF-8'));
	}
}
