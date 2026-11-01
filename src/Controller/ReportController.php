<?php
namespace Mmi\TestKit\Controller;

use Mmi\TestKit\Http\Response;
use Mmi\TestKit\Service\ReportService;

class ReportController extends BaseController
{
	public function index($request, $params)
	{
		$service = new ReportService($this->config);
		$envs = array();
		foreach ($service->envs() as $name => $env) {
			$env['runs'] = $service->runs($name, 50);
			$envs[$name] = $env;
		}
		return $this->render('reports', array('title' => 'Rapports', 'envs' => $envs));
	}

	public function env($request, $params)
	{
		$name = isset($params['env']) ? $params['env'] : '';
		$service = new ReportService($this->config);
		if (!$service->valid($name)) {
			return new Response('Environnement invalide', 400);
		}
		$envs = $service->envs();
		if (!isset($envs[$name])) {
			return new Response('Environnement inconnu', 404);
		}
		return $this->render('reports', array(
			'title' => 'Rapports — '.$envs[$name]['label'],
			'envs' => array($name => array_merge($envs[$name], array('runs' => $service->runs($name, 100)))),
		));
	}

	public function show($request, $params)
	{
		$service = new ReportService($this->config);
		$report = $service->report($params['env'], $params['run']);
		if (!$report) {
			return new Response('Rapport introuvable', 404);
		}
		$envs = $service->envs();
		return $this->render('report', array(
			'title' => 'Rapport — '.$params['env'],
			'report' => $report,
			'env' => $params['env'],
			'envLabel' => isset($envs[$params['env']]['label']) ? $envs[$params['env']]['label'] : $params['env'],
			'diagnose' => $service->diagnose($params['env']),
		));
	}

	public function raw($request, $params)
	{
		$service = new ReportService($this->config);
		$report = $service->report($params['env'], $params['run']);
		if (!$report) {
			return new Response('Rapport introuvable', 404);
		}
		return Response::json($report);
	}
}
