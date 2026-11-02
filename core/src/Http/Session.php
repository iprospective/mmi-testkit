<?php
namespace Mmi\TestKit\Http;

class Session
{
	public static function start()
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			return;
		}
		session_set_cookie_params(array(
			'lifetime' => 0,
			'path' => '/',
			'httponly' => true,
			'samesite' => 'Lax',
			'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
		));
		session_start();
	}

	public static function get($key, $default = null)
	{
		return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
	}

	public static function set($key, $value)
	{
		$_SESSION[$key] = $value;
	}

	public static function remove($key)
	{
		unset($_SESSION[$key]);
	}

	public static function flash($key, $value)
	{
		$_SESSION['_flash'][$key] = $value;
	}

	public static function pullFlash($key, $default = null)
	{
		if (!isset($_SESSION['_flash'][$key])) {
			return $default;
		}
		$value = $_SESSION['_flash'][$key];
		unset($_SESSION['_flash'][$key]);
		return $value;
	}
}
