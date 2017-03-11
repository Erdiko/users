<?php
/**
 * Role model test cases
 *
 * @category   UnitTests
 * @package    tests
 * @copyright  Copyright (c) 2016, Arroyo Labs, http://www.arroyolabs.com
 *
 * @author     Julian Diaz, julian@arroyolabs.com
 */

namespace tests\phpunit;

require_once dirname(__DIR__).'/ErdikoTestCase.php';

class RoleTest extends \tests\ErdikoTestCase
{
    protected $entityManager = null;
    protected $roleModel = null;
    protected $modelArray = null;
    protected $userArray = null;
    protected $userId = null;
    protected $id = null;

    function setUp()
    {
        $this->entityManager = \erdiko\doctrine\EntityManager::getEntityManager();
        $this->modelArray = array(
            'id'=>0,
            'active' => 1,
            'name' => 1,
        );
        $this->userArray = array(
            'email' => 'user+'.time().'@email.com',
            'password' => 'booyah_'.time(),
            'name' => 'user+'.time(),
            'role' => 1,
            'gateway_customer_id' => time()
        );
        $this->roleModel = new \erdiko\users\models\Role();
    }

    /**
     * test the Role is created.
     */
    function testCreate()
    {
        $this->id = $this->roleModel->create($this->modelArray);
        $this->assertGreaterThan(0, $this->id);
    }


    /**
     * test the findById is working. The entity should exist.
     */
    function testFindById()
    {
        $this->id = $this->roleModel->create($this->modelArray);
        $entity = $this->roleModel->findById($this->id);
        $this->assertNotNull($entity);
    }


    /**
     * test findById with an fake id. The entity should not exist.
     *
     */
    function testFindByNotExist()
    {
        $id = 999999999;
        $result = $this->roleModel->findById($id);
        $this->assertNull($result);
    }

    /**
     * @expectedException \Exception
     * test the findById method should brake if a null id is given.
     */

    function testFindByBreaks()
    {
        $id = null;
        $result = $this->roleModel->findById($id);
        $this->assertNull($result);
    }

    /**
     * test findByName with a real id given. Entity should exist.
     */
    function testFindByName()
    {
        $this->id = $this->roleModel->create($this->modelArray);
        $entity = $this->roleModel->findById($this->id);
        $entityfound = $this->roleModel->findByName($entity->getName());
        $this->assertNotNull($entity);
        $this->assertEquals($entity->getId(),$entityfound->getId());
    }

    /**
     * test save method a new entity should be created.
     */

    function testSaveNewOne()
    {
        $this->id = $this->roleModel->save($this->modelArray);
        $this->assertGreaterThan(0,$this->id);
    }

    /**
     * test save method, the entity exist then should update params.
     */
    function testSaveExistent()
    {
        $this->id = $this->roleModel->create($this->modelArray);
        $this->modelArray['id'] = $this->id;
        $new_id = $this->roleModel->save($this->modelArray);
        $this->assertEquals($new_id,$this->id);
    }

    /**
     * test getCountByRole the number of entities should not be 0
     */
    function testGetCountByRole()
    {
        $userEntity = new \erdiko\users\entities\User;
        $userEntity->setEmail($this->userArray['email']);
        $userEntity->setPassword($this->userArray['password']);
        $userEntity->setName($this->userArray['name']);
        $userEntity->setRole($this->userArray['role']);
        $userEntity->setGatewayCustomerId($this->userArray['gateway_customer_id']);

        // Save
        $this->entityManager->getRepository('erdiko\users\entities\User');
        $this->entityManager->persist($userEntity);
        $this->entityManager->flush();
        $this->entityManager->refresh($userEntity);
        $this->userId = $userEntity->getId();
        $count = $this->roleModel->getCountByRole($this->userArray['role']);
        $this->assertGreaterThan(0,$count);
    }

    /**
     * test getCountByRole with a not real id, should be 0
     */
    function testGetCountByRoleNotExist()
    {
        $role = 999999999;
        $count = $this->roleModel->getCountByRole($role);
        $this->assertEquals(0,$count);
    }

    /**
     * throws exception Role is required
     * @expectedException \Exception
     */

    function testGetCountByRoleBreaks()
    {
        $role = null;
        $count = $this->roleModel->getCountByRole($role);
        $this->assertEquals(0,$count);
    }


    private function removeEntities()
    {
        if (!empty($this->id)) {
            $this->roleModel->delete($this->id);
        }

        if (!empty($this->userId)) {
            $entity = $this->entityManager->getRepository('erdiko\users\entities\User')
                ->find($this->userId);
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }
    }

    function tearDown()
    {
        $this->removeEntities();
        unset($this->entityManager);
    }
}