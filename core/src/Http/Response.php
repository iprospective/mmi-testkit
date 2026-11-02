<?php
namespace Mmi\TestKit\Http;

class Response
{
	/** @var string */
	public $body;
	/** @var int */
	public $status;
	/** @var array */
	public $headers;

	public function __construct($body = '', $status = 200, $headers = array())
	{
		$this->body = $body;
		$this->status = $status;
		$this->headers = $headers;
	}

	public static function redirect($url, $status = 302)
	{
		return new self('', $status, array('Location' => $url));
	}

	public static function json($data, $status = 200)
	{
		return new self(
			json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
			$status,
			array('Content-Type' => 'application/json; charset=UTF-8')
		);
	}

	public function send()
	{
		http_response_code($this->status);
		foreach ($this->headers as $name => $value) {
			header($name.': '.$value);
		}
		echo $this->body;
	}
}
