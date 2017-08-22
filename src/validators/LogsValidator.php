<?php

namespace erdiko\users\validators;

use erdiko\users\helpers\CommonHelper;

class LogsValidator implements \erdiko\authorize\ValidatorInterface
{
	const LOGS_CAN_LIST = 'LOGS_CAN_LIST';
	const LOGS_CAN_CREATE = 'LOGS_CAN_CREATE';
	const LOGS_CAN_FILTER = 'LOGS_CAN_FILTER';

	private static $_attributes = [
		self::LOGS_CAN_LIST,
		self::LOGS_CAN_CREATE,
		self::LOGS_CAN_FILTER
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

		$roleCode = (!empty($user) && is_callable(array($user,'getRole')))
			? $user->getRole()
			: -1;
		$role = CommonHelper::getRoleName($roleCode);

		$ownData = false;
		if(!empty($object)){
			$ownData = ($object->getUserId()==$user->getId());
		}

		switch ($attribute) {
			case 'LOGS_CAN_LIST':
				$result = in_array($role,array('admin','super_admin'));
				break;
			case 'LOGS_CAN_CREATE':
				$result = in_array($role,array('admin','super_admin')) || $ownData;
				break;
			case 'LOGS_CAN_FILTER':
				$result = in_array($role,array('admin','super_admin')) || $ownData;
				break;
			default:
				$result = false;
		}
		return $result;
	}
}