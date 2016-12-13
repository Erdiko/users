<?php


/**
 * Ajax
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
use erdiko\users\models\User;

class Ajax extends \erdiko\core\AjaxController
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
	public function postCreateUser()
	{
		$response = array(
			"method" => "createuser",
			"success" => false,
			"user" => "",
			"error_code" => 0,
			"error_message" => ""
		);

		try {
			$data = json_decode(file_get_contents("php://input"));
            // Check required fields
            $requiredParams = array('email','password', 'role', 'name');
            $params = (array) $data;
            foreach ($requiredParams as $param){
                if(empty($params[$param])){
                    throw new \Exception(ucfirst($param) .' is required.');
                }
            }

			$userModel = new User();
			$userId = $userModel->save($data);
            if(empty($userId)){
                throw  new \Exception('Could not create new user.');
            }
            $user = $userModel->getById($userId);
            $output = array('id'       => $user->getId(),
                            'email'    => $user->getEmail(),
                            'password' => $user->getPassword(),
                            'role'     => $user->getRole(),
                            'name'     => $user->getName(),
                            'last_login' => $user->getLastLogin(),
                            'gateway_customer_id'=> $user->getGatewayCustomerId()
            );

			$response['user'] = $output;
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
			"method" => "read",
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

	public function getUsers(){
        $response = array(
            "method" => "users",
            "success" => false,
            "users" => "",
            "error_code" => 0,
            "error_message" => ""
        );


        try {
            $userModel = new User();
            $users = $userModel->getUsers();
            $output = array();
            foreach ($users as $user){
                $output[] = array('id'       => $user->getId(),
                                  'email'    => $user->getEmail(),
                                  'password' => $user->getPassword(),
                                  'role'     => $user->getRole(),
                                  'name'     => $user->getName(),
                                  'last_login' => $user->getLastLogin(),
                                  'gateway_customer_id'=> $user->getGatewayCustomerId()
                );
            }
            $response['success'] = true;
            $response['users'] = $output;
            $this->setStatusCode(200);
        } catch (\Exception $e) {
            $response['error_message'] = $e->getMessage();
            $response['error_code'] = $e->getCode();
        }

        $this->setContent($response);

    }

    public function getUser(){
        $response = array(
            "method" => "user",
            "success" => false,
            "user" => "",
            "error_code" => 0,
            "error_message" => ""
        );

        try {
            $params = (object) $_REQUEST;
            // Check required fields
            if((empty($this->id) || ($this->id < 1)) && (empty($params->id) || ($params->id < 1))){
                throw new \Exception("ID is required.");
            } elseif (empty($params->id) && (!empty($this->id) || ($this->id >= 1))) {
                $params->id = $this->id;
            }

            $userModel = new User();
            $user = $userModel->getById($params->id);
            if(empty($user)){
                throw new \Exception('User not found.');
            }
            $output[] = array('id'       => $user->getId(),
                              'email'    => $user->getEmail(),
                              'password' => $user->getPassword(),
                              'role'     => $user->getRole(),
                              'name'     => $user->getName(),
                              'last_login' => $user->getLastLogin(),
                              'gateway_customer_id'=> $user->getGatewayCustomerId()
            );
            $response['success'] = true;
            $response['user'] = $output;
            $this->setStatusCode(200);
        } catch (\Exception $e) {
            $response['error_message'] = $e->getMessage();
            $response['error_code'] = $e->getCode();
        }

        $this->setContent($response);
    }

	public function postUpdateUser()
	{
		$response = array(
			"method" => "updateuser",
			"success" => false,
			"user" => "",
			"error_code" => 0,
			"error_message" => ""
		);

		try {
			$params = json_decode(file_get_contents("php://input"));

			// Check required fields
			if((empty($this->id) || ($this->id < 1)) && (empty($params->id) || ($params->id < 1))){
				throw new \Exception("Id is required.");
			} elseif (empty($params->id) && (!empty($this->id) || ($this->id >= 1))) {
				$params->id = $this->id;
			}

			$userModel = new User();
			$entity = $userModel->getById($params->id);
            if(empty($entity)){
                throw new \Exception('User not found.');
            }
            $result = $userModel->save($params);
            $user = $userModel->getById($result);
            $output = array('id'       => $user->getId(),
                            'email'    => $user->getEmail(),
                            'password' => $user->getPassword(),
                            'role'     => $user->getRole(),
                            'name'     => $user->getName(),
                            'last_login' => $user->getLastLogin(),
                            'gateway_customer_id'=> $user->getGatewayCustomerId()
            );
			$response['success'] = true;
			$response['user'] = $output;
			$this->setStatusCode(200);
		} catch (\Exception $e) {
			$response['error_message'] = $e->getMessage();
			$response['error_code'] = $e->getCode();
		}

		$this->setContent($response);
	}

	public function getDeleteUser()
	{
		$response = array(
			"method" => "deleteuser",
			"success" => false,
			"user" => "",
			"error_code" => 0,
			"error_message" => ""
		);

		try {

            $params = (object) $_REQUEST;
            // Check required fields
            if((empty($this->id) || ($this->id < 1)) && (empty($params->id) || ($params->id < 1))){
                throw new \Exception("Id is required.");
            } elseif (empty($params->id) && (!empty($this->id) || ($this->id >= 1))) {
                $params->id = $this->id;
            }

			$userModel = new User();
			$result = $userModel->deleteUser($params->id);

            if(false == $result){
                throw new \Exception('User could not be deleted.');
            }

			$response['user'] = array('id' => $params->id);
			$response['success'] = true;

			$this->setStatusCode(200);
		} catch (\Exception $e) {
			$response['error_message'] = $e->getMessage();
			$response['error_code'] = $e->getCode();
		}

		$this->setContent($response);
	}


    /**
     *
     * return roles with properties: id, users count, active, name.
     */
    public function getRoles(){
        $response = (object)array(
            'method'        => 'roles',
            'success'       => false,
            'status'        => 200,
            'error_code'    => 0,
            'error_message' => '',
            'roles'          => array()
        );

        try {
            $roleModel    = new \erdiko\users\models\Role();
            $roles = $roleModel->findByStatus(1);
            $responseRoles = array();
            foreach ($roles as $role){
                $responseRoles[] = array('id'     => $role->getId(),
                    'active' =>(bool) $role->getActive(),
                    'name'   => $role->getName(),
                    'users'  => $roleModel->getCountByRole($role->getId()),
                );
            }
            $response->success = true;
            $response->roles = $responseRoles;
            unset($response->error_code);
            unset($response->error_message);
        } catch (\Exception $e) {
            $response->success = false;
            $response->error_code = $e->getCode();
            $response->error_message = $e->getMessage();
        }
        $this->setContent($response);
    }


    /**
     *
     * return a role with their users.
     */
    public function getRole(){
        $response = (object)array(
            'method'        => 'role',
            'success'       => false,
            'status'        => 200,
            'role'          =>'',
            'error_code'    => 0,
            'error_message' => ''
        );

        $data = (object) $_REQUEST;
        try {
            if(empty($data->id)){
                throw new \Exception('Role Id is required.');
            }
            $roleModel    = new \erdiko\users\models\Role();
            if(empty($data->id)){
                throw new \Exception('Role Id is required.');
            }
            else{
                $id = $_REQUEST['id'];
            }
            $role = $roleModel->findById($id);
            if(empty($role)){
                throw new \Exception('Role not found.');
            }
            $users = $roleModel->getUsersForRole($id);
            $responseUsers = array();
            foreach ($users as $user){
                $responseUsers[] = array('id'   => $user->getId(),
                    'name' => $user->getName(),
                    'email'=> $user->getEmail()
                );
            }
            $responseRole = array('id' => $role->getId(),
                                  'name' => $role->getName(),
                                  'active' => $role->getActive(),
                                  'users'  => $responseUsers
            );
            $response->success = true;
            $response->role = $responseRole;
            unset($response->error_code);
            unset($response->error_message);
        } catch (\Exception $e) {
            $response->success = false;
            $response->error_code = $e->getCode();
            $response->error_message = $e->getMessage();
        }
        $this->setContent($response);
    }

    /**
     * Create a new role
     */
    public function postCreateRole(){
        $response = (object)array(
            'method'        => 'createrole',
            'success'       => false,
            'status'        => 200,
            'error_code'    => 0,
            'error_message' => ''
        );
        // decode json data
        $json = file_get_contents('php://input');
        $data = json_decode(trim($json));
        $requiredParams = array('name', 'active');
        try {
            $data = (array) $data;
            foreach ($requiredParams as $param){
                if(empty($data[$param])){
                    throw new \Exception(ucfirst($param) .' is required.');
                }
            }
            $data[] = array('active' => $data['active'],
                'name'   => strtolower($data['name'])
            );

            $roleModel    = new \erdiko\users\models\Role();
            $roleId = $roleModel->create($data);
            if($roleId === 0){
                throw new \Exception('Could not create Role.');
            }
            $role = $roleModel->findById($roleId);
            $responseRole = array('id' => $role->getId(),
                                  'active' => (boolean) $role->getActive(),
                                  'name'   => $role->getName()
            );
            $response->success = true;
            $response->role = $responseRole;
            unset($response->error_code);
            unset($response->error_message);
        } catch (\Exception $e) {
            $response->success = false;
            $response->error_code = $e->getCode();
            $response->error_message = $e->getMessage();
        }
        $this->setContent($response);
    }

    /**
     * update a given role
     */
    public function postUpdateRole(){
        $response = (object)array(
            'method'        => 'updaterole',
            'success'       => false,
            'status'        => 200,
            'error_code'    => 0,
            'error_message' => ''
        );
        // decode json data
        $json = file_get_contents('php://input');
        $data = json_decode(trim($json));
        $requiredParams = array('id', 'name', 'active');
        try {
            $data = (array) $data;
            foreach ($requiredParams as $param){
                if(empty($data[$param])){
                    throw new \Exception(ucfirst($param) .' is required.');
                }
            }

            $data[] = array('id' => $data['id'],
                            'active' => filter_var($data['active'], FILTER_VALIDATE_BOOLEAN),
                            'name'   => strtolower($data['name'])
            );

            $roleModel    = new \erdiko\users\models\Role();
            $roleId = $roleModel->save($data);
            $role = $roleModel->findById($roleId);
            $responseRole = array('id' => $role->getId(),
                'active' => (boolean) $role->getActive(),
                'name'   => $role->getName()
            );
            $response->success = true;
            $response->role = $responseRole;
            unset($response->error_code);
            unset($response->error_message);
        } catch (\Exception $e) {
            $response->success = false;
            $response->error_code = $e->getCode();
            $response->error_message = $e->getMessage();
        }
        $this->setContent($response);
    }

    /**
     * delete a given role
     */
    public function postDeleteRole(){
        $response = (object)array(
            'method'        => 'deleterole',
            'success'       => false,
            'status'        => 200,
            'error_code'    => 0,
            'error_message' => ''
        );
        // decode json data
        $json = file_get_contents('php://input');
        $data = json_decode(trim($json));
        $requiredParams = array('id');
        try {
            $data = (array) $data;
            foreach ($requiredParams as $param){
                if(empty($data[$param])){
                    throw new \Exception(ucfirst($param) .' is required.');
                }
            }

            $roleModel    = new \erdiko\users\models\Role();
            $roleId = $roleModel->delete($data['id']);
            $responseRoleId = array('id' => $roleId);
            $response->success = true;
            $response->role = $responseRoleId;
            unset($response->error_code);
            unset($response->error_message);
        } catch (\Exception $e) {
            $response->success = false;
            $response->error_code = $e->getCode();
            $response->error_message = $e->getMessage();
        }
        $this->setContent($response);
    }
}