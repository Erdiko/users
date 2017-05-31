<?php
/**
 * User validator test cases
 *
 * @category   UnitTests
 * @package    tests
 * @copyright  Copyright (c) 2017, Arroyo Labs, http://www.arroyolabs.com
 *
 * @author     Leo Daidone, leo@arroyolabs.com
 */

namespace tests\phpunit;

require_once dirname(__DIR__).'/ErdikoTestCase.php';


class UserValidatorTest extends \tests\ErdikoTestCase
{

	public function testSupportedAttributes()
	{
		$attribs = erdiko\users\validators\UserValidator::supportedAttributes();
		$this->assertNotEmpty($attribs);
		$this->assertInternalType('array',$attribs);
	}

	public function testSupportsAttribute()
	{
		$userValidator = new erdiko\users\validators\UserValidator();

		$this->assertTrue($userValidator->supportsAttribute('USER_CAN_CREATE'));
		$this->assertTrue($userValidator->supportsAttribute('USER_CAN_DELETE'));
		$this->assertTrue($userValidator->supportsAttribute('USER_CAN_SAVE'));
		$this->assertFalse($userValidator->supportsAttribute('INVALID_ONE'));
	}
}