<?php

namespace erdiko\users\validators;

class LogsValidator implements erdiko\authorize\ValidatorInterface
{
	private static $_attributes = [
		'CAN_LIST_LOGS',
		'CAN_CREATE_LOGS',
		'CAN_FILTER_LOGS'
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
			case 'CAN_LIST_LOGS':
				$result = in_array($role,array('admin','super_admin'));
				break;
			case 'CAN_CREATE_LOGS':
				$result = in_array($role,array('admin','super_admin')) || $ownData;
				break;
			case 'CAN_FILTER_LOGS':
				$result = in_array($role,array('admin','super_admin')) || $ownData;
				break;
			default:
				$result = false;
		}
		return $result;
	}
}