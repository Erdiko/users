<?php
/**
 * Login
 *
 * @package     erdiko/users/controllers
 * @copyright   Copyright (c) 2016, Arroyo Labs, http://www.arroyolabs.com
 * @author      Leo Daidone, leo@arroyolabs.com
 */

namespace erdiko\users\controllers;

use erdiko\authenticate\services\BasicAuthenticator;
use erdiko\users\models\User;

class Login extends \erdiko\core\Controller
{

	protected $selfViews;

	/**
	 * Before action hook
	 * setting package views path
	 */
	public function _before()
	{
		parent::_before();
		$this->selfViews = dirname(__FILE__)."/..";
	}

	public function getLogin()
	{
		$this->setTitle('Welcome to Erdiko');
		$view = $this->getView('login',null,$this->selfViews);
		$this->appendContent($view);
	}

	public function postLogin()
	{
		$authenticator = new BasicAuthenticator(new User);
		$data = (object)$_REQUEST;

		if ($authenticator->login(array('username'=>$data->email, 'password'=>$data->password),'erdiko_user')) {
			\erdiko\core\helpers\FlashMessages::set("Welcome, ".$data->email, "success");
			$this->redirect("/dashboard");
		} else {
			\erdiko\core\helpers\FlashMessages::set("Username/password are wrong.\n Please try again.", "danger");
			$this->getLogin();
		}
	}

	public function getLogout()
	{
		$authenticator = new BasicAuthenticator(new User());
		$authenticator->logout();
		\erdiko\core\helpers\FlashMessages::set("Good bye, ".$authenticator->currentUser()->getUsername(), "success");
		$this->getLogin();
	}
}