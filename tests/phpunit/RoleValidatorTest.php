<?php
/**
 * Role validator test cases
 *
 * @category   UnitTests
 * @package    tests
 * @copyright  Copyright (c) 2017, Arroyo Labs, http://www.arroyolabs.com
 *
 * @author     Leo Daidone, leo@arroyolabs.com
 */

namespace tests\phpunit;

require_once dirname(__DIR__).'/ErdikoTestCase.php';


class RoleValidatorTest extends \tests\ErdikoTestCase
{

	public function testSupportedAttributes()
	{
		$attribs = erdiko\users\validators\RoleValidator::supportedAttributes();
		$this->assertNotEmpty($attribs);
		$this->assertInternalType('array',$attribs);
	}

	public function testSupportsAttribute()
	{
		$userValidator = new erdiko\users\validators\RoleValidator();

		$this->assertTrue($userValidator->supportsAttribute('CAN_CREATE_ROLE'));
		$this->assertTrue($userValidator->supportsAttribute('CAN_DELETE_ROLE'));

		$this->assertFalse($userValidator->supportsAttribute('INVALID_ONE'));
	}
}