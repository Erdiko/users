<?php
/**
 * User Event Log test cases
 *
 * @category   UnitTests
 * @package    tests
 * @copyright  Copyright (c) 2017, Arroyo Labs, http://www.arroyolabs.com
 * @author     Julian Diaz, julian@arroyolabs.com
 */
require_once dirname(__DIR__).'/ErdikoTestCase.php';

class UserEventLogModelTest extends \tests\ErdikoTestCase
{

    protected $entityManager = null;
    protected $_logs = null;
    protected $id = null;

    /**
     *
     *
     */
    function setUp()
    {
        $this->entityManager = \erdiko\doctrine\EntityManager::getEntityManager();
        $this->_logs = new \erdiko\users\models\user\event\Log();
    }

    /**
     *
     *
     */
    function tearDown()
    {
        if ($this->id) {
            $entity = $this->entityManager->getRepository('\erdiko\users\entities\user\event\Log')
                ->find($this->id);
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }
    }

    /**
     * @expectedException \Exception
     */
    public function testCreateWithInvalidParams()
    {
        $this->_logs->create();
    }

    /**
     * @expectedException \Exception
     */
    public function testCreateWithInvalidUid()
    {
        $uid = "blah!";
        $type = 'login';
        $this->_logs->create($uid, $type);
    }

    /**
     * @expectedException \Exception
     */
    public function testCreateWithInvalidType()
    {
        $this->_logs->create(null);
    }

    /**
     * test the creation of one entity with the model
     */
    public function testCreate()
    {
        $uid = 1;
        $data = array('email'=>'test@mail.com');
        $this->_logs->create($uid, 'backend-test-profile-create', $data);
        $result = $this->_logs->getLogsById(1);

        $this->assertEquals(1,$result[0]->getUserId(),"Result has correct User ID.");

        $this->id = $result[0]->getId();
    }

    /**
     * @depends testCreate
     * @expectedException \Exception
     */
    public function testLogByIdNull()
    {
        $this->_logs->getLogsById(null);
    }

    /**
     * @depends testCreate
     */
    public function testLogByIdMissing()
    {
        $result  = $this->_logs->getLogsById(99999999999999);
        $this->assertEmpty($result);
    }

    /**
     *
     * @depends testLogByIdMissing
     */
    public function testGetAllLogs()
    {
        $uid = 1;
        $data = array('email'=>'test@mail.com');
        $this->_logs->create($uid, 'backend-test-profile-create', $data);
        $result = $this->_logs->getLogsById(1);
        $this->id = $result[0]->getId();

        $result = $this->_logs->getAllLogs();
        $this->assertTrue(is_array($result), "Returned value is an array");
        $this->assertInstanceOf('\erdiko\users\entities\user\event\Log', $result[0], 'Returned value is a \erdiko\users\entities\Log Object');
    }
}
