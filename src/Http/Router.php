<?php
namespace Mmi\TestKit\Http;

class Router
{
	/** @var array<int, array{method: string, pattern: string, handler: mixed}> */
	private $routes = array();

	public function get($pattern, $handler)
	{
		$this->routes[] = array('method' => 'GET', 'pattern' => $pattern, 'handler' => $handler);
	}

	public function post($pattern, $handler)
	{
		$this->routes[] = array('method' => 'POST', 'pattern' => $pattern, 'handler' => $handler);
	}

	/**
	 * @param Request $request
	 * @param array   $config
	 * @return Response
	 */
	public function dispatch(Request $request, $config)
	{
		foreach ($this->routes as $route) {
			if ($route['method'] !== $request->method) {
				continue;
			}
			$regex = '#^'.preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $route['pattern']).'$#';
			if (!preg_match($regex, $request->path, $matches)) {
				continue;
			}
			$params = array();
			foreach ($matches as $key => $value) {
				if (!is_int($key)) {
					$params[$key] = urldecode($value);
				}
			}
			$handler = $route['handler'];
			if (is_array($handler) && count($handler) === 2) {
				$class = $handler[0];
				$method = $handler[1];
				$controller = new $class($config);
				$result = $controller->$method($request, $params);
			} else {
				$result = call_user_func($handler, $request, $params, $config);
			}
			return $result instanceof Response ? $result : new Response((string) $result);
		}
		return new Response('Page introuvable', 404);
	}
}
