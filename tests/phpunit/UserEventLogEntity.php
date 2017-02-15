<?php
/**
 * Log Entity test cases
 * @category   UnitTests
 * @package    tests
 * @copyright  Copyright (c) 2016, Arroyo Labs, http://www.arroyolabs.com
 *
 * @author     Julian Diaz, julian@arroyolabs.com
 */

namespace tests\phpunit;

require_once dirname(__DIR__).'/ErdikoTestCase.php';

class UserEventLogEntity extends \tests\ErdikoTestCase
{
    protected $entityManager = null;
    protected $logArray = null;
    protected $updates = null;

    function setUp()
    {
        $this->entityManager = \erdiko\doctrine\EntityManager::getEntityManager();

        $this->logArray = array(
            'user_id'   => 1,
            'event_log' => 'eventLogTest',
            'event_data'=> 'eventDataTest'
        );
    }

    function tearDown()
    {
        unset($this->entityManager);
    }

    /**
     * @return int
     *
     * test the Role is created.
     */
    function testCreate()
    {
        $logEntity = new \erdiko\users\entities\user\event\Log();
        $logEntity->setUserId($this->logArray['user_id']);
        $logEntity->setEventLog($this->logArray['event_log']);
        $logEntity->setEventData($this->logArray['event_data']);
        // Save
        $this->entityManager->persist($logEntity);
        $this->entityManager->flush();

        $this->assertGreaterThan(0, $logEntity->getId());
        return $logEntity->getId();
    }

    /**
     * Read the recent insert and check the fields
     * @depends testCreate
     */
    public function testUpdate($id)
    {
        $oldEntity = $this->entityManager->getRepository('\erdiko\users\entities\user\event\Log')
                                         ->find($id);

        $this->updates = array(
            'user_id' => 2,
            'event_log' => 'eventLogUpdated',
            'event_data' => 'eventDataUpdated'.time(),
        );
        $oldEntity->setUserId($this->updates['user_id']);
        $oldEntity->setEventLog($this->updates['event_log']);
        $oldEntity->setEventData($this->updates['event_data']);
        // Save
        $this->entityManager->merge($oldEntity);
        $this->entityManager->flush();

        $this->assertGreaterThan(0, $oldEntity->getId());

        // get entity
        $updatedEntity = $this->entityManager->getRepository('\erdiko\users\entities\user\event\Log')
                                             ->find($id);

        $this->assertEquals($updatedEntity->getEventLog(), $this->updates['event_log']);
        $this->assertEquals($updatedEntity->getEventData(), $this->updates['event_data']);
        $this->assertEquals($updatedEntity->getUserId(), $this->updates['user_id']);

        return $updatedEntity->getId();
    }

    /**
     * Delete the Rol
     * @depends testUpdate
     */
    public function testDelete($id)
    {
        $entity = $this->entityManager
                        ->getRepository('\erdiko\users\entities\user\event\Log')
                        ->find($id);

        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
}
