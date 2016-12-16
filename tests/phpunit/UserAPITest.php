<?php
/**
 * User API test cases
 *
 * @category   UnitTests
 * @package    tests
 * @copyright  Copyright (c) 2016, Arroyo Labs, http://www.arroyolabs.com
 *
 * @author     Leo Daidone, leo@arroyolabs.com
 */
namespace tests\phpunit;

require_once dirname(__DIR__).'/ErdikoTestCase.php';


class UserAPITest extends \tests\ErdikoTestCase
{
	const url = "http://docker.local:8088/ajax/users/";

	protected $userData;
	protected $userDataUpdate;
	protected static $uid = 0;

    function setUp()
    {
	    $this->userData = array(
			"email"=>"leo@testlabs.com",
			"password"=>"asdf1234",
			"role"=>"1",
			"name"=>"Test",
			"gateway_customer_id"=>""
	    );

	    $this->userDataUpdate = array(
		    "id"=> 0,
		    "email"=>"leo@arroyolabs.com",
		    "password"=>"asdf1234",
		    "role"=>"2",
		    "name"=>"Test_update",
		    "gateway_customer_id"=>"1"
	    );
    }

	/**
	 * @expectedException
	 */
    public function testCreateFail()
    {
    	$url = self::url.'create';
	    $this->_call($url,null,'POST');
    }


    public function testRegister()
    {
	    $url = self::url.'register';
	    $json = $this->_call($url,json_encode($this->userData),'POST');
	    $result = json_decode($json);

	    $this->assertFalse($result->errors);
	    $this->assertEquals($result->body->action, 'register');
	    $this->assertTrue($result->body->success);
	    $this->assertInternalType('int',$result->body->user->id);

	    self::$uid = $result->body->body;
    }

    public function testCreateFailParamMissing(){
        $url = self::url.'register';
        $this->userData->email ='';

        $json = $this->_call($url,json_encode($this->userData),'POST');
        $result = json_decode($json);

        $this->assertFalse($result->errors);
        $this->assertFalse($result->body->success);
        $this->assertEquals($result->body->method, 'register');
        $this->assertEquals($result->body->error_message, "Email is required.");

        // verify data
        $url = self::url.'user/'.self::$uid;
        $json = $this->_call($url,null,'GET');
        $result = json_decode($json);

        $this->assertFalse($result->body->success);
        $this->assertEquals($result->body->error_message, "User not found.");
    }

    public function testUserNotFound()
    {
        $url = self::url.'getuser?id=999999999';
        $json = $this->_call($url,null,'GET');
        $result = json_decode($json);

        $this->assertFalse($result->errors);
        $this->assertEquals($result->body->method, 'getuser');
        $this->assertFalse($result->body->success);
        $this->assertEquals($result->body->error_message, "User not found.");

    }

    public function testUsers()
    {
	    $url = self::url.'getusers';
	    $json = $this->_call($url,null,'GET');
	    $result = json_decode($json);

	    $this->assertFalse($result->errors);
	    $this->assertEquals($result->body->method, 'getusers');
	    $this->assertTrue($result->body->success);
        $this->assertNotNull($result->body->users);
    }

    public function testUpdateFail()
    {
	    $url = self::url.'update';
	    $json = $this->_call($url,$this->userData,'POST');
	    $result = json_decode($json);

	    $this->assertEquals($result->body->method, 'update');
	    $this->assertFalse($result->body->success);
        $this->assertEquals($result->body->error_message, "Id is required.");
    }

    public function testUpdate()
    {
	    $url = self::url.'update';
	    $this->userDataUpdate['id'] = self::$uid;
	    $params = json_encode($this->userDataUpdate);

	    $json = $this->_call($url,$params,'POST');
	    $result = json_decode($json);

	    $this->assertFalse($result->errors);
	    $this->assertEquals($result->body->method, 'update');
	    $this->assertTrue($result->body->success);

	    // verify data
	    $url = self::url.'getuser/id?='.self::$uid;
	    $json = $this->_call($url,null,'GET');
	    $result = json_decode($json);

	    $this->assertEquals($result->body->user->email, $this->userDataUpdate['email']);
	    $this->assertEquals($result->body->user->role, $this->userDataUpdate['role']);
	    $this->assertEquals($result->body->user->name, $this->userDataUpdate['name']);
        $this->assertEquals($result->body->user->last_login, $this->userDataUpdate['last_login']);
	    $this->assertEquals($result->body->user->gateway_customer_id, $this->userDataUpdate['gateway_customer_id']);
    }

    public function testDelete()
    {
	    $url = self::url.'cancel/'.self::$uid;
	    $json = $this->_call($url,null,'GET');
	    $result = json_decode($json);

	    $this->assertFalse($result->errors);
	    $this->assertTrue($result->body->success);
	    $this->assertEquals($result->body->method, 'cancel');
	    $this->assertEquals($result->body->user, self::$uid);

	    // verify data
	    $url = self::url.'getuser/'.self::$uid;
	    $json = $this->_call($url,null,'GET');
	    $result = json_decode($json);

	    $this->assertFalse($result->body->success);
        $this->assertEquals($result->body->error_message, "User not found.");
    }


    public function testDeleteNotExisting(){
        $url = self::url.'cancel/99999999999';
        $json = $this->_call($url,null,'GET');
        $result = json_decode($json);

        $this->assertFalse($result->errors);
        $this->assertFalse($result->body->success);
        $this->assertEquals($result->body->method, 'cancel');
        $this->assertEquals($result->body->error_message, "User could not be deleted.");

        // verify data
        $url = self::url.'getuser/'.self::$uid;
        $json = $this->_call($url,null,'GET');
        $result = json_decode($json);

        $this->assertFalse($result->body->success);
        $this->assertEquals($result->body->error_message, "User could not be deleted.");
    }

	/**
	 * @param        $url
	 * @param        $data
	 * @param string $type
	 *
	 * @return mixed
	 * @throws Exception
	 */
	private function _call($url, $data, $type="GET")
	{
		$curl = curl_init();

		$opts = array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_HTTPHEADER => array(
				"cache-control: no-cache",
				"content-type: application/json"
			)
		);

		if($type=="POST") {
			$opts[CURLOPT_CUSTOMREQUEST] = "POST";
			$opts[CURLOPT_POSTFIELDS] = $data;
		} else {
			$opts[CURLOPT_CUSTOMREQUEST] = "GET";
		}

		curl_setopt_array($curl, $opts);

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			throw new \Exception("cURL Error #:" . $err);
		} else {
			return $response;
		}
	}
}