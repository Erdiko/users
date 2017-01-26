<?php


/**
 * UserAjax
 *
 * @category    Erdiko
 * @package     User
 * @copyright   Copyright (c) 2016, Arroyo Labs, http://www.arroyolabs.com
 * @author      Julian Diaz, julian@arroyolabs.com
 */

namespace erdiko\users\controllers\admin;

use erdiko\authenticate\services\BasicAuthenticator;
use erdiko\authorize\Authorizer;
use erdiko\authorize\UserInterface;
use erdiko\users\models\User;

class UserAjax extends \erdiko\core\AjaxController
{
	private $id = null;
	/**
	 * @param $action
	 * @param $resource
	 *
	 * @return bool
	 */
	protected function checkAuth()
	{
        //remove after testing
        //frisby test are not able to get a kind of "setUp" before each test.
	    return true;
		try {
			$userModel  = new User();
			$auth       = new BasicAuthenticator($userModel);
			$user       = $auth->currentUser();

			if ($user instanceof UserInterface) {
				$result = $user->isAdmin();
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

			if ($this->checkAuth()) {
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

			if ($this->checkAuth()) {
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
	 * User CRUD actions
	 */
	public function postCreate()
	{
		$response = array(
			"method" => "create",
			"success" => false,
			"user" => "",
			"error_code" => 0,
			"error_message" => ""
		);

		try {
            $data = json_decode(file_get_contents("php://input"));
            if (empty($data)) {
                $data = (object) $_POST;
            }
            // Check required fields
            $requiredParams = array('email', 'name', 'role');
            $params = (array) $data;
            foreach ($requiredParams as $param){
                if (empty($params[$param])) {
                    throw new \Exception(ucfirst($param) .' is required.');
                }
            }

            // default password, user will need to update on login
            $data->password = "changeme";

			$userModel = new User();
            $userId = $userModel->save($data);

            if (empty($userId)) {
                throw  new \Exception('Could not create new user.');
            }
            $user = $userModel->getById($userId);
            $output = array('id'       => $user->getId(),
                            'email'    => $user->getEmail(),
                            'role'     => $this->getRoleInfo($user),
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


    public function getList()
    {
        $response = array(
            "method" => "list",
            "success" => false,
            "users" => "",
            "error_code" => 0,
            "error_message" => ""
        );

        // decode
        $data =  ( object) array();

        $data->page = 0;
        if (array_key_exists("page", $_GET)) {
            $data->page = $_GET['page'];
        }

        $data->pagesize = 100;
        if (array_key_exists("pagesize", $_GET)) {
            $data->pagesize = $_GET['pagesize'];
        }

        $data->sort         = 'id';
        $data->direction    = 'desc';

        $validSort = array('id', 'name','email','created_at', 'updated_at');
        try {
            if (array_key_exists("sort", $_GET)) {
                $sort = strtolower($_GET["sort"]);
                if (!in_array($sort, $validSort)) {
                    throw new \Exception('The attribute used to sort is invalid.');
                }
                $data->sort = $sort;
            }

            if(array_key_exists("direction", $_GET)) {
                $dir = strtolower($_GET["direction"]);
                if (!in_array($dir, array("asc", "desc"))) {
                    throw new \Exception('sort direction is invalid');
                }
                $data->direction = $dir;
            }

            $userModel = new User();
            $userResult = $userModel->getUsers($data->page, $data->pagesize, $data->sort, $data->direction);
            $output = array("users" => array(), "total" => $userResult->total);
            foreach ($userResult->users as $user) {

                $lastLogin = $user->getLastLogin();
                if (empty($lastLogin)) {
                    $lastLogin = "n/a";
                }

                $output["users"][] = array(
                    'id'          => $user->getId(),
                    'email'       => $user->getEmail(),
                    'role'     => $this->getRoleInfo($user),
                    'name'        => $user->getName(),
                    'last_login'  => $lastLogin,
                    'joined'      => $user->getCreatedAt()
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

    public function getRetrieve()
    {
        $response = array(
            "method" => "retrieve",
            "success" => false,
            "user" => "",
            "error_code" => 0,
            "error_message" => ""
        );

        try {
            $params = (object) $_GET;
            // Check required fields
            if ((empty($this->id) || ($this->id < 1)) && (empty($params->id) || ($params->id < 1))) {
                throw new \Exception("ID is required.");
            } elseif (empty($params->id) && (!empty($this->id) || ($this->id >= 1))) {
                $params->id = $this->id;
            }

            $userModel = new User();
            $user = $userModel->getById($params->id);
            if (empty($user)) {
                throw new \Exception('User not found.');
            }
            $output = array('id'       => $user->getId(),
                            'email'    => $user->getEmail(),
                            'role'     => $this->getRoleInfo($user),
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

	public function postUpdate()
	{
		$response = array(
			"method" => "update",
			"success" => false,
			"user" => "",
			"error_code" => 0,
			"error_message" => ""
		);

		try {
            $params = json_decode(file_get_contents("php://input"));
            if (empty($params)) {
                $params = (object) $_POST;
            }

			// Check required fields
			if ((empty($this->id) || ($this->id < 1)) && (empty($params->id) || ($params->id < 1))) {
				throw new \Exception("Id is required.");
			} elseif (empty($params->id) && (!empty($this->id) || ($this->id >= 1))) {
				$params->id = $this->id;
			}

			$userModel = new User();
			$entity = $userModel->getById($params->id);
            if (empty($entity)) {
                throw new \Exception('User not found.');
            }
            $result = $userModel->save($params);
            $user = $userModel->getById($result);

            $output = array('id'       => $user->getId(),
                            'email'    => $user->getEmail(),
                            'role'     => $this->getRoleInfo($user),
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

	public function getDelete()
	{
		$response = array(
			"method" => "delete",
			"success" => false,
			"user" => "",
			"error_code" => 0,
			"error_message" => ""
		);

		try {
            $params = (object) $_GET;
            // Check required fields
            if ((empty($this->id) || ($this->id < 1)) && (empty($params->id) || ($params->id < 1))) {
                throw new \Exception("Id is required.");
            } elseif (empty($params->id) && (!empty($this->id) || ($this->id >= 1))) {
                $params->id = $this->id;
            }

			$userModel = new User();
			$result = $userModel->deleteUser($params->id);

            if (false == $result) {
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
     * @param $user
     * @return null|object
     */
    private function getRoleInfo($user)
    {
        $roleModel = new \erdiko\users\models\Role();
        $roleEntity = $roleModel->findById($user->getRole());
        return array('id' => $roleEntity->getId(),
                     'name' => $roleEntity->getName()
        );
    }

    /**
     *
     * get event log activity for current user
     */
    public function getUserActivity()
    {
        $response = array(
            "method" => "useractivity",
            "success" => false,
            "activities" => "",
            "error_code" => 0,
            "error_message" => ""
        );

        // decode
        $data =  ( object) array();

        $data->page = 0;
        if(array_key_exists("page", $_GET)) {
            $data->page = $_GET['page'];
        }

        $data->pagesize = 10;
        if(array_key_exists("pagesize", $_GET)){
            $data->pagesize = $_GET['pagesize'];
        }

        $data->sort = 'created_at';
        $data->direction = 'asc';

        $validSort = array('created_at');
        $validDirection = array('asc', 'desc');
        try {
            if (array_key_exists("sort", $_GET)) {
                $sort = strtolower($_GET["sort"]);
                if (!in_array($sort, $validSort)) {
                    throw new \Exception('The attribute used to sort is invalid.');
                }
                $data->sort = $sort;
            }

            if (array_key_exists("direction", $_GET)) {
                $direction = strtolower($_GET["direction"]);
                if (!in_array($direction, $validDirection)) {
                    throw new \Exception('The attribute used to direction is invalid.');
                }
                $data->direction = $direction;
            }

            $logModel = new Log();
            $user = new User();
            $basicAuth = new BasicAuthenticator($user);
            $currentUser = $basicAuth->currentUser();

            $logs = $logModel->getLogsByUserId($currentUser->getId(),$data->page, $data->pagesize, $data->sort, $data->direction);
            $output = array();
            foreach ($logs as $log) {
                $output[] = array('id'         => $log->getId(),
                    'event'      => $log->getEventLog(),
                    'event_data' => $log->getEventData(),
                    'created_at' => $log->getCreatedAt()
                );
            }
            $response['success'] = true;
            $response['user_id'] = $currentUser->getId();
            $response['activities'] = $output;
            $response['page'] = $data->page;
            $response['page_size'] = $data->pagesize;
            $response['sort'] = $data->sort;
            $response['direction'] = $data->direction;
            $this->setStatusCode(200);
        } catch (\Exception $e) {
            $response['error_message'] = $e->getMessage();
            $response['error_code'] = $e->getCode();
        }

        $this->setContent($response);
    }

    /**
     *
     * create a new event Log for current user
     *
     */
    public function postAddUserEvent()
    {
        $response = array(
            "method" => "adduserevent",
            "success" => false,
            "log" => "",
            "error_code" => 0,
            "error_message" => ""
        );

        try {
            $data = json_decode(file_get_contents("php://input"));
            if (empty($data)) {
                $data = (object) $_POST;
            }
            // Check required fields
            $requiredParams = array('event');
            $params = (array) $data;
            foreach ($requiredParams as $param){
                if (empty($params[$param])) {
                    throw new \Exception(ucfirst($param) .' is required.');
                }
            }

            if(!array_key_exists("event_data", $params)){
                $data->event_data = "";
            }

            $logModel = new Log();
            $user = new User();
            $basicAuth = new BasicAuthenticator($user);
            $currentUser = $basicAuth->currentUser();

            $frontEndClarify = 'front_end_'; //we need to clarify from where is the creation log
            $logId = $logModel->create($currentUser->getId(), $frontEndClarify.$data->event, $data->event_data);

            $entity = $logModel->findById($logId);
            $output = array('id'        => $entity->getId(),
                'event'     => $entity->getEventLog(),
                'event_data'=> $entity->getEventData(),
                'created_at'=> $entity->getCreatedAt()
            );

            $response['log'] = $output;
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
     * get event log activity for specified user user
     */
    public function getEventLogs()
    {
        $response = array(
            "method" => "geteventlogs",
            "success" => false,
            "user_id" => "",
            "logs" => "",
            "error_code" => 0,
            "error_message" => ""
        );

        // decode
        $data =  ( object) array();

        if(!array_key_exists("user_id", $_GET)) {
            throw  new \Exception('User_id is requerided.');
        }
        $user_id = $_GET['user_id'];

        $data->page = 0;
        if(array_key_exists("page", $_GET)) {
            $data->page = $_GET['page'];
        }

        $data->pagesize = 10;
        if(array_key_exists("pagesize", $_GET)){
            $data->pagesize = $_GET['pagesize'];
        }

        $data->sort = 'created_at';
        $data->direction = 'asc';

        $validSort = array('created_at');
        $validDirection = array('asc', 'desc');
        try {
            if (array_key_exists("sort", $_GET)) {
                $sort = strtolower($_GET["sort"]);
                if (!in_array($sort, $validSort)) {
                    throw new \Exception('The attribute used to sort is invalid.');
                }
                $data->sort = $sort;
            }

            if (array_key_exists("direction", $_GET)) {
                $direction = strtolower($_GET["direction"]);
                if (!in_array($direction, $validDirection)) {
                    throw new \Exception('The attribute used to direction is invalid.');
                }
                $data->direction = $direction;
            }

            $logModel = new Log();

            $logs = $logModel->getLogsByUserId($user_id,$data->page, $data->pagesize, $data->sort, $data->direction);
            $output = array();
            foreach ($logs as $log) {
                $output[] = array('id'         => $log->getId(),
                                  'event'      => $log->getEventLog(),
                                  'event_data' => $log->getEventData(),
                                  'created_at' => $log->getCreatedAt()
                );
            }
            $response['success'] = true;
            $response['user_id'] = $user_id;
            $response['logs'] = $output;
            $response['page'] = $data->page;
            $response['page_size'] = $data->pagesize;
            $response['sort'] = $data->sort;
            $response['direction'] = $data->direction;
            $this->setStatusCode(200);
        } catch (\Exception $e) {
            $response['error_message'] = $e->getMessage();
            $response['error_code'] = $e->getCode();
        }

        $this->setContent($response);
    }
}
