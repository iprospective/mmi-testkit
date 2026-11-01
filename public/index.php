<?php
require_once dirname(__DIR__).'/src/Autoload.php';

$config = require dirname(__DIR__).'/config/app.php';

\Mmi\TestKit\Http\Session::start();

$request = new \Mmi\TestKit\Http\Request($config['base_url']);

$router = new \Mmi\TestKit\Http\Router();
$router->get('/', array(\Mmi\TestKit\Controller\DashboardController::class, 'index'));
$router->get('/reports', array(\Mmi\TestKit\Controller\ReportController::class, 'index'));
$router->get('/reports/{env}', array(\Mmi\TestKit\Controller\ReportController::class, 'env'));
$router->get('/reports/{env}/{run}', array(\Mmi\TestKit\Controller\ReportController::class, 'show'));
$router->get('/reports/{env}/{run}/json', array(\Mmi\TestKit\Controller\ReportController::class, 'raw'));
$router->get('/anomalies', array(\Mmi\TestKit\Controller\AnomalyController::class, 'index'));
$router->get('/run', array(\Mmi\TestKit\Controller\RunController::class, 'index'));
$router->post('/run', array(\Mmi\TestKit\Controller\RunController::class, 'launch'));
$router->get('/jobs/{id}/log', array(\Mmi\TestKit\Controller\RunController::class, 'log'));
$router->get('/login', array(\Mmi\TestKit\Controller\LoginController::class, 'index'));
$router->post('/login', array(\Mmi\TestKit\Controller\LoginController::class, 'login'));
$router->get('/logout', array(\Mmi\TestKit\Controller\LoginController::class, 'logout'));

$publicPaths = array('/login');
if (!\Mmi\TestKit\Http\Auth::check() && !in_array($request->path, $publicPaths, true)) {
	\Mmi\TestKit\Http\Response::redirect('/login')->send();
	exit;
}

try {
	$response = $router->dispatch($request, $config);
} catch (\Throwable $e) {
	$response = new \Mmi\TestKit\Http\Response(
		'<pre>'.htmlspecialchars($e->getMessage()."\n".$e->getFile().':'.$e->getLine(), ENT_QUOTES, 'UTF-8').'</pre>',
		500,
		array('Content-Type' => 'text/html; charset=UTF-8')
	);
}
$response->send();
