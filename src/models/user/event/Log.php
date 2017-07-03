<?php
/**
 * Log Model
 *
 * @category    app
 * @package     app\models
 * @copyright   Copyright (c) 2017, Arroyo Labs, http://www.arroyolabs.com
 * @author      Julian Diaz, julian@arroyolabs.com
 * @author      John Arroyo, john@arroyolabs.com
 */

namespace erdiko\users\models\user\event;

use \erdiko\users\models\user\UserProvider;

class Log
{
    use \erdiko\doctrine\EntityTraits; // This adds some convenience methods like getRepository('entity_name')

    const EVENT_LOGIN = 'login';
    const EVENT_ATTEMPT = 'login-attempt';
    const EVENT_LOGOUT = 'logout';
    const EVENT_CREATE = 'create';
    const EVENT_DELETE = 'delete';
    const EVENT_UPDATE = 'update';
    const EVENT_PASSWORD = 'update-password';

    private $_em;
	protected $authorizer;

    public function __construct()
    {
        $this->_em    =  $this->getEntityManager();
	    // Authorize
	    $provider = new UserProvider();
	    $authManager = new \erdiko\authenticate\AuthenticationManager($provider);
	    $this->authorizer = new \erdiko\authorize\Authorizer($authManager);
    }


    protected function save($logEntity)
    {
        //@TODO: reevaluate next block, it does not make sense as it is. Leo.
//	    if(!$this->authorizer->can('LOGS_CAN_CREATE', $logEntity)){
//		    throw new \Exception('You are not allowed');
//	    }
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
            $entity->setEventData($event_data);
        }

        // make sure we set a created at TS, else we get weird results!
        $entity->setCreatedAt(date('Y-m-d H:i:s'));

        return $entity;
    }

    // general log stuff
    public function getAllLogs()
    {
        //@TODO: reevaluate next block, it does not make sense as it is. Leo.
//	    if(!$this->authorizer->can('LOGS_CAN_LIST')){
//		    throw new \Exception('You are not allowed');
//	    }
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
    public function getLogs($page = 0, $pagesize = 100, $sort = 'id', $direction = 'asc')
    {
        //@TODO: reevaluate next block, it does not make sense as it is. Leo.
//	    if(!$this->authorizer->can('LOGS_CAN_LIST')){
//		    throw new \Exception('You are not allowed');
//	    }
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
            array(),
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
            ->where('u.user_id = :user_id')
            ->setParameter("user_id", $id)
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
    	// @todo: reevaluate next block, it does not make sense as it is. Leo.
	    /*if(!$this->authorizer->can('LOGS_CAN_FILTER')){
		    throw new \Exception('You are not allowed');
	    }*/
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

    /**
     * Create user log entry
     * @param int $userId
     * @param string $eventLog
     * @param string $eventData
     * @return int $id
     */
    public function create($userId=null, $eventLog=null, $eventData=null)
    {
        if(is_null($userId)) throw new \Exception('User ID is required.');
        if(is_null($eventLog)) throw new \Exception('Event Log is required.');
        if(!is_numeric($userId)) throw new \Exception("Invalid User ID.");

        $id = $this->save($this->generateEntity(intval($userId), $eventLog, $eventData));
        return $id;
    }

}
