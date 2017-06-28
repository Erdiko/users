<?php

namespace erdiko\users\validators;

use erdiko\users\helpers\CommonHelper;

class RoleValidator implements \erdiko\authorize\ValidatorInterface
{
	private static $_attributes = [
		'ROLE_CAN_CREATE',
		'ROLE_CAN_DELETE'
	];


	/**
	 * Should return array of supported attributes as uppercase strings
	 *
	 * @return array of strings
	 */
	public static function supportedAttributes()
	{
		return self::$_attributes;
	}

	/**
	 * Validate if $attribute is supported by this validator
	 *
	 * @param $attribute
	 * @return bool
	 */
	public function supportsAttribute($attribute)
	{
		return in_array($attribute, self::supportedAttributes());
	}

	/**
	 * @param $token
	 * @return bool
	 */
	public function validate($token, $attribute='', $object=null)
	{
		$user = CommonHelper::extractUser($token);
		$roleCode = -1;
		if(!empty($user)){
			if(is_callable(array($user,'getRole'))){
				$roleCode = $user->getRole();
				$role = CommonHelper::getRoleName($roleCode);
			} elseif (is_callable(array($user,'getRoles'))){
				$roleCode = $user->getRoles();

			}
		}
		if(is_array($roleCode)) {
			foreach ($roleCode as $code) {
				$role = CommonHelper::getRoleName($code);
			}
		} else {
			$role = CommonHelper::getRoleName($roleCode);
		}

		switch ($attribute) {
			case 'ROLE_CAN_CREATE':
				$result = $role=='super_admin';
				break;
			case 'ROLE_CAN_DELETE':
				$result = $role=='super_admin';
				break;
			default:
				$result = false;
		}
		return $result;
	}
}