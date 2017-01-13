<?php
/**
 * User entity
 *
 * Entity for the user table
 *
 * @category    Erdiko
 * @package     User
 * @copyright   Copyright (c) 2016, Arroyo Labs, http://www.arroyolabs.com
 * @author      Leo Daidone, leo@arroyolabs.com
 */
namespace erdiko\users\entities;

/**
 * @Entity @Table(name="users") @HasLifecycleCallbacks
 */
class User
{
	/**
	 * @Id @GeneratedValue @Column(type="integer")
	 * @var integer
	 */
	protected $id;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $email;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $password;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $role;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $gateway_customer_id;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $last_login;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $created_at;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $updated_at;

	// Kalinka compliant
	public function getRoles()
	{
		$array = array();
		array_push($array, $this->getRole());
		return $array;
	}

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public function getEmail()
	{
		return $this->email;
	}

	public function setEmail($email)
	{
		$this->email = $email;
	}

	public function getPassword()
	{
		return $this->password;
	}

	public function setPassword($password)
	{
		$this->password = md5($password);
	}

	public function getRole()
	{
		return $this->role;
	}

	public function setRole($role)
	{
		$this->role = $role;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getGatewayCustomerId()
	{
		return $this->gateway_customer_id;
	}

	public function setGatewayCustomerId($gateway_customer_id)
	{
		$this->gateway_customer_id = $gateway_customer_id;
	}

	public function getLastLogin()
	{
		return $this->last_login;
	}

	public function setLastLogin($last_login = null)
	{
		if(empty($last_login))
			$this->last_login = date('Y-m-d H:i:s');
		else
			$this->last_login = $last_login;
	}

	public function getCreatedAt()
	{
		return $this->created_at;
	}

	public function setCreatedAt($created)
	{
		$this->created_at = $created;
	}

	public function getUpdatedAt()
	{
		return $this->updated_at;
	}

	public function setUpdatedAt($updated)
	{
		$this->updated_at = $updated;
	}

	/**
	 * @PrePersist
	 */
	public function doStuffOnPrePersist()
	{
		$this->setCreatedAt(date('Y-m-d H:i:s'));
	}

	/**
	 * @PreUpdate
	 */
	public function doStuffOnPreMerge()
	{
		$this->setUpdatedAt(date('Y-m-d H:i:s'));
	}

	/**
	 * Marshalling
	 */

	public function marshall($type="json")
	{
		$tmp = $this;
		// unset sensitive fields
		unset($tmp->token);
		unset($tmp->password);
		unset($tmp->created_at);
		unset($tmp->updated_at);
		$out = array();
		foreach ($tmp as $field=>$value){
			$out[$field] = $value;
		}
		$response = null;
		switch ($type) {
			case "object":
				$response = (object)$out;
				break;
			case "array":
				$response = (array)$out;
				break;
			case "json":
			default:
				$response = json_encode($out);
		}
		return $response;
	}
}
