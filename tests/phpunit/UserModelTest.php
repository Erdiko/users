<?php
/**
 * User entity test cases
 *
 * @category   UnitTests
 * @package    tests
 * @copyright  Copyright (c) 2016, Arroyo Labs, http://www.arroyolabs.com
 *
 * @author     John Arroyo, john@arroyolabs.com
 * @author     Leo Daidone, leo@arroyolabs.com
 */
namespace tests\phpunit;

require_once dirname(__DIR__).'/ErdikoTestCase.php';


class UserModelTest extends \tests\ErdikoTestCase
{
	protected $entityManager = null;
	protected $userArrayData;
	protected $userArrayUpdate;
    protected $roleAdminArrayData;
    protected $roleAnonymousArrayData;
	protected $model;
    protected $roleModel;
    protected $anonymousId;
    protected $adminId;
    protected $rolesCreated;

	protected static $lastID;

	function setUp()
	{
		$this->entityManager = \erdiko\doctrine\EntityManager::getEntityManager();
		$this->userArrayData = array(
			"email"=>"leo@testlabs.com",
			"password"=>"asdf1234",
			"role"=>1,
			"name"=>"Test",
		);
		$this->userArrayUpdate = array(
			"id"=>null,
			"email"=>"leo@arroyolabs.com",
			"password"=>"asdf1234",
			"role"=>2,
			"name"=>"Test 2",
		);

        $this->rolesCreated = array();

        $this->roleAdminArrayData = array(
            "name" => 'admin',
            "active" => 1
        );

        $this->roleAnonymousArrayData = array(
            "name" => 'anonymous',
            "active" => 1
        );

        //create Roles needed to tests.
        $this->roleModel = new \erdiko\users\models\Role();

        $roleEntity = $this->roleModel->findByName('admin');
        if(empty($roleEntity)) {
            $id = $this->roleModel->create($this->roleAdminArrayData);
            $this->rolesCreated[] = $id;
            $this->adminId = $id;
        }
        else{
            $this->adminId = $roleEntity->getId();
        }



        $roleEntity = $this->roleModel->findByName('anonymous');
        if(empty($roleEntity)) {
            $id = $this->roleModel->create($this->roleAnonymousArrayData);
            $this->rolesCreated[] = $id;
            $this->anonymousId = $id;
        }
        else{
            $this->anonymousId = $roleEntity->getId();
        }


        $this->model = new \erdiko\users\models\User();
	}

	/**
	 * @expectedException Exception
	 */
	public function testSetEntityFail()
	{
        $obj   = (object) array();
		$this->model->setEntity($obj);
	}

	public function testSetEntity()
	{
		$entity = new \erdiko\users\entities\User();
		$entity->setId( 0 );
		$entity->setRole( $this->anonymousId);
		$entity->setName( 'anonymous' );
		$entity->setEmail( 'anonymous' );
		$this->model->setEntity($entity);
        $this->assertTrue(true);
	}

	/**
	 *
	 */
	public function testGetEntity()
	{
		$entity = $this->model->getEntity();

		$this->assertInstanceOf('\erdiko\users\entities\User', $entity);
		$this->assertEquals('anonymous', $entity->getName());
		$this->assertEquals($this->anonymousId, $entity->getRole());
		$this->assertEquals('anonymous', $entity->getEmail());
	}

	/**
	 *
	 */
	public function testMarshall()
	{
		$encoded = $this->model->marshall();
		$this->assertInternalType('string', $encoded);

		$out = (object)array(
			"id" => 0,
			"name" => 'anonymous',
			"role" => $this->anonymousId,
			"email" => 'anonymous',
			'gateway_customer_id' => null,
			'last_login' => null
		);

		$this->assertEquals($out, json_decode($encoded));
	}

	/**
	 *
	 */
	public function testUnmarshall()
	{
		$object = (object)array(
			"id" => 0,
			"name" => 'anonymous',
			"role" => $this->anonymousId,
			"email" => 'anonymous',
			'gateway_customer_id' => null,
			'last_login' => null
		);
		$out = $this->model->unmarshall(json_encode($object));

		$this->assertInstanceOf('\erdiko\users\models\User', $out);
		$this->assertNotEmpty($this->model->getEntity());
	}

	public function testGetSalted()
	{
		$password = "asdf1234";
		$salted = $this->model->getSalted($password);
		$expect = $password . \erdiko\users\models\User::PASSWORDSALT;
		$this->assertEquals($expect, $salted);
	}

	// CRUD related
	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage User data is missing
	 */
	public function testCreateUserNoData()
	{
		$this->model->createUser();
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage email & password are required
	 */
	public function testCreateUserFail()
	{
		$data = $this->userArrayData;
		unset($data['email'], $data['password']);
		$this->model->createUser($data);
	}

	/**
	 *
	 */
	public function testCreateUser()
	{
		$data = $this->userArrayData;
		$result = $this->model->createUser($data);

		$this->assertTrue($result);

		$newEntity = $this->model->getEntity();
		$this->userArrayUpdate['id'] = $newEntity->getId();
		self::$lastID = $newEntity->getId();
	}

	public function testIsAnonymous()
	{
		$result = $this->model->isAnonymous();
		$this->assertTrue($result);
	}

	/**
	 *
	 */
	public function testAuthenticateInvalid()
	{
		$logged = $this->model->isLoggedIn();
		$this->assertFalse($logged);

		$result = $this->model->authenticate( null, null );
		$this->assertFalse( $result );
	}

    /**
     *
     */
    public function testAuthenticate()
    {
        $data = $this->userArrayData;
        $data['role'] = $this->adminId;
        $result = $this->model->createUser($data);
        $newEntity = $this->model->getEntity();
         self::$lastID = $newEntity->getId();


        $email = $this->userArrayData['email'];
        $password = $this->userArrayData['password'];

        $result = $this->model->authenticate($email, $password);

        $this->assertNotEmpty($result);
        $this->assertInstanceOf('\erdiko\users\models\User', $result);

        // double check
        $logged = $result->isLoggedIn();
        $this->assertTrue($logged);
    }


    /**
     *
     */
    public function testLastLogin()
    {
        $data = $this->userArrayData;
        $data['role'] = $this->adminId;
        $result = $this->model->createUser($data);
        $newEntity = $this->model->getEntity();
        self::$lastID = $newEntity->getId();


        $email = $this->userArrayData['email'];
        $password = $this->userArrayData['password'];

        $result = $this->model->authenticate($email, $password);

        $entity = $this->model->getEntity();
        $this->assertNotEmpty($entity->getLastLogin());

    }

	public function testSave()
	{
		$params = $this->userArrayUpdate;
		$params['password'] = $this->model->getSalted($this->userArrayUpdate['password']);
		$params['id'] = self::$lastID;
        $params['role'] = $this->adminId;

		$result = $this->model->save($params);

		$this->assertInternalType('int',$result);
		$this->assertTrue(($result > 0));

		$entity = $this->model->getEntity();
		$this->assertEquals($entity->getEmail(),$this->userArrayUpdate['email']);
		$this->assertEquals($entity->getName(),$this->userArrayUpdate['name']);
		$this->assertEquals($entity->getRole(),$this->adminId);

		$this->assertTrue($this->model->isAdmin());

		$newEntity = $this->model->getEntity();
		$this->userArrayUpdate['id'] = $newEntity->getId();
		self::$lastID = $newEntity->getId();
	}

	public function testDelete()
	{
		$id = empty($this->userArrayUpdate['id']) ? self::$lastID : $this->userArrayUpdate['id'];
		$result = $this->model->deleteUser($id);

		$this->assertTrue($result);
	}


    public function testDeleteNullParam()
    {
        $id = null;
        $result = $this->model->deleteUser($id);

        $this->assertFalse($result);
    }

	public function testDeleteNotExisting(){
	    $id = 99999999999;
        $result = $this->model->deleteUser($id);

        $this->assertFalse($result);
    }

	function tearDown()
	{
	   foreach ($this->rolesCreated as $id){
	       $this->roleModel->delete($id);
       }
       unset($this->entityManager);
       if(!empty(self::$lastID)){
           $this->model->deleteUser(self::$lastID);
       }

	}
}