<?php
/**
 * Ajax controller
 *
 * @category    app
 * @package     Example
 * @copyright   Copyright (c) 2014, Arroyo Labs, www.arroyolabs.com
 * @author     Julian Diaz, julian@arroyolabs.com
 */
namespace erdiko\users\controllers;

use erdiko\authenticate\BasicAuth;
use erdiko\authenticate\iErdikoUser;
use erdiko\authorize\Authorizer;
use erdiko\users\models\User;

/**
 * AjaxExample Class
 */
class Ajax extends \erdiko\core\AjaxController
{

  public function _after()
  {
    //@TODO we need to add some basic ACL checks to make sure the user is logged in
    header("Access-Control-Allow-Origin: *");
  }

  /**
   * Get
   */
  public function get($var = null)
  {
    if ($var != null) {
    // load action
        return $this->_autoaction($var);
    }
    $this->setContent($view);
  }

  /**
   * Post
   */
  public function post($var = null) 
  {
    if ($var != null) {
      // load action
      return $this->_autoaction($var, 'post');
    }
    $this->setContent($view);
  }


 /**
  *
  * return roles with properties: id, users count, active, name.
  */
 public function getRoles(){
     $response = (object)array(
         'method'        => 'roles',
         'success'       => false,
         'status'        => 200,
         'error_code'    => 0,
         'error_message' => '',
         'roles'          => array()
     );

     try {
         $roleModel    = new \app\models\Role();
         $roles = $roleModel->findByStatus(1);
         $responseRoles = array();
         foreach ($roles as $role){
             $responseRoles[] = array('id'     => $role->getId(),
                                      'active' =>(bool) $role->getActive(),
                                      'name'   => $role->getName(),
                                      'users'  => $roleModel->getCountByRole($role->getName()),
                                );
         }
         $response->success = true;
         $response->roles = $responseRoles;
         unset($response->error_code);
         unset($response->error_message);
     } catch (\Exception $e) {
         $response->success = false;
         $response->error_code = $e->getCode();
         $response->error_message = $e->getMessage();
     }
     $this->setContent($response);
 }

    /**
     *
     * return a role with their users.
     */
    public function getRole(){
        $response = (object)array(
            'method'        => 'role',
            'success'       => false,
            'status'        => 200,
            'error_code'    => 0,
            'error_message' => ''
        );

        $data = (object) $_REQUEST;
        try {
            if(empty($data->id)){
                    throw new \Exception('Role Id is required.');
            }
            $roleModel    = new \app\models\Role();
            if(empty($data->id)){
                throw new \Exception('Role Id is required.');
            }
            else{
                $id = $_REQUEST['id'];
            }
            $users = $roleModel->getUsersForRole($id);
            $responseRole = array();
            foreach ($users as $user){
                $responseRole[] = array('id'   => $user->getId(),
                                        'name' => $user->getName(),
                                        'email'=> $user->getEmail()
                );
            }
            $response->success = true;
            $response->users = $responseRole;
            unset($response->error_code);
            unset($response->error_message);
        } catch (\Exception $e) {
            $response->success = false;
            $response->error_code = $e->getCode();
            $response->error_message = $e->getMessage();
        }
        $this->setContent($response);
    }

    /**
     * Create a new role
     */
    public function postCreateRole(){
        $response = (object)array(
            'method'        => 'createrole',
            'success'       => false,
            'status'        => 200,
            'error_code'    => 0,
            'error_message' => ''
        );
        // decode json data
        $json = file_get_contents('php://input');
        $data = json_decode(trim($json));
        $requiredParams = array('name', 'active');
        try {
            $data = (array) $data;
            foreach ($requiredParams as $param){
                if(empty($data[$param])){
                    throw new \Exception($param .' is required.');
                }
            }
            $data[] = array('active' => $data['active'],
                            'name'   => strtolower($data['name'])
                      );

            $roleModel    = new \app\models\Role();
            $roleId = $roleModel->create($data);
            if($roleId === 0){
                throw new \Exception('Could not create Role.');
            }
            $role = $roleModel->findById($roleId);
            $responseRole = array('id' => $role->getId(),
                                  'active' => (boolean) $role->getActive(),
                                  'name'   => $role->getName()
                            );
            $response->success = true;
            $response->role = $responseRole;
            unset($response->error_code);
            unset($response->error_message);
        } catch (\Exception $e) {
            $response->success = false;
            $response->error_code = $e->getCode();
            $response->error_message = $e->getMessage();
        }
        $this->setContent($response);
    }

    /**
     * update a given role
     */
    public function postUpdateRole(){
        $response = (object)array(
            'method'        => 'updaterole',
            'success'       => false,
            'status'        => 200,
            'error_code'    => 0,
            'error_message' => ''
        );
        // decode json data
        $json = file_get_contents('php://input');
        $data = json_decode(trim($json));
        $requiredParams = array('id', 'name', 'active');
        try {
            $data = (array) $data;
            foreach ($requiredParams as $param){
                if(empty($data[$param])){
                    throw new \Exception($param .' is required.');
                }
            }
            $data[] = array('id' => $data['id'],
                            'active' => $data['active'],
                            'name'   => strtolower($data['name'])
            );

            $roleModel    = new \app\models\Role();
            $roleId = $roleModel->save($data);
            $role = $roleModel->findById($roleId);
            $responseRole = array('id' => $role->getId(),
                                  'active' => (boolean) $role->getActive(),
                                  'name'   => $role->getName()
            );
            $response->success = true;
            $response->role = $responseRole;
            unset($response->error_code);
            unset($response->error_message);
        } catch (\Exception $e) {
            $response->success = false;
            $response->error_code = $e->getCode();
            $response->error_message = $e->getMessage();
        }
        $this->setContent($response);
    }

    /**
     * delete a given role
     */
    public function postDeleteRole(){
        $response = (object)array(
            'method'        => 'deleterole',
            'success'       => false,
            'status'        => 200,
            'error_code'    => 0,
            'error_message' => ''
        );
        // decode json data
        $json = file_get_contents('php://input');
        $data = json_decode(trim($json));
        $requiredParams = array('id');
        try {
            $data = (array) $data;
            foreach ($requiredParams as $param){
                if(empty($data[$param])){
                    throw new \Exception($param .' is required.');
                }
            }

            $roleModel    = new \app\models\Role();
            $roleId = $roleModel->delete($data['id']);
            $responseRoleId = array('id' => $roleId);
            $response->success = true;
            $response->role = $responseRoleId;
            unset($response->error_code);
            unset($response->error_message);
        } catch (\Exception $e) {
            $response->success = false;
            $response->error_code = $e->getCode();
            $response->error_message = $e->getMessage();
        }
        $this->setContent($response);
    }
}
