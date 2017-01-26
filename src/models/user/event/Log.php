<?php
/**
 * Log Model
 *
 * @category    app
 * @package     app\models
 * @copyright   Copyright (c) 2017, Arroyo Labs, http://www.arroyolabs.com
 * @author      Julian Diaz, julian@arroyolabs.com
 */

namespace erdiko\users\models\user\event;

class Log
{
    use \erdiko\doctrine\EntityTraits; // This adds some convenience methods like getRepository('entity_name')

    private $_em;

    public function __construct()
    {
        $this->_em    =  $this->getEntityManager();
    }


    protected function save($logEntity)
    {
        $this->_em->persist($logEntity);
        $this->_em->flush();
        return $logEntity->getId();
    }

    protected function generateEntity($uid, $event_log, $event_data = null)
    {
        $entity = new \erdiko\users\entities\user\event\Log();
        $entity->setUserId($uid);
        $entity->setEventLog($event_log);
        $entity->setEventData($event_data);


        // only record event data if value is passed
        if(!empty($event_data)) {
            $entity->setEventData(serialize($event_data));
        }

        // make sure we set a created at TS, else we get weird results!
        $entity->setCreatedAt(date('Y-m-d H:i:s'));

        return $entity;
    }

    // general log stuff
    public function getAllLogs()
    {
        return $this->getRepository('\erdiko\users\entities\user\event\Log')->findAll();
    }


    /**
     * @param int $page
     * @param int $pagesize
     * @param string $sort
     * @param string $direction
     * @return object
     *
     * return all the logs entries paginated by parameters.
     */
    public function getLogsByUserId($id, $page = 0, $pagesize = 100, $sort = 'id', $direction = 'asc')
    {
        if(is_null($id)) {
            throw new \Exception('User ID is required.');
        }

        $result = (Object)array(
            "logs" =>  array(),
            "total" => 0
        );

        $repo = $this->getRepository('\erdiko\users\entities\user\event\Log');

        $offset = 0;
        if ($page > 0) {
            $offset = ($page - 1) * $pagesize;
        }

        $result->logs = $repo->findBy(
            array("user_id" => $id),
            array(
                $sort => $direction
            ),
            $pagesize,
            $offset
        );

        // get total log count
        $result->total = (int)$repo->createQueryBuilder('u')
            ->select('count(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $result;
    }


    /**
     * @param $id
     * @return null|object
     * @throws \Exception
     *
     * return a Log entity by id
     */
    public function findById($id)
    {
        if( is_null($id)) {
            throw new \Exception('ID is required');
        }

        try {
            $log = $this->getRepository('\erdiko\users\entities\user\event\Log');
            $result = $log->find($id);
        }catch (\Exception $e) {
            \error_log($e->getMessage());
        }
        return $result;
    }


    public function create($user_id=null, $event_log=null, $event_data=null)
    {
        if(is_null($user_id)) throw new \Exception('User ID is required.');
        if(is_null($event_log)) throw new \Exception('Event Log is required.');
        if(!is_numeric($user_id)) throw new \Exception("Invalid User ID.");

        $id = $this->save($this->generateEntity(intval($user_id), $event_log, $event_data));
        return $id;
    }

}