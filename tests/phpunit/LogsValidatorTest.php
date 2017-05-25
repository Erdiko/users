<?php
/**
 * Logs validator test cases
 *
 * @category   UnitTests
 * @package    tests
 * @copyright  Copyright (c) 2017, Arroyo Labs, http://www.arroyolabs.com
 *
 * @author     Leo Daidone, leo@arroyolabs.com
 */

namespace tests\phpunit;

require_once dirname(__DIR__).'/ErdikoTestCase.php';


class LogsValidatorTest extends \tests\ErdikoTestCase
{
	public function testSupportedAttributes()
	{
		$attribs = erdiko\users\validators\LogsValidator::supportedAttributes();
		$this->assertNotEmpty($attribs);
		$this->assertInternalType('array',$attribs);
	}

	public function testSupportsAttribute()
	{
		$userValidator = new erdiko\users\validators\LogsValidator();

		$this->assertTrue($userValidator->supportsAttribute('CAN_LIST_LOGS'));
		$this->assertTrue($userValidator->supportsAttribute('CAN_CREATE_LOGS'));
		$this->assertTrue($userValidator->supportsAttribute('CAN_FILTER_LOGS'));

		$this->assertFalse($userValidator->supportsAttribute('INVALID_ONE'));
	}
}