<?php
/**
 * Role entity
 *
 * Entity for the Roles table
 *
 * @package     erdiko/users/entities
 * @copyright   Copyright (c) 2017, Arroyo Labs, http://www.arroyolabs.com
 * @author      Julian Diaz, julian@arroyolabs.com
 */
namespace erdiko\users\entities;

/**
 * @Entity @Table(name="roles") @HasLifecycleCallbacks
 */
class Role
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var integer
     */
    protected $id;

    /**
     * @Column(type="integer")
     * @var string
     */
    protected $active;

    /**
     * @Column(type="string")
     * @var integer
     */
    protected $name;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $created;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $updated;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getActive()
    {
        return $this->active;
    }

    public function setActive($active)
    {
        $this->active = $active;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = strtolower($name);
    }


    public function setCreated($created)
    {
        $this->created = $created;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * @PrePersist
     */
    public function doStuffOnPrePersist()
    {
        $this->setCreated(date('Y-m-d H:i:s'));
    }

    /**
     * @PreUpdate
     */
    public function doStuffOnPreMerge()
    {
        $this->setUpdated(date('Y-m-d H:i:s'));
    }
}