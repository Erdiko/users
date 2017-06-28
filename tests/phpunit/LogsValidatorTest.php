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
	private $logsValidator;
	private $attribs = [
		'LOGS_CAN_LIST',
		'LOGS_CAN_CREATE',
		'LOGS_CAN_FILTER'
	];

	function setUp()
	{
		$this->logsValidator = new \erdiko\users\validators\LogsValidator();
	}

	function tearDown()
	{
		unset($this->logsValidator);
	}

	public function testSupportedAttributes()
	{
		$attribs = \erdiko\users\validators\LogsValidator::supportedAttributes();
		$this->assertNotEmpty($attribs);
		$this->assertInternalType('array',$attribs);
	}

	public function testSupportsAttribute()
	{
		foreach ($this->attribs as $item) {
			$this->assertTrue($this->logsValidator->supportsAttribute($item));
		}

		$this->assertFalse($this->logsValidator->supportsAttribute('INVALID_ONE'));
	}

	public function testValidate()
	{

		//$this->logsValidator->validate($token, 'LOGS_CAN_LIST', $object=null);
	}
}