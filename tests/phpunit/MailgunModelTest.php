<?php
/**
 * Mailgun model test cases
 *
 * @category   UnitTests
 * @package    app
 * @copyright  Copyright (c) 2016, Arroyo Labs, http://www.arroyolabs.com
 *
 * @author     Julian Diaz, julian@arroyolabs.com
 */

namespace tests\phpunit;
require_once dirname(__DIR__).'/ErdikoTestCase.php';

class MailgunTest extends \tests\ErdikoTestCase
{
    protected $mailgunModel = null;
    protected $dataTest = null;

    function setUp()
    {
        $this->mailgunModel = new \erdiko\users\models\Mailgun();
        $this->dataTest = (object) array(
                                        'from'    => "User Test <test@arroyolabs.com>",
                                        'to'      => "User Test <test@arroyolabs.com>",
                                        'cc'      => '',
                                        'bcc'     => '',
                                        'subject' => 'Arroyo Labs',
                                        'text'    => '',
                                        'html'    => 'test text email',
                                        'pass'    => 'asdf1234'
        );
    }

    function tearDown(){
        unset($this->mailgunModel);
        unset($this->dataTest);
    }

    function testForgotPassword(){
        $result = $this->mailgunModel->forgotPassword($this->dataTest->to,
                                            $this->dataTest->pass,
                                            $this->dataTest->html
        );
        $this->assertEquals("200", $result->http_response_code);
        $this->assertEquals("Queued. Thank you.", $result->http_response_body->message);
        $this->assertNotNull($result->http_response_body->id);
    }

    /**
     * @expectedException \Exception
     */
    function testForgotPasswordFail(){
        $this->dataTest->to = null;
        $this->mailgunModel->forgotPassword($this->dataTest->to,
            $this->dataTest->pass,
            $this->dataTest->html
        );
    }

    function testSendMail(){
        $result = $this->mailgunModel->sendMail($this->dataTest);
        $this->assertEquals("200", $result->http_response_code);
        $this->assertEquals("Queued. Thank you.", $result->http_response_body->message);
        $this->assertNotNull($result->http_response_body->id);
    }

    /**
     * @expectedException \Exception
     */
    function testSendMailFail(){
        $this->dataTest->to = null;
        $this->mailgunModel->sendMail($this->dataTest);
    }

}
?>