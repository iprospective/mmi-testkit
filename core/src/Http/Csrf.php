<?php
namespace Mmi\TestKit\Http;

class Csrf
{
	public static function token()
	{
		$token = Session::get('csrf_token');
		if (!is_string($token) || strlen($token) < 16) {
			$token = bin2hex(random_bytes(32));
			Session::set('csrf_token', $token);
		}
		return $token;
	}

	public static function validate($token)
	{
		$current = Session::get('csrf_token');
		return is_string($current) && is_string($token) && hash_equals($current, $token);
	}
}
