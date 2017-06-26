<?php
/**
 * User Event Log test cases
 *
 * @category   UnitTests
 * @package    tests
 * @copyright  Copyright (c) 2017, Arroyo Labs, http://www.arroyolabs.com
 * @author     Julian Diaz, julian@arroyolabs.com
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

class UserEventLogModelTest extends \tests\ErdikoTestCase
{

    protected $entityManager = null;
    protected $_logs = null;
    protected $id = null;

    const EVENT_LOG_NAME = "backend-test-profile-create";

    /**
     *
     *
     */
    function setUp()
    {
        $this->entityManager = \erdiko\doctrine\EntityManager::getEntityManager();
	    $this->doLogin('super@mail.com');
        $this->_logs = new \erdiko\users\models\user\event\Log();
    }

    /**
     *
     *
     */
    function tearDown()
    {
        if ($this->id) {
            $entity = $this->entityManager->getRepository('\erdiko\users\entities\user\event\Log')
                ->find($this->id);
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }

        // delete all remaining test log entries
        $records = $this->entityManager->getRepository('\erdiko\users\entities\user\event\Log')
                        ->findBy(array("event_log" => self::EVENT_LOG_NAME));
        foreach($records as $record) {
            $this->entityManager->remove($record);
            $this->entityManager->flush();
    }
    }

    /**
     * @expectedException \Exception
     */
    public function testCreateWithInvalidParams()
    {
        $this->_logs->create();
    }

    /**
     * @expectedException \Exception
     */
    public function testCreateWithInvalidUid()
    {
        $uid = "blah!";
        $type = 'login';
        $this->_logs->create($uid, $type);
    }

    /**
     * @expectedException \Exception
     */
    public function testCreateWithInvalidType()
    {
        $this->_logs->create(null);
    }

    /**
     * test the creation of one entity with the model
     */
    public function testCreate()
    {
        $uid = 1;
        $data = array('email'=>'test@mail.com');
        $entityId = $this->_logs->create($uid, self::EVENT_LOG_NAME, $data);
        $result = $this->_logs->getLogsByUserId(1)->logs;

        $this->assertGreaterThan(0, $entityId);
        $this->assertEquals(1,$result[0]->getUserId(),"Result has correct User ID.");

        $this->id = $entityId;
    }


    /**
     * @expectedException \Exception
     */
    public function testFindByIdNull()
    {
        $this->_logs->findById(null);
    }


    /**
     * @depends testCreate
     * @expectedException \Exception
     */
    public function testLogByUserIdNull()
    {
        $this->_logs->getLogsByUserId(null);
    }

    /**
     * @depends testCreate
     */
    public function testLogByUserIdMissing()
    {
        $result  = $this->_logs->getLogsByUserId(99999999999999);
        $this->assertEmpty($result->logs);
    }

    /**
     *
     * @depends testLogByUserIdMissing
     */
    public function testGetAllLogs()
    {
        $uid = 1;
        $data = array('email'=>'test@mail.com');
        $this->_logs->create($uid, self::EVENT_LOG_NAME, $data);
        $result = $this->_logs->getLogsByUserId(1)->logs;
        $this->id = $result[0]->getId();

        $result = $this->_logs->getAllLogs();
        $this->assertTrue(is_array($result), "Returned value is an array");
        $this->assertInstanceOf('\erdiko\users\entities\user\event\Log', $result[0], 'Returned value is a \erdiko\users\entities\Log Object');
    }

    /**
     * test findById method
     */
    public function testFindById()
    {
        $uid = 1;
        $data = array('email'=>'test@mail.com');
        $this->id = $this->_logs->create($uid, self::EVENT_LOG_NAME, $data);

        $result = $this->_logs->findById($this->id);
        $this->assertEquals($this->id, $result->getId());
        $this->assertInstanceOf('\erdiko\users\entities\user\event\Log', $result, 'Returned value is a \erdiko\users\entities\Log Object');
    }

    /**
     *
     *
     */
    public function testGetLogs() 
    {
        $uid = 1;
        $data = array('email'=>'test@mail.com');
        $entityId = $this->_logs->create($uid, self::EVENT_LOG_NAME, $data);
        $result = $this->_logs->getLogs();

        $this->assertGreaterThan(0, $entityId);
        $this->assertEquals($result->total, count($result->logs), "Result has log counts");

        $this->id = $entityId;
    }

	protected function doLogin($type='bar@mail.com')
	{
		$_userProvider = new InMemoryUserProvider(
			array(
				'super@mail.com' => array(
					'password' => 'asdf1234',
					'roles'    => array('super_admin'),
				),
				'bar@mail.com' => array(
					'password' => 'asdf1234',
					'roles'    => array('admin'),
				),
				'foo@mail.com' => array(
					'password' => 'asdf1234',
					'roles'    => array('user'),
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

		$token = new UsernamePasswordToken($type, "asdf1234", "main", array());

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
