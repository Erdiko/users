<?php
/**
 *
 * @category    helper
 * @copyright   Copyright (c) 2017, Arroyo Labs, http://www.arroyolabs.com
 * @author      Leo Daidone, leo@arroyolabs.com
 */
namespace erdiko\users\helpers;

use erdiko\users\models\User;

class CommonHelper
{
	public static function getRoleName($roleCode)
	{
		$roleNme = $roleCode;
		if (is_numeric(intval($roleCode)) && (intval($roleCode)>0)) {
			$roleNme = self::lookupRole($roleCode)->getName();
		}

		return $roleNme;
	}

	public static function lookupRole($roleCode)
	{
		$roleModel  = new \erdiko\users\models\Role;
		$roleEntity = $roleModel->findById( $roleCode );
		$roleName    = $roleEntity;
		return $roleName;
	}

	public static function extractUser($token)
	{
		$user = null;
		$_user = $token->getUser();
		if(is_string($_user)){
			$user = self::lookupUser($_user);
		} elseif ($_user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
			$user = self::lookupUser($_user->getUsername());
		}
		return $user;
	}

	public static function lookupUser($email)
	{
		$uModel = new User();
		$user = $uModel->getByParams(array('email'=>$email));
		if(!empty($user) && is_array($user)){
			$user = array_shift($user);
		}
		return $user;
	}

}