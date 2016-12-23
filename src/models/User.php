<?php
/**
 * User Model
 *
 * @category    Erdiko
 * @package     User
 * @copyright   Copyright (c) 2016, Arroyo Labs, http://www.arroyolabs.com
 * @author      Leo Daidone, leo@arroyolabs.com
 */

namespace erdiko\users\models;

use \erdiko\users\entities\User as entity;
use \erdiko\authenticate\iErdikoUser;

class User implements iErdikoUser
{

	use \erdiko\doctrine\EntityTraits; // This adds some convenience methods like getRepository('entity_name')

	// @todo move salt to the entity?
	const PASSWORDSALT = "FOO"; // @todo add salt to config instead
	protected $_user;
	private $_em;

	public function __construct( $em = null ) {
		$this->_em = $em;
		if ( empty( $em ) ) {
			$this->_em = $this->getEntityManager();
		}
		$this->_user = self::createAnonymous();
	}

	public function setEntity($entity)
	{
	    if(!($entity instanceof  entity)){
            throw new \Exception('Parameter must be an entity User');
        }
		$this->_user = $entity;
	}

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
	public static function unmarshall( $encoded ) {
		$decode = json_decode( $encoded, true );
		if(empty($decode)){
			$entity = self::createAnonymous();
		} else {
			$entity = new entity();
			foreach ($decode as $key=>$value) {
				$key = str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
				$method = "set{$key}";
				$entity->$method($value);
			}
		}
		$model = new User();
		$model->setEntity($entity);
		return $model;
	}

	protected static function createAnonymous()
	{
	    $roleModel = new \erdiko\users\models\Role();
        $roleAnonymous = $roleModel->findByName('anonymous');
        if(empty($roleAnonymous)){
            throw  new \Exception('Error, role anonymous not found.');
        }

		$entity = new entity();
		$entity->setId( 0 );
		$entity->setRole( $roleAnonymous->getId() );
		$entity->setName( 'anonymous' );
		$entity->setEmail( 'anonymous' );
		return $entity;
	}

	public static function getAnonymous()
	{
		$user = new User();
		$entity = self::createAnonymous();
		$user->setEntity($entity);
		return $user;
	}

	public function marshall($type="json") {
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

	public function getDisplayName()
	{
		return $this->_user->getName();
	}
	/**
	 *
	 */
	public function createUser( $data = array() ) {
		if ( empty( $data ) ) {
			throw new \Exception( "User data is missing" );
		}

		if ( empty( $data['email'] ) || empty( $data['password'] ) ) {
			throw new \Exception( "email & password are required" );
		}

		try {

			if ( empty( $data['role'] ) ) {
                $roleModel = new \erdiko\users\models\Role();
                $roleAnonymous = $roleModel->findByName('anonymous');
                if(empty($roleAnonymous)){
                    throw  new \Exception('Error, role anonymous not found.');
                }
				$data['role'] = $roleAnonymous->getId();
			}

			$password = $this->getSalted($data['password']);

			$entity = new entity();
			$entity->setEmail($data['email']);
			$entity->setName($data['name']);
			$entity->setRole($data['role']);
			$entity->setPassword($password);

			$this->_em->persist($entity);
			$this->_em->flush();

			$this->setEntity($entity);
		} catch ( \Exception $e ) {
			throw new \Exception( $e->getMessage() );
		}

		return true;
	}

	/**
	 * getSalted
	 *
	 * returns password string concat'd with password salt
	 */
	public function getSalted( $password ) {
		$res =  $password . self::PASSWORDSALT;
        return $res;
	}


	/**
	 * authenticate
	 *
	 * attempt to validate the user by querying the DB for params
	 */
	public function authenticate( $email, $password ) {
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
			return $this;
		}
		
		return false;
	}

	/**
	 * @todo: should use "\erdiko\authenticate" sutff
	 *
	 * isLoggedIn
	 *
	 * returns true if the user is logged in
	 */
	public function isLoggedIn() {
        $roleModel = new \erdiko\users\models\Role();
        $roleAnonymous = $roleModel->findByName('anonymous');
        if(empty($roleAnonymous)){
            throw  new \Exception('Error, role anonymous not found.');
        }

		return ( ( $this->_user->getId() > 0 ) && ( $this->_user->getRole() !== $roleAnonymous->getId() ) );
	}

	/**
	 * isEmailUnique
	 *
	 * returns true if provided email was not found in the user table
	 */
	public function isEmailUnique( $email ) {
		$repo   = $this->getRepository( 'erdiko\users\entities\User' );
		$result = $repo->findBy( array( 'email' => $email ) );

		if ( empty( $result ) ) {
			$response = 0;
		} else {
			$response = (bool) ( count( $result ) == 0 );
		}

		return $response;
	}

	/**
	 * @return array
	 */
	public function getRoles() {
		return array( $this->_user->getRole() );
	}

	/**
	 * isAdmin
	 *
	 * returns true if current user's role is admin
	 */
	public function isAdmin() {
        return $this->hasRole('admin');
	}

	/**
	 * isAnonymous
	 *
	 * returns true if current user's role is anonymous
	 */
	public function isAnonymous()
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
	public function hasRole($role="anonymous")
	{
        $roleModel = new \erdiko\users\models\Role();
        $roleEntity = $roleModel->findByName($role);
        if(empty($roleEntity)){
            throw  new \Exception('Error, role anonymous not found.');
        }
        $result = $this->_user->getRole() == $roleEntity->getId();

		return $result;
	}

    public function getRole()
    {
        return  $this->_user->getRole();
    }

	/**
	 *
	 *
	 *
	 */
    public function getUsers($page = 0, $pagesize = 100, $sort = 'id', $direction = 'asc') 
    {
        $result = (Object)array(
            "users" =>  array(),
            "total" => 0
        );

        $repo = $this->getRepository('erdiko\users\entities\User');

        $offset = 0;
        if($page > 0) {
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
     *
     *
     *
     */
    public function getAdmins($page=0, $pagesize=100, $sort = 'id') {
        $roleModel = new \erdiko\users\models\Role();
        $roleAdmin = $roleModel->findByName('admin');
        if(empty($roleAdmin)){
            throw  new \Exception('Error, role admin not found.');
        }
        $repo   = $this->getRepository( 'erdiko\users\entities\User' );
        $offset = 0;
        if($page > 0) {
            $offset = $page * $pagesize;
        }
        $result = $repo->findBy(array('role'=>$roleAdmin->getId()),array($sort => 'asc'), $pagesize, $offset);

        return $result;
    }

	/**
	 * deleteUser
	 *
	 *
	 */
	public function deleteUser( $id ) {
		try {
			$_user = $this->_em->getRepository( 'erdiko\users\entities\User' )->findOneBy(array('id'=>$id));

			if ( ! is_null( $_user ) ) {
				$this->_em->remove($_user);
				$this->_em->flush();
				$this->_user = null;
				$_user = null;
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
	 *
	 */
	public function getUserId() {
		return $this->_user->getId();
	}

	/**
	 *
	 */
	public function save( $data ) {
		$data = (object) $data;
		$new  = false;
		if ( isset( $data->id ) ) {
			$entity = $this->getById( $data->id );
		} else {
			$entity = new entity();
			$new    = true;
		}
		if ( isset( $data->name ) ) {
			$entity->setName( $data->name );
		}
		if ( isset( $data->email ) ) {
			$entity->setEmail( $data->email );
		}
		if ( isset( $data->password ) ) {
			$entity->setPassword( $data->password );
		}
		if ( isset( $data->role ) ) {
			$entity->setRole( $data->role );
		}
		if ( isset( $data->gateway_customer_id ) ) {
			$entity->setGatewayCustomerId( $data->gateway_customer_id );
		}
		if ( $new ) {
			$this->_em->persist( $entity );
		} else {
			$this->_em->merge( $entity );
		}
		$this->_em->flush();
		$this->setEntity($entity);
		return $entity->getId();
	}

	/**
	 * getById
	 *
	 */
	public function getById( $id ) {
		$repo   = $this->getRepository( 'erdiko\users\entities\User' );
		$result = $repo->findOneBy( array( 'id' => $id ) );

		return $result;
	}

	/**
	 * getByParams
	 *
	 * @param $params
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getByParams($params)
	{
		try {
			//validate
			$obj    = new \erdiko\users\entities\User();
			$params = (array) $params;
			$filter = array();
			foreach ( $params as $key => $value ) {
				$method = "get" . ucfirst( $key );
				if ( method_exists( $obj, $method ) ) {
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
	public function getGatewayCustomerId( $uid ) {
		$result = 0;
		$user   = $this->findUser( $uid );
		if ( ! is_null( $user ) ) {
			$result = intval( $user->getGatewayCustomerId() );
		}

		return $result;
	}
}
