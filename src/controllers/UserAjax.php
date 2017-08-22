<?php
/**
 * UserAjax
 *
 * @package     erdiko/users/controllers
 * @copyright   Copyright (c) 2017, Arroyo Labs, http://www.arroyolabs.com
 * @author      Leo Daidone, leo@arroyolabs.com
 */

namespace erdiko\users\controllers;

use erdiko\authenticate\services\BasicAuthenticator;
use erdiko\authenticate\services\JWTAuthenticator;
use erdiko\authorize\helpers\AuthorizerHelper;
use erdiko\authorize\UserInterface;
use erdiko\authorize\Authorizer;
use erdiko\users\models\User;
use erdiko\users\models\user\event\Log;
use erdiko\users\validators\LogsValidator;
use erdiko\users\validators\UserValidator;

class UserAjax extends \erdiko\core\AjaxController
{
    private $id = null;

	/**
	 * It always returns true because login is not a must in this section, but we still want to create authenticator
	 * instance if there's a logged in user to restrict some actions.
	 *
	 * @return bool
	 */
	protected function checkAuth()
	{
		try {

			// get the JWT from the headers
			list($jwt) = sscanf($_SERVER["HTTP_AUTHORIZATION"], 'Bearer %s');

			// init the jwt auth class
			$authenticator = new JWTAuthenticator(new User());

			// get the application secret key
			$config     = \Erdiko::getConfig();
			$secretKey  = $config["site"]["secret_key"];

			// collect login params
			$params = array(
				'secret_key'    =>  $secretKey,
				'jwt'           =>  $jwt
			);

			$user = $authenticator->verify($params, 'jwt_auth');

			//TODO check the user's permissions via Resource & Authorization

			// no exceptions? welp, this is a valid request
			return true;
		} catch (\Exception $e) {
			return true;
		}
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
	 * Return TRUE to allow CORS requests
	 *
	 * NOTE - this method facilitates local env testing with the node server.
	 *  we *could* get rid of this but I do not think its a bad idea to leave it.
	 *
	 * @param null $var
	 *
	 * @return boolean
	 */
	public function options($var = null)
	{
		header('Access-Control-Allow-Credentials: true');
		header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
		header("Access-Control-Allow-Headers: Accept, Accept-CH, Accept-Charset, Accept-Datetime, Accept-Encoding, Accept-Ext, Accept-Features, Accept-Language, Accept-Params, Accept-Ranges, Access-Control-Allow-Credentials, Access-Control-Allow-Headers, Access-Control-Allow-Methods, Access-Control-Allow-Origin, Access-Control-Expose-Headers, Access-Control-Max-Age, Access-Control-Request-Headers, Access-Control-Request-Method, Age, Allow, Alternates, Authentication-Info, Authorization, C-Ext, C-Man, C-Opt, C-PEP, C-PEP-Info, CONNECT, Cache-Control, Compliance, Connection, Content-Base, Content-Disposition, Content-Encoding, Content-ID, Content-Language, Content-Length, Content-Location, Content-MD5, Content-Range, Content-Script-Type, Content-Security-Policy, Content-Style-Type, Content-Transfer-Encoding, Content-Type, Content-Version, Cookie, Cost, DAV, DELETE, DNT, DPR, Date, Default-Style, Delta-Base, Depth, Derived-From, Destination, Differential-ID, Digest, ETag, Expect, Expires, Ext, From, GET, GetProfile, HEAD, HTTP-date, Host, IM, If, If-Match, If-Modified-Since, If-None-Match, If-Range, If-Unmodified-Since, Keep-Alive, Label, Last-Event-ID, Last-Modified, Link, Location, Lock-Token, MIME-Version, Man, Max-Forwards, Media-Range, Message-ID, Meter, Negotiate, Non-Compliance, OPTION, OPTIONS, OWS, Opt, Optional, Ordering-Type, Origin, Overwrite, P3P, PEP, PICS-Label, POST, PUT, Pep-Info, Permanent, Position, Pragma, ProfileObject, Protocol, Protocol-Query, Protocol-Request, Proxy-Authenticate, Proxy-Authentication-Info, Proxy-Authorization, Proxy-Features, Proxy-Instruction, Public, RWS, Range, Referer, Refresh, Resolution-Hint, Resolver-Location, Retry-After, Safe, Sec-Websocket-Extensions, Sec-Websocket-Key, Sec-Websocket-Origin, Sec-Websocket-Protocol, Sec-Websocket-Version, Security-Scheme, Server, Set-Cookie, Set-Cookie2, SetProfile, SoapAction, Status, Status-URI, Strict-Transport-Security, SubOK, Subst, Surrogate-Capability, Surrogate-Control, TCN, TE, TRACE, Timeout, Title, Trailer, Transfer-Encoding, UA-Color, UA-Media, UA-Pixels, UA-Resolution, UA-Windowpixels, URI, Upgrade, User-Agent, Variant-Vary, Vary, Version, Via, Viewport-Width, WWW-Authenticate, Want-Digest, Warning, Width, X-Content-Duration, X-Content-Security-Policy, X-Content-Type-Options, X-CustomHeader, X-DNSPrefetch-Control, X-Forwarded-For, X-Forwarded-Port, X-Forwarded-Proto, X-Frame-Options, X-Modified, X-OTHER, X-PING, X-PINGOTHER, X-Powered-By, X-Requested-With");

		exit;
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

        $this->setStatusCode($response["error_code"]);
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

        $this->setStatusCode($response["error_code"]);
		$this->setContent($response);
	}


	/**
	 * User CRUD actions
	 */
	public function postRegister()
	{
		$response = array(
			"method" => "register",
			"success" => false,
			"user" => "",
			"error_code" => 0,
			"error_message" => ""
		);

		try {
			AuthorizerHelper::can(UserValidator::USER_CAN_CREATE);

            $data = json_decode(file_get_contents("php://input"));
            if (empty($data)) {
                $data = (object) $_POST;
            }
            // Check required fields
            $requiredParams = array('email','password', 'role', 'name');
            $params = (array) $data;
            foreach ($requiredParams as $param){
                if (empty($params[$param])) {
                    throw new \Exception(ucfirst($param) .' is required.');
                }
            }

			$userModel = new User();
            // Check Dupe email [ER-155]
            if(!empty($params->email)){
                if (false === $userModel->isEmailUnique($params->email)) {
                    throw new \Exception("The email you entered already exists.");
                }
            }

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
        if(array_key_exists("page", $_GET)) {
            $data->page = $_GET['page'];
        }

        $data->pagesize = 100;
        if(array_key_exists("pagesize", $_GET)){
            $data->pagesize = $_GET['pagesize'];
        }

        $data->sort = 'id';

        $validSort = array('id', 'name', 'email', 'created_at', 'updated_at');
        try {
            if (array_key_exists("sort", $_GET)) {
                $sort = strtolower($_GET["sort"]);
                if (!in_array($sort, $validSort)) {
                    throw new \Exception('The attribute used to sort is invalid.');
                }
                $data->sort = $sort;
            }

            $userModel = new User();
            $users = $userModel->getUsers($data->page, $data->pagesize, $data->sort);
            $output = array();
            foreach ($users->users as $user){
                $output['users'][] = array('id'       => $user->getId(),
                                  'email'    => $user->getEmail(),
                                  'role'     => $this->getRoleInfo($user),
                                  'name'     => $user->getName(),
                                  'last_login' => $user->getLastLogin(),
                                  'joined'      => $user->getCreatedAt(),
                                  //'gateway_customer_id'=> $user->getGatewayCustomerId()
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
	        AuthorizerHelper::can(UserValidator::USER_CAN_RETRIEVE);
            $params = (object) $_GET;
            // Check required fields
            if ((empty($this->id) || ($this->id < 1)) && (empty($params->id) || ($params->id < 1))) {
                throw new \Exception("ID is required.");
            } elseif (empty($params->id) && (!empty($this->id) || ($this->id >= 1))) {
                $params->id = $this->id;
            }

            $userModel = new User();
            $user = $userModel->getById($params->id);
            if( empty($user)) {
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
			AuthorizerHelper::can(UserValidator::USER_CAN_SAVE);
            $params = json_decode(file_get_contents("php://input"));
            if (empty($params)) {
                $params = (object) $_POST;
            }
            $userModel = new User();
            // Check Dupe email [ER-155]
            if(!empty($params->email)){
                if(false === $userModel->isEmailUnique($params->email)){
                    throw new \Exception("The email you entered already exists.");
                }
            }

			// Check required fields
			if ((empty($this->id) || ($this->id < 1)) && (empty($params->id) || ($params->id < 1))) {
				throw new \Exception("Id is required.");
			} elseif (empty($params->id) && (!empty($this->id) || ($this->id >= 1))) {
				$params->id = $this->id;
			}

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

	public function getCancel()
	{
		$response = array(
			"method" => "cancel",
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
            "user_id" => "",
            "error_code" => 0,
            "error_message" => ""
        );

        try {
	        AuthorizerHelper::can(LogsValidator::LOGS_CAN_CREATE);
            $data = json_decode(file_get_contents("php://input"));
            if (empty($data)) {
                $data = (object) $_POST;
            }
            // Check required fields
            $requiredParams = array('event');
            $params = (array) $data;
            foreach ($requiredParams as $param) {
                if (empty($params[$param])) {
                    throw new \Exception($param .' is required.');
                }
            }

            if (!array_key_exists("event_data", $params)) {
                $data->event_data = "";
            }

            if(!is_array($data->event_data)) {
                $data->event_data = array("data" => $data->event_data);
            }

            if (!array_key_exists("event_source", $params)) {
                $data->event_source = "front_end";
            }

            $data->event_type = $params['event'];
            $data->event_data = array_merge($data->event_data, array("source" => $data->event_source));

            $logModel = new Log();
            $user = new User();
            $basicAuth = new BasicAuthenticator($user);
            $currentUser = $basicAuth->currentUser();

            $logId = $logModel->create($currentUser->getUserId(), $data->event_type, $data->event_data);

            $entity = $logModel->findById($logId);

            $output = array('id'        => $entity->getId(),
                'event'     => $entity->getEventLog(),
                'event_data'=> $entity->getEventData(),
                'created_at'=> $entity->getCreatedAt()
            );

            $response['log'] = $output;
            $response['user_id'] = $currentUser->getUserId();
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

}
