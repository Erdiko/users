<?php
/**
 *
 * @category    helper
 * @copyright   Copyright (c) 2017, Arroyo Labs, http://www.arroyolabs.com
 * @author      Leo Daidone, leo@arroyolabs.com
 */
namespace erdiko\users\helpers;

class CommonHelper
{
	public static function getRoleName($roleCode)
	{
		$roleModel = new \erdiko\users\models\Role;
		$roleEntity = $roleModel->findById($roleCode);
		return $roleEntity->getName();
	}

	public static function extractUser($token)
	{
		$user = null;
		$_user = $token->getUser();
		if(is_string($_user)){
			$uModel = new User();
			$user = $uModel->getByParams(array('email'=>$_user));
			if(!empty($user) && is_array($user)){
				$user = array_shift($user);
			}
		}
		return $user;
	}

}