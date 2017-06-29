<?php


/**
 * UserProvider
 *
 * @package     erdiko/users/models
 * @copyright   Copyright (c) 2017, Arroyo Labs, http://www.arroyolabs.com
 * @author      Leo Daidone, leo@arroyolabs.com
 */


namespace erdiko\users\models\user;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProvider implements \Symfony\Component\Security\Core\User\UserProviderInterface
{
	use \erdiko\doctrine\EntityTraits; // This adds some convenience methods like getRepository('entity_name')

	const PASSWORDSALT = "FOO"; // @todo add salt to config instead
	private $_em;

	public function __construct( $em = null ) {
		$this->_em = $em;
		if (empty( $em )) {
			$this->_em = $this->getEntityManager();
		}
	}

	public function loadUserByUsername( $username ) {
		$repo   = $this->getRepository( '\erdiko\users\entities\User' );
		$user = $repo->findOneBy( array( 'email' => $username) );
		if(empty($user)){
			throw new UsernameNotFoundException();
		}
		return $user->getEntity();
	}

	public function refreshUser( UserInterface $user ) {
		// TODO: Implement refreshUser() method.
	}

	public function supportsClass( $class ) {
		return $class === '\erdiko\users\entities\User';
	}

}