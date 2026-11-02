<?php
namespace Mmi\TestKit\Controller;

use Mmi\TestKit\Http\Auth;
use Mmi\TestKit\Http\Csrf;
use Mmi\TestKit\Http\Session;

class LoginController extends BaseController
{
	public function index($request, $params)
	{
		if (Auth::check()) {
			return $this->redirect('/');
		}
		return $this->render('login', array(
			'title' => 'Connexion',
			'configured' => Auth::isConfigured(),
			'error' => Session::pullFlash('error'),
			'csrf' => Csrf::token(),
		), null);
	}

	public function login($request, $params)
	{
		if (!Csrf::validate($request->input('csrf'))) {
			Session::flash('error', 'Jeton CSRF invalide.');
			return $this->redirect('/login');
		}
		if (!Auth::isConfigured()) {
			Session::flash('error', 'MMI_TESTKIT_WEB_PASSWORD (ou _HASH) doit être défini.');
			return $this->redirect('/login');
		}
		if (!Auth::attempt($request->input('password', ''))) {
			Session::flash('error', 'Mot de passe invalide.');
			return $this->redirect('/login');
		}
		return $this->redirect('/');
	}

	public function logout($request, $params)
	{
		Auth::logout();
		return $this->redirect('/login');
	}
}
