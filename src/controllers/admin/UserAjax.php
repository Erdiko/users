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

use erdiko\authenticate\services\JWTAuthenticator;
use erdiko\authenticate\iErdikoUser;
use erdiko\authenticate\services\BasicAuthenticator;

use erdiko\authorize\Authorizer;
use erdiko\authorize\UserInterface;
use erdiko\users\models\User;
use erdiko\users\models\user\event\Log;

class UserAjax extends \erdiko\core\AjaxController
{
    private $id = null;

	/**
	 * @param $action
	 * @param $resource
	 *
	 * @return bool
	 */
	protected function checkAuth($resource = null)
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
            return false;
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

	    $userModel = new User();

            // Check required fields
            $requiredParams = array('email', 'name', 'role', 'password');
            $params = (array) $data;
            foreach ($requiredParams as $param){
                if (empty($params[$param])) {
                    throw new \Exception(ucfirst($param) .' is required.');
                }
            }

            // Check Dupe email [ER-155]
            if(!empty($data->email)) {
                if (false === $userModel->isEmailUnique($data->email)) {
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

    /**
     *
     */
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

    /**
     *
     */
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
                            'created_at'    => $user->getCreatedAt(),
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

    /**
     *
     */
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
			$userModel = new User();
            // Check Dupe email [ER-155]
            if(!empty($params->email)) {
                if (false === $userModel->isEmailUnique($params->email)) {
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

    /**
     *
     */
	public function postDelete()
	{
		$response = array(
			"method" => "delete",
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
            if (empty($data->id)) {
                throw new \Exception("Id is required.");
            }

			$userModel = new User();
			$result = $userModel->deleteUser($data->id);

            if (false == $result) {
                throw new \Exception('User could not be deleted.');
            }

			$response['user'] = array('id' => $data->id);
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

        $data->page_size = 10;
        if(array_key_exists("page_size", $_GET)){
            $data->page_size = $_GET['page_size'];
        }

        $data->sort = 'created_at';
        $data->direction = 'asc';

        $validSort = array('id', 'created_at');
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

            $responseLog = $logModel->getLogsByUserId($currentUser->getUserId(),$data->page, $data->page_size, $data->sort, $data->direction);

            $output = array();
            foreach ($responseLog->logs as $log) {
                $output[] = array('id' => $log->getId(),
                                  'event'      => $log->getEventLog(),
                                  'event_data' => $log->getEventData(),
                                  'created_at' => $log->getCreatedAt()
                );
            }
            $response['success'] = true;
            $response['user_id'] = $currentUser->getUserId();
            $response['activities'] = $output;
            $response['page'] = $data->page;
            $response['page_size'] = $data->page_size;
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
     * get event log activity for specified user_id
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

        $data->page = 0;
        if(array_key_exists("page", $_GET)) {
            $data->page = $_GET['page'];
        }

        $data->page_size = 10;
        if(array_key_exists("page_size", $_GET)){
            $data->page_size = $_GET['page_size'];
        }

        $data->sort = 'created_at';
        $data->direction = 'asc';

        $validSort = array('id', 'created_at');
        $validDirection = array('asc', 'desc');
        try {

            $user_id = null;
            if (array_key_exists("user_id", $_GET)) {
                $user_id = $_GET['user_id'];
            }

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

            if(!is_null($user_id)) {
                $responseLog = $logModel->getLogsByUserId($user_id, $data->page, $data->page_size, $data->sort, $data->direction);
            } else {
                $responseLog = $logModel->getLogs($data->page, $data->page_size, $data->sort, $data->direction);
            }

            $output = array();
            foreach ($responseLog->logs as $log) {
                $output[] = array('id'         => $log->getId(),
                                  'user_id'    => $log->getUserId(),
                                  'event'      => $log->getEventLog(),
                                  'event_data' => $log->getEventData(),
                                  'created_at' => $log->getCreatedAt()
                );
            }
            $response['success']    = true;
            $response['user_id']    = $user_id;
            $response['logs']       = $output;
            $response['page']       = $data->page;
            $response['page_size']  = $data->page_size;
            $response['sort']       = $data->sort;
            $response['direction']  = $data->direction;
            $response['total']      = $responseLog->total;
            $this->setStatusCode(200);
        } catch (\Exception $e) {
            $response['error_message'] = $e->getMessage();
            $response['error_code'] = $e->getCode();
        }

        $this->setContent($response);
    }
    
    /**
     * postChangepass
     *
     */
    public function postChangepass()
    {
        $response = array(
            "method" => "changepass",
            "success" => false,
            "error_code" => 0,
            "error_message" => ""
        );

        try {
            $params = json_decode(file_get_contents("php://input"));
            if (empty($params)) {
                $params = (object) $_POST;
            }

            // Check required fields
            if(empty($params->email) && empty($params->id)) {
                throw new \Exception('User Email or ID is required.');
            }

            if(empty($params->newpass)) {
                throw new \Exception('New password is required.');
            }

            $user = new User();

            if(!empty($params->id)) {
                $userParams = array('id' => $params->id);
            } else {
                $userParams = array('email' => $params->email);
            }

            $userResult = $user->getByParams($userParams);
            if (empty($userResult) || !is_a($userResult[0], 'erdiko\users\entities\User')) {
                throw new \Exception('User not found.');
            }

            $userToChange = $userResult[0];

            $res = $user->save(array('id' => $userToChange->getId(), 'password' => $params->newpass));

            $response['success'] = true;
            $this->setStatusCode(200);
        } catch (\Exception $e) {
            $response['error_message'] = $e->getMessage();
            $response['error_code'] = $e->getCode();
        }

        $this->setContent($response);
    }

}
