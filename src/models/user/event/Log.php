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

    public function getLogsById($id)
    {
        if(is_null($id)) {
            throw new \Exception('User ID is required.');
        } else {
            return $this->getRepository('\erdiko\users\entities\user\event\Log')->findBy(array('user_id'=>$id));
        }
    }


    public function create($user_id=null, $event_log=null, $event_data=null)
    {
        if(is_null($user_id)) throw new \Exception('User ID is required.');
        if(is_null($event_log)) throw new \Exception('Event Log is required.');
        if(!is_numeric($user_id)) throw new \Exception("Invalid User ID.");

        $this->save($this->generateEntity(intval($user_id), $event_log, $event_data));
    }

}