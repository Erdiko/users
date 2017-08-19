<?php
/**
 * User Model
 * @todo should refactor and move some of the get methods into a user service class (e.g. getUsers())
 *
 * @package     erdiko/users/models
 * @copyright   Copyright (c) 2017, Arroyo Labs, http://www.arroyolabs.com
 * @author      Leo Daidone, leo@arroyolabs.com
 */

namespace erdiko\users\models;

use \erdiko\users\entities\User as entity;
use \erdiko\users\models\user\event\Log;
use \erdiko\authenticate\services\JWTAuthenticator;
use \erdiko\users\helpers\CommonHelper;
use \erdiko\users\models\user\UserProvider;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements
	\erdiko\authenticate\UserStorageInterface,
	\erdiko\authorize\UserInterface
{

	use \erdiko\doctrine\EntityTraits; // This adds some convenience methods like getRepository('entity_name')

	// @todo move salt to the entity?
	const PASSWORDSALT = "FOO"; // @todo add salt to config instead
	protected $_user;
	private $_em;

    /**
     *
     */
	public function __construct( $em = null )
    {
		$this->_em = $em;
		if (empty( $em )) {
			$this->_em = $this->getEntityManager();
		}
		try {
            $this->_user = self::createGeneral();
        } catch (\Exception $e) {
            throw new \Exception('Parameter must be an entity User');
        }
	}

    /**
     *
     */
	public function setEntity($entity)
	{
	    if (!($entity instanceof  entity)) {
            throw new \Exception('Parameter must be an entity User');
        }
		$this->_user = $entity;
	}

    /**
     *
     */
	public function getEntity()
	{
		return $this->_user;
	}

	/**
	 * iErdikoUser Interface inherited - start
	 */

	/**
	 * @param $encoded
	 *
	 * @return User
	 */
	public static function unmarshall($encoded)
    {
		$decode = json_decode( $encoded, true );
		if (empty($decode)) {
			$entity = self::createGeneral();
		} else {
			$entity = new entity();
			foreach ($decode as $key => $value) {
				$key = str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
				$method = "set{$key}";
				$entity->$method($value);
			}
		}
		$model = new User();
		$model->setEntity($entity);
		return $model;
	}

    /**
     * @return entity
     * @throws \Exception
     *
     * returns a new general user entity.
     */
	protected static function createGeneral()
	{
	    $roleModel = new \erdiko\users\models\Role;
        $roleGeneral = $roleModel->findByName('general');
        if (empty($roleGeneral)) {
            throw  new \Exception('Role general not found.');
        }

		$entity = new entity();
		$entity->setId( 0 );
		$entity->setRole( $roleGeneral->getId() );
		$entity->setName( 'user' );
		$entity->setEmail( 'user' );
		return $entity;
	}

	public static function getAnonymous()
	{
		return self::createGeneral();
	}

    /**
     * @return User
     *
     * returns a new User model with entity general
     */
	public static function getGeneral()
	{
		$user = new User();
		$entity = self::createGeneral();
		$user->setEntity($entity);
		return $user;
	}

    /**
     *
     */
	public function marshall($type="json")
    {
		$_user = $this->getEntity()->marshall($type);
		return $_user;
	}

	/**
	 * iErdikoUser Interface inherited - end
	 */
	public function getUsername()
	{
		return $this->_user->getName();
	}

    /**
     *
     */
	public function getDisplayName()
	{
		return $this->_user->getName();
	}

	/**
	 * getSalted
	 *
	 * returns password string concat'd with password salt
	 */
	public function getSalted($password)
    {
		$res =  $password . self::PASSWORDSALT;
        return $res;
	}


	/**
	 * authenticate
	 *
	 * attempt to validate the user by querying the DB for params
	 */
	public function authenticate($email, $password)
    {
		$pass = $password . self::PASSWORDSALT;
		$pwd = md5( $pass );
		// @todo: repository could change...
		$repo   = $this->getRepository( '\erdiko\users\entities\User' );
		$result = $repo->findOneBy( array( 'email' => $email, 'password' => $pwd ) );

		if (!empty($result)) {
		    //update last_login
            $result->setLastLogin();
            $this->_em->merge($result);
            $this->_em->flush();

			$this->setEntity( $result );
			$this->createUserEventLog(Log::EVENT_LOGIN, ['email' => $email]);

			return $this;
		}

        $this->createUserEventLog(Log::EVENT_ATTEMPT, ['email' => $email]);
		return false;
	}

	/**
	 * @todo update to use "\erdiko\authenticate" classes
	 *
	 * isLoggedIn
	 *
	 * returns true if the user is logged in
	 */
	public function isLoggedIn()
    {
        $roleModel = new \erdiko\users\models\Role;
        $roleGeneral = $roleModel->findByName('user');

		// @todo update exception message for clarity
        if (empty($roleGeneral)){
            throw  new \Exception('Error, role user not found.');
        }

		return ( ( $this->_user->getId() > 0 ) && ( $this->_user->getRole() !== $roleGeneral->getId() ) );
	}

	/**
	 * isEmailUnique
	 *
	 * returns true if provided email was not found in the user table
	 */
	public function isEmailUnique($email)
    {
		$repo   = $this->getRepository( 'erdiko\users\entities\User' );
		$result = $repo->findBy( array( 'email' => $email ) );

		if (empty($result)) {
			$response = 0;
		} else {
			$response = (bool) ( count( $result ) == 0 );
		}

		return $response;
	}

	/**
	 * @return array
     *
     * return the friendly user role names
	 */
	public function getRoles()
    {
        $roleModel = new \erdiko\users\models\Role;
        $roleEntity = $roleModel->findById($this->_user->getRole());
		return array( $roleEntity->getName());
	}

	/**
	 * isAdmin
	 *
	 * returns true if current user's role is admin
	 */
	public function isAdmin()
    {
        return $this->hasRole('admin');
	}

	/**
	 * isGeneral
	 *
	 * returns true if current user's role is general
	 */
	public function isGeneral()
	{
		return $this->hasRole();
	}

	/**
	 * hasRole
	 * returns true if current user has requested role
	 *
	 * @param string
	 *
	 * @return bool
	 */
	public function hasRole($role = "general")
	{
        $roleModel = new \erdiko\users\models\Role;
        $roleEntity = $roleModel->findByName($role);
        if (empty($roleEntity)) {
            throw  new \Exception("Error, role {$role} not found.");
        }
        $result = $this->_user->getRole() == $roleEntity->getId();

		return $result;
	}

    public function getRole()
    {
        return  $this->_user->getRole();
    }

    /**
     * @param int $page
     * @param int $pagesize
     * @param string $sort
     * @param string $direction
     * @return object
     *
     * return all the users paginated by parameters.
     */
    public function getUsers($page = 0, $pagesize = 100, $sort = 'id', $direction = 'asc')
    {
        $result = (Object)array(
            "users" =>  array(),
            "total" => 0
        );

        $repo = $this->getRepository('erdiko\users\entities\User');

        $offset = 0;
        if ($page > 0) {
            $offset = ($page - 1) * $pagesize;
        }

        $result->users = $repo->findBy(
                                        array(),
                                        array(
                                            $sort => $direction
                                        ),
                                        $pagesize,
                                        $offset
                                    );

        // get total users count
        $result->total = (int)$repo->createQueryBuilder('u')
                                   ->select('count(u.id)')
                                   ->getQuery()
                                   ->getSingleScalarResult();

		return $result;
	}


	/**
	 * deleteUser
	 *
	 *
	 */
	public function deleteUser($id)
    {
		try {
			$_user = $this->_em->getRepository( 'erdiko\users\entities\User' )->findOneBy(array('id'=>$id));

			if (! is_null($_user)) {
				$this->_em->remove($_user);
				$this->_em->flush();
				$this->_user = null;
				$_user = null;
				$this->createUserEventLog(Log::EVENT_DELETE, ['id' => $id]);
			} else {
				return false;
			}
		} catch ( \Exception $e ) {
			throw new \Exception( $e->getMessage() );
		}

		return true;
	}

	/**
	 * getUserId
	 *
	 * @return int $id
	 */
	public function getUserId()
    {
		return $this->getId();
	}

	/**
	 * getId
	 *
	 * @return int $id
	 */
	public function getId()
	{
		return $this->_user->getId();
	}

	/**
	 * Create user
     * @param array $data
     * @return int
     * @throws \Exception
	 *
	 * @todo deprecate this function
     *
     * create a new entity and set it to current user model.
     */
	public function createUser($data = array())
    {
		return $this->save($data);
	}

	protected function _getDefaultRole()
	{
		$roleModel = new \erdiko\users\models\Role();
		$roleGeneral = $roleModel->findByName('general');
		if (empty($roleGeneral)) {
			throw  new \Exception('Default role not found.');
		}

		return $roleGeneral->getId();
	}

	/**
	 * Update or create a new user
	 *
	 * @param $data
	 * @return int
	 * @throws \Exception
	 */
	public function save($data=array())
    {

		if (empty($data)) {
			throw new \Exception( "User data is missing" );
		}
        $data = (object) $data;

		if ((!isset($data->email) || empty($data->email))) {
			throw new \Exception( "Email is required" );
		}
		if ((!isset($data->password) || empty($data->password)) && !isset($data->id)) {
			throw new \Exception( "Password is required" );
		}

		$new  = false;
		if (isset($data->id)) {
			$entity = $this->getById($data->id);
		} else {
			$entity = new entity();
			$new    = true;
		}
		if (isset($data->name)) {
			$entity->setName($data->name);
		}
		if (isset($data->email)) {
			$entity->setEmail($data->email);
		}
		if (isset($data->password)) {
			$entity->setPassword($this->getSalted($data->password));
		} elseif (isset($data->new_password)){
			$entity->setPassword($this->getSalted($data->new_password));
		}
		if (empty($data->role)) {
			$data->role = $this->_getDefaultRole();
		}
		$entity->setRole($data->role);
		if (isset($data->gateway_customer_id)) {
			$entity->setGatewayCustomerId($data->gateway_customer_id);
		}

		if ($new) {
			$this->_em->persist($entity);
		} else {
			$this->_em->merge($entity);
		}

		// Save the entity
		try {
			$eventType = $new ? Log::EVENT_CREATE : Log::EVENT_UPDATE;
			if(isset($data->new_password)){
				$eventType = Log::EVENT_PASSWORD;
				unset($data->new_password);
			}
			unset($data->password);

			$this->createUserEventLog($eventType, $data);
			$this->_em->flush();
			$this->setEntity($entity);

			return $entity->getId();

		} catch ( \Doctrine\DBAL\Exception\UniqueConstraintViolationException $e ) {
			// \Erdiko::log(\Psr\Log\LogLevel::INFO, 'UniqueConstraintViolationException caught: '.$e->getMessage());
			throw new \Exception( "Can not create user with duplicate email" );
		}

		return null;
	}

    /**
     * @param $id
     * @return null|object
     *
     * return a user by id.
     */

	public function getById($id)
    {
		$repo   = $this->getRepository('erdiko\users\entities\User');
		$result = $repo->findOneBy(array('id' => $id ));

		return $result;
	}

	/**
	 * getByParams
	 *
	 * @param $params
	 * @return array
	 * @throws \Exception
     *
     * return users using params as query filter
	 */
	public function getByParams($params)
	{
		try {
			//validate
			$obj    = new \erdiko\users\entities\User;
			$params = (array) $params;
			$filter = array();
			foreach ($params as $key => $value) {
				$method = "get" . ucfirst( $key );
				if (method_exists($obj, $method)) {
					$filter[ $key ] = $value;
				}
			}
			$repo   = $this->getRepository( 'erdiko\users\entities\User' );
			$result = empty($filter)
				? $this->getUsers()
				: $repo->findBy( $filter );
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage());
		}
		return $result;
	}

	/**
	 * @param $uid
	 *
	 * @return int
	 */
	public function getGatewayCustomerId($uid)
    {
		$result = 0;
		$user   = $this->findUser( $uid );
		if (! is_null($user)) {
			$result = intval($user->getGatewayCustomerId());
		}

		return $result;
	}

	protected function createUserEventLog($eventType, $eventData)
    {
        if ($eventType == Log::EVENT_LOGIN || $eventType == Log::EVENT_ATTEMPT) {
            $users = $this->getByParams(['email' => $eventData['email']]);
            $userId = 0;
            if (count($users) >= 1) {
                $userId = $users[0] instanceof entity ? $users[0]->getId() : $users[0]->getUserId();
            }
            if ($eventType == Log::EVENT_ATTEMPT) {
                $eventData['message'] = !$userId ? "User {$eventData['email']} not found." : "Invalid Password";
            }
        }else {
            $auth = new JWTAuthenticator(new self());
            $user = $auth->currentUser();
            $userId = $user instanceof entity ? $user->getId() : $user->getUserId();
        }
        $logModel = new Log();
        $logModel->create($userId, $eventType, $eventData);
    }

}
