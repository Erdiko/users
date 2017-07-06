<?php

namespace erdiko\users\tests\phpunit;

use erdiko\authenticate\services\JWTAuthenticator;
use erdiko\users\controllers\admin\UserAjax;
use erdiko\users\models\User;
use GuzzleHttp\Client;

require_once dirname(__DIR__) . '/ErdikoTestCase.php';

class UserAjaxTest extends \tests\ErdikoTestCase
{

    protected $loginData;
    protected $validCredentials = [
        'username' => 'erdiko.super@arroyolabs.com',
        'password' => 'master_password'
    ];
    protected $userModel;
    protected $logData;
    protected $user;
    protected $token;

    public function setup()
    {
        $this->initDummySession();
//        $this->initModels();
        $this->initBasicLoginData();
    }

    public function testPostCreate()
    {
//        $this->loginAction(true);
        $client = new Client(['base_uri' => 'http://webserver']);

        $response = $client->post('/ajax/users/authentication/login', [
            'headers' => [
                'Connection' => 'keep-alive'
            ],
            'json' => [
                'email' => $this->validCredentials['username'],
                'password' => $this->validCredentials['password'],
            ]
        ]);
        $authResponse = json_decode($response->getBody());
        $token = $authResponse->body->token;

//        $headers = $response->getHeaders();
//        $setCookieRaw = explode('', $headers['Set-Cookie']);
//
//        var_dump($headers['Set-Cookie']);

        $response = $client->get('/ajax/erdiko/users/admin/list',[
            'headers' => [
//                'Cookie' => $phpsessid,
                'Connection' => 'keep-alive',
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json'
            ]
        ]);

        var_dump((string)$response->getBody());
    }

    /**
     * Initialize $_SESSION for login process
     */
    private function initDummySession()
    {
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
    }

    /**
     *  Initialize Basic Login Data
     */
    private function initBasicLoginData()
    {
        $config     = \Erdiko::getConfig();
        $this->loginData = [
            'secret_key' => $config["site"]["secret_key"]
        ];
    }

    /**
     * Retrieve Valid Credentials
     *
     * @return array
     */
    private function getValidCredentials()
    {
        return array_merge($this->loginData, $this->validCredentials);
    }

    /**
     * Retrieve Invalid Credentials
     *
     * @return array
     */
    private function getInvalidCredentials($user)
    {
        $invalidCredentials = $this->validCredentials;
        $invalidCredentials['password'] = rand(0, 99999);
        if (!$user) {
            $invalidCredentials['username'] = $invalidCredentials['username'].rand(0, 99999);
        }

        return array_merge($this->loginData, $invalidCredentials);
    }

    /**
     * Login Action
     *
     * @param bool $valid
     * @return bool
     */
    protected function loginAction($valid=true, $user=true)
    {
        $authenticator = new JWTAuthenticator(new User());
        $config     = \Erdiko::getConfig();
        $secretKey  = $config["site"]["secret_key"];
        $credentials = $valid ? $this->getValidCredentials() : $this->getInvalidCredentials($user);
        $this->logData = ['email' => $credentials['username']];
        $authParams = $credentials;
        $authParams['secret_key'] = $secretKey;

        try {
            $result = $authenticator->login($authParams, 'jwt_auth');
            $this->user = $result->user->getEntity();
            $this->token = $result->token;
            return true;
        } catch (\Exception $e) {
            // Mute Exception to continue with the process.
        }

        $this->user = $this->userModel->getGeneral()->getEntity();
        $this->logData['message'] = "User {$credentials['username']} not found.";
        $users = $this->userModel->getByParams(['email'=>$credentials['username']]);
        if (count($users)>0) {
            $this->user = $users[0];
            $this->logData['message'] = "Invalid Password";
        }
        return false;
    }

}
