<?php
namespace Mmi\TestKit\Controller;

use Mmi\TestKit\Service\ReportService;

class AnomalyController extends BaseController
{
	public function index($request, $params)
	{
		$service = new ReportService($this->config);
		return $this->render('anomalies', array(
			'title' => 'Anomalies',
			'content' => $service->anomalies(),
		));
	}
}
