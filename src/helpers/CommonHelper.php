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

	public static function verifyHash()
	{
		$sapi = php_sapi_name();
		if(!self::contains('cli', $sapi)){
			self::startSession();
		} else {
			global $_SESSION;
		}
		$result = false;
		if(array_key_exists('setup_hash',$_SESSION)){
			$hash = $_SESSION['setup_hash'];
			if(!strpos($hash, ':')) return false;
			list ($expire, $rawhash) = explode(':', $hash, 2);
			$testhash = hash_hmac('sha1', 'erdiko_users_setup', $expire);
			if ($expire > time() && $testhash == $rawhash) {
				$result = true;
			}
		}
		return $result;
	}

	public static function startSession()
	{
		if (version_compare(phpversion(), '5.4.0', '<')) {
			if(session_id() == '') {
				@session_start();
			}
		}
		else
		{
			if (session_status() == PHP_SESSION_NONE) {
				@session_start();
			}
		}
	}

	public static function contains($needle, $haystack)
	{
		return strpos($haystack, $needle) !== false;
	}
}