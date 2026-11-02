<?php
namespace Mmi\TestKit\Controller;

use Mmi\TestKit\Service\ReportService;
use Mmi\TestKit\Service\RunService;

class DashboardController extends BaseController
{
	public function index($request, $params)
	{
		$reports = new ReportService($this->config);
		$runs = new RunService($this->config);
		$envs = array();
		foreach ($reports->envs() as $name => $env) {
			$env['latest'] = $reports->latest($name);
			$env['runs'] = count($reports->runs($name, 0));
			$envs[$name] = $env;
		}
		return $this->render('dashboard', array(
			'title' => 'Tableau de bord',
			'envs' => $envs,
			'jobs' => $runs->jobs(10),
		));
	}
}
