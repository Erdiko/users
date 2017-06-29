<?php
/**
 * Role model test cases
 *
 * @category   UnitTests
 * @package    tests
 * @copyright  Copyright (c) 2016, Arroyo Labs, http://www.arroyolabs.com
 *
 * @author     Julian Diaz, julian@arroyolabs.com
 */

namespace tests\phpunit;

use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;
use Symfony\Component\Security\Core\User\UserChecker;

require_once dirname(__DIR__) . '/ErdikoTestCase.php';

class RoleTest extends \tests\ErdikoTestCase
{
    protected $entityManager = null;
    protected $roleModel = null;
    protected $modelArray = null;
    protected $userArray = null;
    protected $userId = null;
    protected $id = null;

    function setUp()
    {
	    $this->startSession();
    	$_SESSION = [];
        $this->entityManager = \erdiko\doctrine\EntityManager::getEntityManager();
        $this->modelArray = array(
            'id'=>0,
            'active' => 1,
            'name' => 1,
        );
        $this->userArray = array(
            'email' => 'user+'.time().'@email.com',
            'password' => 'booyah_'.time(),
            'name' => 'user+'.time(),
            'role' => 1,
            'gateway_customer_id' => time()
        );

	    $this->doLogin('erdiko.super@arroyolabs.com');

        try {
	        $this->roleModel = new \erdiko\users\models\Role();
        } catch (\Exception $e) {
        	var_dump($e->getMessage());
        }
    }

    /**
     * test the Role is created.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage You are not allowed
     */
    function testCreateUnauthorized()
    {
	    $this->invalidateToken();
	    $this->id = $this->roleModel->create( $this->modelArray );
	    $this->assertGreaterThan( 0, $this->id );
    }

    /**
     * test the Role is created.
     */
    function testCreate()
    {
	    $this->doLogin('erdiko.super@arroyolabs.com');
	    $this->id = $this->roleModel->create( $this->modelArray );
	    $this->assertGreaterThan( 0, $this->id );
	}


    /**
     * test the findById is working. The entity should exist.
     */
    function testFindById()
    {
        $this->id = $this->roleModel->create($this->modelArray);
        $entity = $this->roleModel->findById($this->id);
        $this->assertNotNull($entity);
    }


    /**
     * test findById with an fake id. The entity should not exist.
     *
     */
    function testFindByNotExist()
    {
        $id = 999999999;
        $result = $this->roleModel->findById($id);
        $this->assertNull($result);
    }

    /**
     * @expectedException \Exception
     * test the findById method should brake if a null id is given.
     */

    function testFindByBreaks()
    {
        $id = null;
        $result = $this->roleModel->findById($id);
        $this->assertNull($result);
    }

    /**
     * test findByName with a real id given. Entity should exist.
     */
    function testFindByName()
    {
        $this->id = $this->roleModel->create($this->modelArray);
        $entity = $this->roleModel->findById($this->id);
        $entityfound = $this->roleModel->findByName($entity->getName());
        $this->assertNotNull($entity);
        $this->assertEquals($entity->getId(),$entityfound->getId());
    }

    /**
     * test save method a new entity should be created.
     */

    function testSaveNewOne()
    {
        $this->id = $this->roleModel->save($this->modelArray);
        $this->assertGreaterThan(0,$this->id);
    }

    /**
     * test save method, the entity exist then should update params.
     */
    function testSaveExistent()
    {
        $this->id = $this->roleModel->create($this->modelArray);
        $this->modelArray['id'] = $this->id;
        $new_id = $this->roleModel->save($this->modelArray);
        $this->assertEquals($new_id,$this->id);
    }

    /**
     * test getCountByRole the number of entities should not be 0
     */
    function testGetCountByRole()
    {
        $userEntity = new \erdiko\users\entities\User;
        $userEntity->setEmail($this->userArray['email']);
        $userEntity->setPassword($this->userArray['password']);
        $userEntity->setName($this->userArray['name']);
        $userEntity->setRole($this->userArray['role']);
        $userEntity->setGatewayCustomerId($this->userArray['gateway_customer_id']);

        // Save
        $this->entityManager->getRepository('erdiko\users\entities\User');
        $this->entityManager->persist($userEntity);
        $this->entityManager->flush();
        $this->entityManager->refresh($userEntity);
        $this->userId = $userEntity->getId();
        $count = $this->roleModel->getCountByRole($this->userArray['role']);
        $this->assertGreaterThan(0,$count);
    }

    /**
     * test getCountByRole with a not real id, should be 0
     */
    function testGetCountByRoleNotExist()
    {
        $role = 999999999;
        $count = $this->roleModel->getCountByRole($role);
        $this->assertEquals(0,$count);
    }

    /**
     * throws exception Role is required
     * @expectedException \Exception
     */

    function testGetCountByRoleBreaks()
    {
        $role = null;
        $count = $this->roleModel->getCountByRole($role);
        $this->assertEquals(0,$count);
    }


    private function removeEntities()
    {
        if (!empty($this->id)) {
            $this->roleModel->delete($this->id);
        }

        if (!empty($this->userId)) {
            $entity = $this->entityManager->getRepository('erdiko\users\entities\User')
                ->find($this->userId);
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }
    }

    function tearDown()
    {
        $this->removeEntities();
        $this->invalidateToken();
        unset(
        	$this->entityManager,
	        $this->roleModel,
	        $_SESSION
        );
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