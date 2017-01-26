<?php
/**
 * user/event/Log entity
 *
 * Entity for the user_event_log table
 *
 * @category    Erdiko
 * @package     entities
 * @copyright   Copyright (c) 2017, Arroyo Labs, http://www.arroyolabs.com
 * @author      Julian Diaz, julian@arroyolabs.com
 */
namespace erdiko\users\entities\user\event;

/**
 * @Entity @Table(name="user_event_log") @HasLifecycleCallbacks
 */
class Log
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var integer
     */
    protected $id;

    /**
     * @Column(type="integer")
     * @var integer
     */
    protected $user_id;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $event_log;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $event_data;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $created_at;


    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function setUserId($userId)
    {
        $this->user_id = $userId;
    }

    public function getEventLog()
    {
        return $this->event_log;
    }

    public function setEventLog($eventLog)
    {
        $this->event_log = $eventLog;
    }

    public function getEventData()
    {
        return $this->event_data;
    }

    public function setEventData($eventData)
    {
        $this->event_data = $eventData;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function setCreatedAt($created)
    {
        $this->created_at = $created;
    }

    /**
     * @PrePersist
     */
    public function doStuffOnPrePersist()
    {
        $this->setCreatedAt(date('Y-m-d H:i:s'));
    }
}
