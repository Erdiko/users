<?php
/**
 * Role Model
 *
 * @package     erdiko/users/models
 * @copyright   Copyright (c) 2017, Arroyo Labs, http://www.arroyolabs.com
 * @author      Julian Diaz, julian@arroyolabs.com
 */

namespace erdiko\users\models;

class Role
{
    use \erdiko\doctrine\EntityTraits; // This adds some convenience methods like getRepository('entity_name')

    private $_em;

    public function __construct()
    {
        $this->_em    =  $this->getEntityManager();
    }


    /**
     * @param $data
     * @return int
     * @throws \Exception
     *
     * Create a new rol
     */
    public function create($data)
    {

        $data = is_object($data) ? $data : (object)$data;
        $id=0;
        try {
            $entity = new \erdiko\users\entities\Role();
            $entity->setName($data->name);
            $entity->setActive($data->active);

            $this->_em->persist($entity);
            $this->_em->flush();

            $id = intval($entity->getId());
        } catch (\Exception $e) {
            \error_log($e->getMessage());
            throw new \Exception("Could not create Role.");
        }
        return (int)$id;
    }

    /**
     * @param $id
     * @return null|object
     * @throws \Exception
     *
     * return a Role entity by id
     */
    public function findById($id)
    {
        if( is_null($id)) {
            throw new \Exception('ID is required');
        }

        try {
            $role = $this->getRepository('\erdiko\users\entities\Role');
            $result = $role->find($id);
        }catch (\Exception $e) {
            \error_log($e->getMessage());
        }
        return $result;
    }

    /**
     * @param $name
     * @return null|object
     * @throws \Exception
     *
     * return a Role entity with a name given
     */
    public function findByName($name)
    {
        if (is_null($name)) {
            throw new \Exception('name is required');
        }

        $result = null;
        try {
            $role = $this->getRepository('\erdiko\users\entities\Role');
            $result = $role->findOneBy(array('name' => $name));
        } catch (\Exception $e) {
            \error_log($e->getMessage());
        }
        return $result;
    }

    /**
     * findByStatus: by default returns active roles.
     *
     * @param int $active
     * @return mixed
     */
    public function findByStatus($active=1)
    {
        $result = null;
        try {
            $role = $this->getRepository('\erdiko\users\entities\Role');
            $result = $role->findBy(array('active' => $active));
        } catch (\Exception $e) {
            \error_log($e->getMessage());
        }
        return $result;
    }

    /**
     * @param $role
     * @return int
     * @throws \Exception
     *
     * Return the count users with a  given id Role
     */
    public function getCountByRole($role)
    {
        if (is_null($role)) {
            throw new \Exception('Role is required');
        }

        $result = 0;
        try {
            $users  = $this->_em->getRepository('\erdiko\users\entities\User')
                           ->findBy(array('role' => $role));
            $result  = count($users);

        }catch (\Exception $e) {
            \error_log($e->getMessage());
        }
        return $result;
    }

    /**
     * @param $id
     * @return array
     * @throws \Exception
     *
     * Return users with a given Role id
     */
    public function getUsersForRole($id)
    {
        if (is_null($id)) {
            throw new \Exception('name is required');
        }

        $result = array();
        try {
            $role = $this->findById($id);
            if(empty($role)) throw new \Exception('Role is not found');
            $users  = $this->_em->getRepository('\erdiko\users\entities\User')
                                ->findBy(array('role' => $role->getName()));
            $result  = $users;
        } catch (\Exception $e) {
            \error_log($e->getMessage());
            throw  $e;
        }
        return $result;
    }

    /**
     * @param null $data
     * @return int
     * @throws \Exception
     *
     * save/update Role attributes. If no id is passed, save a new Role.
     */
    public function save($data=null)
    {
        if ( is_null($data)) {
            throw new \Exception('There is no data to save.');
        }

        $filter = array();
        if (isset($data['id'])) {
            $filter['id'] = $data['id'];
        }

        try {

            // check if exists record
            $entity = $this->getEntity($filter);
            if (!empty($filter['id']) && empty($entity)) {
                throw new \Exception("Role not found.");
            }
            $entity->setActive($data['active']);
            $entity->setName($data['name']);

            $_id = $entity->getId();
            if (isset($_id) && ($_id > 0)) {
                $this->_em->merge($entity);
            } else {
                $this->_em->persist($entity);
            }
            $this->_em->flush();
            $_id = $entity->getId();
        } catch (\Exception $e){
            \error_log($e->getMessage());
            throw new \Exception($e->getMessage());
        }
        return $_id;
    }


    /**
     * @param $filter
     * @return \erdiko\users\entities\Role|null|object
     *
     * returns a entity Role or a new one.
     */
    private function getEntity($filter)
    {
        $roles = $this->getRepository('\erdiko\users\entities\Role');

        if (isset($filter['id']) && $filter['id'] > 0) {
            $result = $roles->find($filter['id']);
        } else {
            $result = new \erdiko\users\entities\Role();
        }
        return $result;
    }


    /**
     *
     * delete Entity with given ID
     */

    public function delete($id)
    {
        if (empty($id)) {
            throw new \Exception('There is no data to save.');
        }
        try{
            $entity = $this->_em->getRepository('\erdiko\users\entities\Role')
                                ->find($id);
            $this->_em->remove($entity);
            $this->_em->flush();
            return $id;
        } catch (\Exception $e){
            \error_log($e->getMessage());
            throw new \Exception("Could not delete Role.");
        }
    }
}