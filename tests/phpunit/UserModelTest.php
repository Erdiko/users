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

use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;
use Symfony\Component\Security\Core\User\UserChecker;

class UserModelTest extends \tests\ErdikoTestCase
{
	protected $entityManager = null;
	protected $userArrayData;
	protected $userArrayUpdate;
    protected $roleAdminArrayData;
    protected $roleUserArrayData;
	protected $roleAnonymousData;
    protected $model;
    protected $roleModel;
    protected $userId;
    protected $adminId;
    protected $anonymousId;
    protected $rolesCreated;

	protected static $lastID;

	function setUp()
	{
		$pass = microtime();
		$email = "test+{$pass}@arroyolabs.com";
		$emailUpdate = "test+{$pass}+update@arroyolabs.com";

		$this->entityManager = \erdiko\doctrine\EntityManager::getEntityManager();
		$this->userArrayData = array(
			"email" => $email,
			"password" => $pass,
			"role"=>1,
			"name"=>"Test",
		);
		$this->userArrayUpdate = array(
			"id"=>null,
			"email" => $emailUpdate,
			"password" => $pass,
			"role"=>2,
			"name"=>"Test 2",
		);

        $this->rolesCreated = array();

        $this->roleAdminArrayData = array(
            "name" => 'admin',
            "active" => 1
        );

        $this->roleUserArrayData = array(
            "name" => 'user',
            "active" => 1
        );

        $this->roleAnonymousArrayData = array(
            "name" => 'anonymous',
            "active" => 1
        );

		$this->doLogin('erdiko.super@arroyolabs.com');

        //create Roles needed to tests.
        $this->roleModel = new \erdiko\users\models\Role();

        $roleEntity = $this->roleModel->findByName('admin');
        if (empty($roleEntity)) {
            $id = $this->roleModel->create($this->roleAdminArrayData);
            $this->rolesCreated[] = $id;
            $this->adminId = $id;
        } else{
            $this->adminId = $roleEntity->getId();
        }


        $roleEntity = $this->roleModel->findByName('user');
        if (empty($roleEntity)) {
            $id = $this->roleModel->create($this->roleUserArrayData);
            $this->rolesCreated[] = $id;
            $this->userId = $id;
        } else{
            $this->userId = $roleEntity->getId();
        }

        $roleEntity = $this->roleModel->findByName('anonymous');
        if (empty($roleEntity)) {
            $id = $this->roleModel->create($this->roleAnonymousData);
            $this->rolesCreated[] = $id;
            $this->anonymousId = $id;
        } else{
            $this->anonymousId = $roleEntity->getId();
        }

        $this->model = new \erdiko\users\models\User();
	}

	/**
	 * @expectedException Exception
     * test the entity cant be created
	 */
	public function testSetEntityFail()
	{
        $obj   = (object) array();
		$this->model->setEntity($obj);
	}

    /**
     * test setEntity method works.
     */
	public function testSetEntity()
	{
		$entity = new \erdiko\users\entities\User();
		$entity->setId( 0 );
		$entity->setRole( $this->userId);
		$entity->setName( 'anonymous' );
		$entity->setEmail( 'anonymous' );
		$this->model->setEntity($entity);
        $this->assertTrue(true);
	}

    /**
     * test getEntity method works.
     */
	public function testGetEntity()
	{
		$entity = $this->model->getEntity();

		$this->assertInstanceOf('\erdiko\users\entities\User', $entity);
		$this->assertEquals('user', $entity->getName());
		$this->assertEquals($this->anonymousId, $entity->getRole());
		$this->assertEquals('user', $entity->getEmail());
	}

	/**
	 * test mashall method works. the json received should be equal to the mocked.
	 */
	public function testMarshall()
	{
		$encoded = $this->model->marshall();
		$this->assertInternalType('string', $encoded);

		$out = (object)array(
			"id" => 0,
			"name" => 'user',
			"role" => $this->anonymousId,
			"email" => 'user',
			'gateway_customer_id' => null,
			'last_login' => null
		);

		$this->assertEquals($out, json_decode($encoded));
	}

	/**
	 * test unmarshall works, the result should be a User instance.
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

    /**
     * test getSalted method works.
     */
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
     *
     * test createUser is not working with empty params.
	 */
	public function testCreateUserNoData()
	{
		$this->model->createUser();
	}

	/**
	 * Case 1: no email and no password
	 * @expectedException \Exception
	 * @expectedExceptionMessage email & password are required
     *
     * test createUser is not working without required params.
	 */
	public function testCreateUserFail1()
	{
		$data = $this->userArrayData;
		unset($data['email'], $data['password']);
		$this->model->createUser($data);
	}

	/**
	 * Case 2: no email
	 * @expectedException \Exception
	 * @expectedExceptionMessage email is required
     *
     * test createUser is not working without required params.
	 */
	public function testCreateUserFail2()
	{
		$data = $this->userArrayData;
		unset($data['email']);
		$this->model->createUser($data);
	}

	/**
	 * test createUser works with params required.
	 */
	public function testCreateUser()
	{
		$data = $this->userArrayData;
		$result = $this->model->createUser($data);

		$this->assertGreaterThan(0, $result);

		$this->userArrayUpdate['id'] = $result;
		self::$lastID = $result;
	}

	/**
	 * Test that multiple users with same email fails
	 * @expectedException \Exception
	 * @expectedExceptionMessage Can not create user with duplicate email
	 */
	public function testCreateMultipleUsers()
	{
		$data = $this->userArrayData;
		$result = $this->model->createUser($data);
		$result = $this->model->createUser($data); // Duplicate user
	}

	public function testIsAnonymous()
	{
		$result = $this->model->isAnonymous();
		$this->assertTrue($result);
	}

	/**
	 * test authenticate is not working without required params.
	 */
	public function testAuthenticateInvalid()
	{
		$logged = $this->model->isLoggedIn();
		$this->assertFalse($logged);

		$result = $this->model->authenticate( null, null );
		$this->assertFalse( $result );
	}

    /**
     * test authenticate is working with required params.
     */
    public function testAuthenticate()
    {
        $data = $this->userArrayData;
        $data['role'] = $this->adminId;
        self::$lastID  = $this->model->createUser($data);

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
     * test lastLogin attribute is set after login.
     */
    public function testLastLogin()
    {
        $data = $this->userArrayData;
        $data['role'] = $this->adminId;
        $result = $this->model->createUser($data);
        self::$lastID = $result;

        $email = $this->userArrayData['email'];
        $password = $this->userArrayData['password'];

        $result = $this->model->authenticate($email, $password);

        $entity = $this->model->getEntity();
        $this->assertNotEmpty($entity->getLastLogin());
    }



    /**
     * test getUsers method is working
     */
    public function testGetUsers()
    {
        $data = $this->userArrayData;
        $data['role'] = $this->adminId;
        $result = $this->model->createUser($data);
        self::$lastID = $result;

        $results = $this->model->getUsers();

        $adminCount = count($results->users);
        $this->assertGreaterThan(0, $adminCount, "some results have been returned");
        $this->assertTrue(($results->total == $adminCount), "expected count returned");

        // check the first result
        $user = $results->users[0];
        $this->assertTrue(!empty($user), "first result is not empty");
        $this->assertTrue(!empty($user->getId()), "first result ID is not empty");

        //TODO test the paging and other variables
        if ($results->users > 10) {
            $results = $this->model->getUsers(1,10);
            $count = count($results->users);
            $this->assertTrue((11 > $count), "expected number of results have been returned");
        }

    }

    /**
     * test save method is working, and the role is correct.
     */
	public function testSave()
	{
		$params = $this->userArrayUpdate;
		$params['password'] = $this->model->getSalted($this->userArrayUpdate['password']);
        $params['role'] = $this->adminId;
        $params['name'] = $this->userArrayUpdate['name'];

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


    /**
     * same goal the prior test, but with an existent user.
     */
    public function testSaveExist()
    {
        $data = $this->userArrayData;
        $data['role'] = $this->adminId;
        $result = $this->model->createUser($data);
        self::$lastID = $result;

        $params['id'] = self::$lastID;
        $params['password'] = $this->model->getSalted($this->userArrayUpdate['password']);
		$params['email'] = $this->userArrayUpdate['email'];
        $params['role'] = $this->adminId;
        $params['name'] = $this->userArrayUpdate['name'];

        $result = $this->model->save($params);

        $this->assertInternalType('int',$result);
        $this->assertTrue(($result > 0));

        $entity = $this->model->getEntity();
        $this->assertEquals($entity->getEmail(),$entity->getEmail());
        $this->assertEquals($entity->getName(),$this->userArrayUpdate['name']);
        $this->assertEquals($entity->getRole(),$this->adminId);

        $this->assertTrue($this->model->isAdmin());

        $newEntity = $this->model->getEntity();
        $this->userArrayUpdate['id'] = $newEntity->getId();
        self::$lastID = $newEntity->getId();
    }

	public function testDelete()
	{
        $data = $this->userArrayData;
        $data['role'] = $this->adminId;
        $result = $this->model->createUser($data);
        self::$lastID = $result;

		$result = $this->model->deleteUser(self::$lastID );

		$this->assertTrue($result);
	}


    /**
     * test delete is not working when a null id is given.
     */
    public function testDeleteNullParam()
    {
        $id = null;
        $result = $this->model->deleteUser($id);

        $this->assertFalse($result);
    }

    /**
     * test delete method is not working with an id not real.
     */

	public function testDeleteNotExisting()
    {
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

	protected function doLogin($type='bar@mail.com')
	{
		$_userProvider = new InMemoryUserProvider(
			array(
				'erdiko.super@arroyolabs.com' => array(
					'password' => '0ce44ca7610894b8da8f2968d42623b3',
					'roles'    => array('super_admin'),
				),
				'erdiko@arroyolabs.com' => array(
					'password' => '0acc6ce8fdc230b30c6f1982be61e331',
					'roles'    => array('admin'),
				),
				'user.bar@arroyolabs.com' => array(
					'password' => '9fc9499787385f63da57293c71bb6aef',
					'roles'    => array('anonymous'),
				),
			)
		);
		$encoderFactory = new \Symfony\Component\Security\Core\Encoder\EncoderFactory(array(
			// We simply use plaintext passwords for users from this specific class
			'Symfony\Component\Security\Core\User\User' => new PlaintextPasswordEncoder(),
		));
		// The user checker is a simple class that allows to check against different elements (user disabled, account expired etc)
		$userChecker = new UserChecker();
		$userProvider = array(
			new DaoAuthenticationProvider($_userProvider, $userChecker, 'main', $encoderFactory, true),
		);

		$authenticationManager = new AuthenticationProviderManager($userProvider, false);

		$token = new UsernamePasswordToken($type, "0ce44ca7610894b8da8f2968d42623b3", "main", array());

		$tokenStorage = new TokenStorage();
		$authToken = $authenticationManager->authenticate($token);

		$tokenStorage->setToken($authToken);
		$_SESSION['tokenstorage'] = $tokenStorage;
	}

	private function startSession()
	{
		if(session_id() == '') {
			@session_start();
		} else {
			if (session_status() === PHP_SESSION_NONE) {
				@session_start();
			}
		}
	}

	protected function invalidateToken()
	{
		$this->startSession();
		if(array_key_exists('tokenstorage',$_SESSION) && !empty($_SESSION['tokenstorage'])) {
			$_SESSION['tokenstorage'] = null;
			session_destroy();
		}
	}
}
