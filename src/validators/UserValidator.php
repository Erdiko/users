<?php

namespace erdiko\users\validators;

class UserValidator implements \erdiko\authorize\ValidatorInterface
{
	private static $_attributes = [
		'USER_CAN_CREATE',
		'USER_CAN_DELETE',
		'USER_CAN_SAVE',
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
		$user = $token->getUser();
		if (!$user instanceof \erdiko\authorize\UserInterface) {
			return false;
		}
		$ownData = false;
		if(!empty($object)){
			$ownData = ($object->getUserId()==$user->getUserId());
		}
		$role = $user->getRole();
		switch ($attribute) {
			case 'USER_CAN_CREATE':
				$result = in_array($role,array('admin','super_admin'));
				break;
			case 'USER_CAN_DELETE':
				$result = $role=='super_admin';
				break;
			case 'USER_CAN_SAVE':
				$result = in_array($role,array('admin','super_admin')) || $ownData;
				break;
			default:
				$result = false;
		}
		return $result;
	}
}