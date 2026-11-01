<?php
namespace Mmi\TestKit\Http;

class Auth
{
	public static function check()
	{
		return Session::get('web_auth') === true;
	}

	public static function isConfigured()
	{
		return (string) getenv('MMI_TESTKIT_WEB_PASSWORD_HASH') !== ''
			|| (string) getenv('MMI_TESTKIT_WEB_PASSWORD') !== '';
	}

	public static function attempt($password)
	{
		$hash = (string) getenv('MMI_TESTKIT_WEB_PASSWORD_HASH');
		if ($hash !== '') {
			$ok = password_verify((string) $password, $hash);
		} else {
			$plain = (string) getenv('MMI_TESTKIT_WEB_PASSWORD');
			$ok = $plain !== '' && hash_equals($plain, (string) $password);
		}
		if (!$ok) {
			return false;
		}
		session_regenerate_id(true);
		Session::set('web_auth', true);
		return true;
	}

	public static function logout()
	{
		Session::remove('web_auth');
		session_regenerate_id(true);
	}
}
