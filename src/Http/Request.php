<?php
namespace Mmi\TestKit\Http;

class Request
{
	/** @var string */
	public $method;
	/** @var string */
	public $path;
	/** @var array */
	public $query;
	/** @var array */
	public $post;
	/** @var array */
	public $server;

	public function __construct($baseUrl = '')
	{
		$this->method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
		$path = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH);
		$path = $path ?: '/';
		$baseUrl = rtrim($baseUrl, '/');
		if ($baseUrl !== '' && strpos($path, $baseUrl) === 0) {
			$path = substr($path, strlen($baseUrl));
			$path = $path === '' ? '/' : $path;
		}
		$this->path = '/'.trim($path, '/');
		if ($this->path === '/') {
			$this->path = '/';
		}
		$this->query = $_GET;
		$this->post = $_POST;
		$this->server = $_SERVER;
	}

	public function input($key, $default = null)
	{
		return array_key_exists($key, $this->post) ? $this->post[$key] : $default;
	}

	public function query($key, $default = null)
	{
		return array_key_exists($key, $this->query) ? $this->query[$key] : $default;
	}

	public function isPost()
	{
		return $this->method === 'POST';
	}
}
