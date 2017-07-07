<?php

namespace erdiko\users\validators;

use erdiko\users\helpers\CommonHelper;

class UserValidator implements \erdiko\authorize\ValidatorInterface
{

    const USER_CAN_LIST = 'USER_CAN_LIST';
    const USER_CAN_RETRIEVE = 'USER_CAN_RETRIEVE';
    const USER_CAN_CREATE = 'USER_CAN_CREATE';
    const USER_CAN_DELETE = 'USER_CAN_DELETE';
    const USER_CAN_SAVE = 'USER_CAN_SAVE';

	private static $_attributes = [
		self::USER_CAN_LIST,
		self::USER_CAN_RETRIEVE,
		self::USER_CAN_CREATE,
		self::USER_CAN_DELETE,
		self::USER_CAN_SAVE,
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

		$ownData = false;
		if(!empty($object) && is_callable(array($object,'getId'))){
			$ownData = ($object->getId()==$user->getId());
		}

		switch ($attribute) {
			case 'USER_CAN_LIST':
				$result = in_array($role,array('general','admin','super_admin'));
				break;
			case 'USER_CAN_RETRIEVE':
				$result = in_array($role,array('general','admin','super_admin'));
				break;
			case 'USER_CAN_CREATE':
				$result = in_array($role,array('admin','super_admin'));
				break;
			case 'USER_CAN_DELETE':
				$result = in_array($role,array('admin','super_admin'));
				break;
			case 'USER_CAN_SAVE':
				$result = in_array($role,array('admin','super_admin')) || $ownData;
				break;
			default:
				$result = false;
		}
		// error_log( "result: ".(int)$result );

		return $result;
	}
}
