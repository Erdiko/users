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
	const url = "http://docker.local:8088/api/users/";

	protected $userData;
	protected $userDataUpdate;
	protected static $uid = 0;

    function setUp()
    {
	    $this->userData = array(
			"email"=>"leo@testlabs.com",
			"password"=>"asdf1234",
			"role"=>"super-admin",
			"name"=>"Test",
			"gateway_customer_id"=>""
	    );

	    $this->userDataUpdate = array(
		    "id"=> 0,
		    "email"=>"leo@arroyolabs.com",
		    "password"=>"asdf1234",
		    "role"=>"admin",
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

    public function testCreate()
    {
	    $url = self::url.'create';
	    $json = $this->_call($url,json_encode($this->userData),'POST');
	    $result = json_decode($json);

	    $this->assertFalse($result->errors);
	    $this->assertEquals($result->body->action, 'create');
	    $this->assertTrue($result->body->success);
	    $this->assertInternalType('int',$result->body->body);

	    self::$uid = $result->body->body;
    }

    public function testRead()
    {
	    $url = self::url.'read/'.self::$uid;
	    $json = $this->_call($url,null,'GET');
	    $result = json_decode($json);

	    $this->assertFalse($result->errors);
	    $this->assertEquals($result->body->action, 'read');
	    $this->assertTrue($result->body->success);
	    $this->assertEquals($result->body->body->email, $this->userData['email']);
	    $this->assertEquals($result->body->body->role, $this->userData['role']);
	    $this->assertEquals($result->body->body->name, $this->userData['name']);
	    $this->assertEquals($result->body->body->gateway_customer_id, $this->userData['gateway_customer_id']);

    }

    public function testUpdateFail()
    {
	    $url = self::url.'update';
	    $json = $this->_call($url,$this->userData,'POST');
	    $result = json_decode($json);

	    $this->assertEquals($result->body->action, 'update');
	    $this->assertFalse($result->body->success);
    }

    public function testUpdate()
    {
	    $url = self::url.'update';
	    $this->userDataUpdate['id'] = self::$uid;
	    $params = json_encode($this->userDataUpdate);

	    $json = $this->_call($url,$params,'POST');
	    $result = json_decode($json);

	    $this->assertFalse($result->errors);
	    $this->assertEquals($result->body->action, 'update');
	    $this->assertTrue($result->body->success);

	    // verify data
	    $url = self::url.'read/'.self::$uid;
	    $json = $this->_call($url,null,'GET');
	    $result = json_decode($json);

	    $this->assertEquals($result->body->body->email, $this->userDataUpdate['email']);
	    $this->assertEquals($result->body->body->role, $this->userDataUpdate['role']);
	    $this->assertEquals($result->body->body->name, $this->userDataUpdate['name']);
	    $this->assertEquals($result->body->body->gateway_customer_id, $this->userDataUpdate['gateway_customer_id']);
    }

    public function testDelete()
    {
	    $url = self::url.'delete/'.self::$uid;
	    $json = $this->_call($url,null,'GET');
	    $result = json_decode($json);

	    $this->assertFalse($result->errors);
	    $this->assertTrue($result->body->success);
	    $this->assertEquals($result->body->action, 'delete');
	    $this->assertEquals($result->body->body, "User ".self::$uid." successfully deleted.");

	    // verify data
	    $url = self::url.'read/'.self::$uid;
	    $json = $this->_call($url,null,'GET');
	    $result = json_decode($json);

	    $this->assertEmpty($result->body->body);
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