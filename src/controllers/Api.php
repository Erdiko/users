<?php


/**
 * Api
 *
 * @category    Erdiko
 * @package     User
 * @copyright   Copyright (c) 2016, Arroyo Labs, http://www.arroyolabs.com
 * @author      Leo Daidone, leo@arroyolabs.com
 */

namespace erdiko\users\controllers;

use erdiko\authenticate\BasicAuth;
use erdiko\authenticate\iErdikoUser;
use erdiko\authorize\Authorizer;
use erdiko\users\app\models\User;

class Api extends \erdiko\core\AjaxController
{
	private $id = null;
	/**
	 * @param $action
	 * @param $resource
	 *
	 * @return bool
	 */
	protected function checkAuth($action,$resource)
	{
		return true; // remove after testing
		try {
			$userModel  = new User();
			$auth       = new BasicAuth($userModel);
			$user       = $auth->current_user();
			if($user instanceof iErdikoUser){
				$authorizer = new Authorizer( $user );
				$result     = $authorizer->can( $action, $resource );
			} else {
				$result = false;
			}
		} catch (\Exception $e) {
			\error_log($e->getMessage());
			$result = false;
		}
		return $result;
	}

	/**
	 * @param null $var
	 *
	 * @return mixed
	 */
	public function get($var = null)
	{
		$this->id = 0;
		if (!empty($var)) {
			$routing = explode('/', $var);
			if(is_array($routing)) {
				$var = array_shift($routing);
				$this->id = empty($routing)
					? 0
					: array_shift($routing);
			} else {
				$var = $routing;
			}

			if ($this->checkAuth("read",$var)) {
				// load action based off of naming conventions
				return $this->_autoaction($var, 'get');
			} else {
				return $this->getForbbiden($var);
			}
		} else {
			return $this->getNoop();
		}
	}

	/**
	 * @param null $var
	 *
	 * @return mixed
	 */
	public function post($var = null)
	{
		$this->id = 0;
		if (!empty($var)) {
			$routing = explode('/', $var);
			if(is_array($routing)) {
				$var = array_shift($routing);
				$this->id = empty($routing)
					? 0
					: array_shift($routing);
			} else {
				$var = $routing;
			}

			if ($this->checkAuth("write", $var)) {
				// load action based off of naming conventions
				return $this->_autoaction($var, 'post');
			} else {
				return $this->getForbbiden($var);
			}
		} else {
			return $this->getNoop();
		}
	}

	/**
	 * Default response for not Authorized requests
	 */
	protected function getForbbiden($var)
	{
		$response = array(
			"action" => $var,
			"success" => false,
			"error_code" => 403,
			"error_message" => "Sorry, you don't have permission for this action"
		);

		$this->setContent($response);
	}

	/**
	 * Default response for no action requests
	 */
	protected function getNoop()
	{
		$response = array(
			"action" => "None",
			"success" => false,
			"error_code" => 404,
			"error_message" => 'Sorry, you need to specify a valid action'
		);

		$this->setContent($response);
	}


	/**
	 * User CRUD actions
	 */
	public function postCreate()
	{
		$response = array(
			"action" => "create",
			"success" => false,
			"body" => "",
			"error_code" => 0,
			"error_message" => ""
		);

		try {
			$params = json_decode(file_get_contents("php://input"));

			// Check required fields
			if(empty($params->email)){
				throw new \Exception("email is required.");
			} else if(empty($params->password)){
				throw new \Exception("password is required.");
			} else if(empty($params->role)){
				throw new \Exception("role is required.");
			}

			$user = new User();
			$uid = $user->save($params);

			$response['body'] = $uid;
			$response['success'] = true;
			$this->setStatusCode(200);
		} catch (\Exception $e) {
			$response['error_message'] = $e->getMessage();
			$response['error_code'] = $e->getCode();
		}

		$this->setContent($response);
	}

	public function getRead()
	{
		$response = array(
			"action" => "read",
			"success" => false,
			"body" => "",
			"error_code" => 0,
			"error_message" => ""
		);

		try {
			$user = new User();
			$result = array();
			if(empty($this->id) || ($this->id < 1)){
				$params = json_decode(file_get_contents("php://input"));
				if(empty($params)) {
					// List all users
					$users = $user->getUsers();
					foreach ( $users as $item ) {
						array_push( $result, $item->marshall( 'array' ) );
					}
				}else{
					$users = $user->getByParams($params);
					foreach ( $users as $item ) {
						array_push( $result, $item->marshall( 'array' ) );
					}
				}
			} else {
				// Get User by ID
				$users = $user->getById($this->id);
				$result = empty($users) ? null : $users->marshall('array');
			}

			$response['success'] = true;
			$response['body'] = $result;

			$this->setStatusCode(200);
		} catch (\Exception $e) {
			$response['error_message'] = $e->getTraceAsString();
			$response['error_code'] = $e->getCode();
		}

		$this->setContent($response);
	}

	public function postUpdate()
	{
		$response = array(
			"action" => "update",
			"success" => false,
			"body" => "",
			"error_code" => 0,
			"error_message" => ""
		);

		try {
			$params = json_decode(file_get_contents("php://input"));

			// Check required fields
			if((empty($this->id) || ($this->id < 1)) && (empty($params->id) || ($params->id < 1))){
				throw new \Exception("ID is required.");
			} elseif (empty($params->id) && (!empty($this->id) || ($this->id >= 1))) {
				$params->id = $this->id;
			}

			$user = new User();
			$uid = $user->save($params);

			$response['success'] = true;
			$response['body'] = $uid;
			$this->setStatusCode(200);
		} catch (\Exception $e) {
			$response['error_message'] = $e->getMessage();
			$response['error_code'] = $e->getCode();
		}

		$this->setContent($response);
	}

	public function getDelete()
	{
		$response = array(
			"action" => "delete",
			"success" => false,
			"body" => "",
			"error_code" => 0,
			"error_message" => ""
		);

		try {

			if(empty($this->id) || ($this->id < 1)){
				throw new \Exception("User ID is required.");
			}

			$user = new User();
			$user->deleteUser($this->id);

			$response['body'] = "User {$this->id} successfully deleted.";
			$response['success'] = true;

			$this->setStatusCode(200);
		} catch (\Exception $e) {
			$response['error_message'] = $e->getMessage();
			$response['error_code'] = $e->getCode();
		}

		$this->setContent($response);
	}
}