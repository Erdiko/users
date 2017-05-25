<?php

namespace erdiko\users\validators;

class UserValidator implements erdiko\authorize\ValidatorInterface
{
	private static $_attributes = [
		'CAN_CREATE_USER',
		'CAN_DELETE_USER',
		'CAN_SAVE_USER',
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
		$ownData = false;
		if(!empty($object)){
			$ownData = ($object->getUserId()==$user->getUserId());
		}
		$role = $user->getRole();
		switch ($attribute) {
			case 'CAN_CREATE_USER':
				$result = in_array($role,array('admin','super_admin'));
				break;
			case 'CAN_DELETE_USER':
				$result = $role=='super_admin';
				break;
			case 'CAN_SAVE_USER':
				$result = in_array($role,array('admin','super_admin')) || $ownData;
				break;
			default:
				$result = false;
		}
		return $result;
	}
}