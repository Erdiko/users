<?php
/**
 * Rol entity test cases
 *
 * @category   UnitTests
 * @package    app
 * @copyright  Copyright (c) 2016, Arroyo Labs, http://www.arroyolabs.com
 *
 * @author     Julian Diaz, julian@arroyolabs.com
 */
namespace tests\phpunit;

require_once dirname(__DIR__).'/ErdikoTestCase.php';
class RoleEntityTest extends \tests\ErdikoTestCase
{
    protected $entityManager = null;
    protected $rolArray = null;
    protected $updates = null;

    function setUp()
    {
        $this->entityManager = \erdiko\doctrine\EntityManager::getEntityManager();

        $this->rolArray = array(
          'active' => 1,
          'name'            => 'RoleName'.time(),
        );
    }

    function tearDown()
    {
        unset($this->entityManager);
    }

    function testCreate()
    {
        $rolEntity = \erdiko\users\entities\Role();
        $rolEntity->setActive($this->rolArray['active']);
        $rolEntity->setName($this->rolArray['name']);
        // Save
        $this->entityManager->persist($rolEntity);
        $this->entityManager->flush();

        $this->assertGreaterThan(0, $rolEntity->getId());
        return $rolEntity->getId();
    }

    /**
     * Read the recent insert and check the fields
     * @depends testCreate
     */
    public function testUpdate($id)
    {
        $oldEntity = $this->entityManager->getRepository('\erdiko\users\entities\Role')
                                         ->find($id);

        $this->updates = array(
            'active' => 0,
            'name' => 'roldupdated'.time(),
        );
        $oldEntity->setActive($this->updates['active']);
        $oldEntity->setName($this->updates['name']);
        // Save
        $this->entityManager->merge($oldEntity);
        $this->entityManager->flush();

        $this->assertGreaterThan(0, $oldEntity->getId());

        // get entity
        $updatedEntity = $this->entityManager->getRepository('\erdiko\users\entities\Role')
                                             ->find($id);

        $this->assertEquals($updatedEntity->getName(), $this->updates['name']);
        $this->assertEquals($updatedEntity->getActive(), $this->updates['active']);

        return $updatedEntity->getId();
    }

    /**
     * Delete the Rol
     * @depends testUpdate
     */
    public function testDelete($id)
    {
        $entity = $this->entityManager
                        ->getRepository('\erdiko\users\entities\Role')
                        ->find($id);

        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
}
