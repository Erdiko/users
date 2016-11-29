<?php
/**
 * User entity test cases
 *
 * @category   UnitTests
 * @package    tests
 * @copyright  Copyright (c) 2016, Arroyo Labs, http://www.arroyolabs.com
 *
 * @author     John Arroyo, john@arroyolabs.com
 * @author     Leo Daidone, leo@arroyolabs.com
 */
require_once dirname(__DIR__).'/ErdikoTestCase.php';


class UserEntityTest extends \tests\ErdikoTestCase
{
    protected $entityManager = null;
    protected $userArray = null;
    protected $id = null;

    function setUp()
    {
        $this->entityManager = \erdiko\doctrine\EntityManager::getEntityManager();
        $this->userArray = array(
            'email' => 'user+'.time().'@email.com',
            'password' => 'booyah_'.time(),
            'name' => 'user+'.time(),
            'role' => 'default',
            'gateway_customer_id' => time() 
            );
    }

    /**
     * @expectedException Doctrine\DBAL\Exception\NotNullConstraintViolationException
     */
    function testCreateFailNoEmail()
    {
        $userEntity = new erdiko\users\entities\User;
        $userEntity->setEmail($this->userArray['email']);

        // Save
        $this->entityManager->persist($userEntity);
        $this->entityManager->flush();
    }

    /**
     * @expectedException Doctrine\DBAL\Exception\NotNullConstraintViolationException
     */
    function testCreateFailNoPass()
    {
        $userEntity = new erdiko\users\entities\User;
        $userEntity->setPassword($this->userArray['password']);

        // Save
        $this->entityManager->persist($userEntity);
        $this->entityManager->flush();
    }

    function testCreate()
    {
        $userEntity = new erdiko\users\entities\User;
        $userEntity->setEmail($this->userArray['email']);
        $userEntity->setPassword($this->userArray['password']);
        $userEntity->setName($this->userArray['name']);
        $userEntity->setRole($this->userArray['role']);
        $userEntity->setGatewayCustomerId($this->userArray['gateway_customer_id']);

        // Save
        $this->entityManager->persist($userEntity);
        $this->entityManager->flush();

        $this->id = $userEntity->getId();
        $this->assertGreaterThan(0, $this->id);

        return $this->id;
    }

    /**
     * Read the recent insert and check the fields
     * @depends testCreate
     */
    public function testRead($id)
    {
        $entity = $this->entityManager->getRepository('erdiko\users\entities\User')
            ->find($id);
        $this->assertEquals($entity->getEmail(), $this->userArray['email']);
        $this->assertEquals($entity->getPassword(), md5($this->userArray['password']));
        $this->assertEquals($entity->getName(), $this->userArray['name']);
        $this->assertEquals($entity->getRole(), $this->userArray['role']);
        $this->assertEquals($entity->getGatewayCustomerId(), $this->userArray['gateway_customer_id']);

        $this->assertEmpty($entity->getLastLogin());
        $this->assertEmpty($entity->getUpdatedAt());
    
        return $id;
    }

    /**
     * Read the recent insert and check the fields
     * @depends testRead
     */
    public function testUpdate($id)
    {
        $entity = $this->entityManager->getRepository('erdiko\users\entities\User')
            ->find($id);
        $updates = array(
            'email' => 'user+'.time().'@update.com',
            'password' => microtime(),
            'role' => 'tester',
            'name' => 'bill+'.time(),
            'last_login' => date('Y-m-d H:i:s')
            );

        $entity->setEmail($updates['email']);
        $entity->setPassword($updates['password']);
        $entity->setName($updates['name']);
        $entity->setRole($updates['role']);
        $entity->setLastLogin($updates['last_login']);

        // Save
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        // get entity
        $entity = $this->entityManager->getRepository('erdiko\users\entities\User')
            ->find($id);

        $this->assertEquals($entity->getEmail(), $updates['email']);
        $this->assertEquals($entity->getPassword(), md5($updates['password']));
        $this->assertEquals($entity->getRole(), $updates['role']);
        $this->assertEquals($entity->getName(), $updates['name']);
        $this->assertEquals($entity->getLastLogin(), $updates['last_login']);

        return $id;
    }

    /**
     * Delete the user
     * @depends testUpdate
     */
    public function testDelete($id)
    {
        $entity = $this->entityManager->getRepository('erdiko\users\entities\User')
            ->find($id);

        // Delete
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
 
        $this->assertEmpty($entity->getId());

        // Attempt to read recently deleted record
        $entity = $this->entityManager
            ->getRepository('erdiko\users\entities\User')
            ->find($id);

        $this->assertEmpty($entity);
    }

    function tearDown() 
    {
        unset($this->entityManager);
    }
}