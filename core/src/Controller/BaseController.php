<?php
namespace Mmi\TestKit\Controller;

use Mmi\TestKit\Http\Response;
use Mmi\TestKit\View\View;

class BaseController
{
	/** @var array */
	protected $config;

	/** @var View */
	protected $view;

	public function __construct($config)
	{
		$this->config = $config;
		$this->view = new View($config['views_dir'], $config);
	}

	protected function render($template, $data = array(), $layout = 'layout')
	{
		return new Response($this->view->render($template, $data, $layout));
	}

	protected function redirect($url, $status = 302)
	{
		return Response::redirect($url, $status);
	}

	protected function json($data, $status = 200)
	{
		return Response::json($data, $status);
	}
}
