<?php

namespace erdiko\users\validators;

class RoleValidator implements erdiko\authorize\ValidatorInterface
{
	private static $_attributes = [
		'CAN_CREATE_ROLE',
		'CAN_DELETE_ROLE'
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
	public function validate($token, $attribute, $object=null)
	{
		$user = $token->getUser();
		if (!$user instanceof UserInterface) {
			return false;
		}
		$role = $user->getRole();
		switch ($attribute) {
			case 'CAN_CREATE_ROLE':
				$result = $role=='super_admin';
				break;
			case 'CAN_DELETE_ROLE':
				$result = $role=='super_admin';
				break;
			default:
				$result = false;
		}
		return $result;
	}
}