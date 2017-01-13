<?php
/**
 * RoleAjax
 *
 * @category    Erdiko
 * @package     User
 * @copyright   Copyright (c) 2016, Arroyo Labs, http://www.arroyolabs.com
 * @author      Julian Diaz, julian@arroyolabs.com
 */

namespace erdiko\users\controllers;

use erdiko\authenticate\services\BasicAuthenticator;
use erdiko\authorize\UserInterface;
use erdiko\authorize\Authorizer;
use erdiko\users\models\Role;

class RoleAjax extends \erdiko\core\AjaxController
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
			$auth       = new BasicAuthenticator($userModel);
			$user       = $auth->currentUser();
			if ($user instanceof UserInterface) {
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
			if (is_array($routing)) {
				$var = array_shift($routing);
				$this->id = empty($routing)
					? 0
					: array_shift($routing);
			} else {
				$var = $routing;
			}

			if ($this->checkAuth("read",$var)) {
				// load action based off of naming conventions
                header('Content-Type: application/json');
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
			if (is_array($routing)) {
				$var = array_shift($routing);
				$this->id = empty($routing)
					? 0
					: array_shift($routing);
			} else {
				$var = $routing;
			}

			if ($this->checkAuth("write", $var)) {
				// load action based off of naming conventions
                header('Content-Type: application/json');
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
     *
     * return roles with properties: id, users count, active, name.
     */
    public function getRoles()
    {
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

        $data = (object) $_GET;
        try {
            if (empty($data->id)) {
                throw new \Exception('Role Id is required.');
            }
            $roleModel    = new \erdiko\users\models\Role();
            if (empty($data->id)) {
                throw new \Exception('Role Id is required.');
            } else{
                $id = $_GET['id'];
            }
            $role = $roleModel->findById($id);
            if (empty($role)) {
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
    public function postCreateRole()
    {
        $response = (object)array(
            'method'        => 'createrole',
            'success'       => false,
            'status'        => 200,
            'error_code'    => 0,
            'error_message' => ''
        );
        // decode json data
        $data = json_decode(file_get_contents("php://input"));
        if (empty($data)) {
            $data = (object) $_POST;
        }
        $requiredParams = array('name', 'active');
        try {
            $data = (array) $data;
            foreach ($requiredParams as $param){
                if (empty($data[$param])) {
                    throw new \Exception(ucfirst($param) .' is required.');
                }
            }
            $data[] = array('active' => $data['active'],
                'name'   => strtolower($data['name'])
            );

            $roleModel    = new \erdiko\users\models\Role();
            $roleId = $roleModel->create($data);
            if ($roleId === 0) {
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
    public function postUpdateRole()
    {
        $response = (object)array(
            'method'        => 'updaterole',
            'success'       => false,
            'status'        => 200,
            'error_code'    => 0,
            'error_message' => ''
        );
        // decode json data
        $data = json_decode(file_get_contents("php://input"));
        if (empty($data)) {
            $data = (object) $_POST;
        }
        $requiredParams = array('id', 'name', 'active');
        try {
            $data = (array) $data;
            foreach ($requiredParams as $param){
                if (empty($data[$param])) {
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
    public function postDeleteRole()
    {
        $response = (object)array(
            'method'        => 'deleterole',
            'success'       => false,
            'status'        => 200,
            'error_code'    => 0,
            'error_message' => ''
        );
        // decode json data
        $data = json_decode(file_get_contents("php://input"));
        if( empty($data)) {
            $data = (object) $_POST;
        }
        $requiredParams = array('id');
        try {
            $data = (array) $data;
            foreach ($requiredParams as $param){
                if (empty($data[$param])) {
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

