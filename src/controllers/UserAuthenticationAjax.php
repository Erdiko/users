<?php


/**
 * UserAuthenticationAjax
 *
 * @category    Erdiko
 * @package     User
 * @copyright   Copyright (c) 2016, Arroyo Labs, http://www.arroyolabs.com
 * @author      Julian Diaz, julian@arroyolabs.com
 */

namespace erdiko\users\controllers;

use erdiko\authenticate\JWTAuth;
use erdiko\authenticate\iErdikoUser;

use erdiko\users\models\User;
use erdiko\users\models\Mailgun;

class UserAuthenticationAjax extends \erdiko\core\AjaxController
{

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
            if( is_array($routing)) {
                $var = array_shift($routing);
                $this->id = empty($routing)
                    ? 0
                    : array_shift($routing);
            } else {
                $var = $routing;
            }

            header('Content-Type: application/json');
            return $this->_autoaction($var, 'get');
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

            // load action based off of naming conventions
            header('Content-Type: application/json');
            return $this->_autoaction($var, 'post');
        } else {
            return $this->getNoop();
        }
    }
    
    /**
     * Return TRUE to allow CORS requests 
     * 
     * 
     * @param null $var
     *
     * @return boolean
     */
    public function options($var = null) 
    {
        header('Access-Control-Allow-Credentials: true');
        header("Access-Control-Allow-Headers: *");
        return;
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
     *
     */
    public function postLogin()
    {
        $response = array(
            "method" => "login",
            "success" => false,
            "error_code" => 0,
            "error_message" => ""
        );

        try {
            $data = json_decode(file_get_contents("php://input"));
            if (empty($data)) {
                $data = (object) $_POST;
            }
            // Check required fields
            $requiredParams = array('email','password');
            $params = (array) $data;
            foreach ($requiredParams as $param){
                if (empty($params[$param])) {
                    throw new \Exception(ucfirst($param) .' is required.');
                }
            }

            // init the jwt auth class
            $authenticator = new JWTAuth(new User());

            // get the application secret key
            $config     = \Erdiko::getConfig();

            // we need the secret key!
            if(!array_key_exists("secret_key", $config["site"]) || empty($config["site"]["secret_key"])) {
                throw new \Exception("Secret Key required to create a JWT for user");
            }
            $secretKey  = $config["site"]["secret_key"];

            // collect login params
            $authParams = array(
                'secret_key'    =>  $secretKey, 
                'username'      =>  $data->email, 
                'password'      =>  $data->password
            );

            // attempt to login
            if ($result = $authenticator->login($authParams, 'jwt_auth')) {

                // if successful, return the JWT token
                $response['token']      = $result->token;
                $response['success']    = true;
            } else{
                throw new \Exception("Username or password are wrong. Please try again.");
            }

            $this->setStatusCode(200);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            $response['error_message'] = $e->getMessage();
            $response['error_code'] = $e->getCode();
        }

        $this->setContent($response);
    }

    /**
     *
     *
     */
    public function getLogout()
    {
        $response = array(
            "method" => "logout",
            "success" => false,
            "error_code" => 0,
            "error_message" => ""
        );

        try {
            $authenticator = new BasicAuth(new User());
            $authenticator->logout();
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
     *
     */
    public function postChangePass()
    {
        $response = array(
            "method" => "changepass",
            "success" => false,
            "error_code" => 0,
            "error_message" => ""
        );

        try {
            $data = json_decode(file_get_contents("php://input"));
            if (empty($data)) {
                $data = (object) $_POST;
            }
            // Check required fields
            $requiredParams = array('email', 'currentpass', 'newpass');
            $params = (array) $data;
            foreach ($requiredParams as $param){
                if (empty($params[$param])) {
                    throw new \Exception(ucfirst($param) .' is required.');
                }
            }

            if ($data->currentpass == $data->newpass) {
                throw new \Exception('Current pass and new pass should be different.');
            }

            $authenticator = new BasicAuth(new User());

            if ($authenticator->login(array('username'=>$data->email, 'password'=>$data->currentpass),'erdiko_user')) {
                $usermodel = new \erdiko\users\models\User();
                $currentUser = $authenticator->current_user();
                $currentUser->save(array('id' => $currentUser->getUserId(), 'password' => $data->newpass));

                $response['success'] = true;
            } else{
                throw new \Exception("Username or password are wrong. Please try again.");
            }
            $this->setStatusCode(200);
        } catch (\Exception $e) {
            $response['error_message'] = $e->getMessage();
            $response['error_code'] = $e->getCode();
        }

        $this->setContent($response);
    }

    /**
     *
     *
     */
    public function postForgotPass()
    {
        $response = array(
            "method" => "forgotpass",
            "success" => false,
            "error_code" => 0,
            "error_message" => ""
        );

        try {
            $data = json_decode(file_get_contents("php://input"));
            if (empty($data)) {
                $data = (object) $_POST;
            }

            // Check required fields
            $requiredParams = array('email');
            $params = (array) $data;

            foreach ($requiredParams as $param){
                if (empty($params[$param])) {
                    throw new \Exception(ucfirst($param) .' is required.');
                }
            }

            $email = $data->email;
            $userModel = new \erdiko\users\models\User();
            $result = $userModel->getByParams(array('email' => $email));
            if (!empty($result)) {
                $userEntity = $result[0];
                $userModel->setEntity($userEntity);

                $randomPassword = $this->getRandomPassword();
                $userModel->save(array('id' => $userModel->getUserId(), 'password' => $randomPassword));

                $mailgunModel = new \erdiko\users\models\Mailgun();
                $emailData = array('newPass' => $randomPassword);
                $viewPath =  dirname(__FILE__)."/..";
                $view = new \erdiko\core\View('forgotPass', $emailData, $viewPath);
                $mailgunModel->forgotPassword($email, $view->toHtml());

                $this->setStatusCode(200);
                $response['success'] = true;
            }
            else{
                throw new \Exception('Email not found.');
            }
        } catch (\Exception $e) {
            $response['error_message'] = $e->getMessage();
            $response['error_code'] = $e->getCode();
        }
        $this->setContent($response);
    }

    /**
     *
     *
     */
    private function getRandomPassword() 
    {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $pass = array();
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass);
    }

}
