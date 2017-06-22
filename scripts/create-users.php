<?php
/**
 * create-users.php
 *
 * @package     erdiko/users
 * @copyright   Copyright (c) 2017, Arroyo Labs, http://www.arroyolabs.com
 * @author      Andy Armstrong, andy@arroyolabs.com
 */


class ErdikoUsersInstall {

    private $_roleService = null;
    private $_userService = null;

    private $_rolesArray = array();
    private $_usersArray = array();

    /**
     *
     *
     */
    public function __construct($rolesArray, $usersArray)
    {
        $this->_rolesArray = $rolesArray;
        $this->_usersArray = $usersArray;
    }

    /**
     * loop through the roles array and create records
     */
    public function installRoles()
    {
        $this->_roleService = new erdiko\users\models\Role();

        $results = array(
            "successes" => array(),
            "failures"  => array(),
        );

        foreach($this->_rolesArray as $role) {
            // attempt to create the role
            // wrap this in a try/catch since this throws an exception if failure on create
            $createResult = false;
            try {
                $createResult = (boolean)$this->_roleService->create($role);
            } catch(\Exception $e) {
                // TODO do we need to log this elsewhere?
            }

            if(true !== $createResult) {
                $results["failures"][] = $role;
            } else {
                $results["successes"][] = $role;
            }
        }

        return $results;
    }

    /**
     *
     */
    private function _getRole($roleName)
    {
        return $this->_roleService->findByName($roleName);
    }

    /**
     * loop through the users array and create records
     */
    public function installUsers()
    {
        $this->_userService = new erdiko\users\models\User();

        $results = array(
            "successes" => array(),
            "failures"  => array(),
        );

        foreach($this->_usersArray as $user) {

            // get role ID from the name
            $user["role"] = $this->_getRole($user["role"])->getId();

            // create the user
            $createResult = $this->_userService->createUser($user);

            unset($user["password"]);

            if(true !== $createResult) {
                $results["failures"][] = $user;
            } else {
                $results["successes"][] = $user;
            }
        }

        return $results;
    }

}

// convert all error messages into exceptions so we can handle with some sanity
function exception_error_handler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
}

set_error_handler("exception_error_handler");

// lets start the install

$roles = array(
    (Object)array(
        "name"      => "admin",
        "active"    => "1",
    ),
    (Object)array(
        "name"      => "anonymous",
        "active"    => "1",
    ),
);

$users = array(
    array(
        "email"     => "erdiko@arroyolabs.com",
        "name"      => "Erdiko Admin",
        "password"  => "password",
        "role"      => "admin",
    ),
    array(
        "email"     => "user.bar@arroyolabs.com",
        "name"      => "Bar Erdiko, Esq",
        "password"  => "barpassword",
        "role"      => "anonymous",
    )
);

echo "\033[32mDatabase installation start \033[0m\n\r";

try {

    // get path to bootstrap include and make sure we actually have this variable
    $opts = getopt('b:q:');

    if(!array_key_exists("b", $opts) || empty($opts["b"])) {
        throw new \Exception("Bootstrap path is a required paramter");
    }

    $quiet = (array_key_exists("q", $opts) || !empty($opts["q"]));

    $bootstrap = filter_var($opts["b"]);

    // require the bootstrap file
    if(!file_exists($bootstrap) || !is_file($bootstrap)) {
        throw new \Exception("Erdiko bootstrap file was not found at `" . $bootstrap . "`");
    }

    require_once $bootstrap;

    // Make sure Erdiko has been bootstrapped
    if(!defined("ERDIKO_ROOT") || !class_exists("Erdiko")) {
        throw new \Exception("Erdiko has not been bootstrapped");
    }

    $erdikoUsersInstall = new ErdikoUsersInstall($roles, $users);

    $roleResults = $erdikoUsersInstall->installRoles();

    $userResults = $erdikoUsersInstall->installUsers();

    if(!$quiet) {
        echo "\033[32mRoles added: " . count($roleResults["successes"]) . " \033[0m\n\r";
        echo "\033[31mRoles failed: " . count($roleResults["failures"]) . " \033[0m\n\r";
        echo "\033[32mUsers added: " . count($userResults["successes"]) . " \033[0m\n\r";
        echo "\033[31mUsers failed: " . count($userResults["failures"]) . " \033[0m\n\r";
    }

} catch(ErrorException $e) {
    echo ("\033[31mDB Installation ErrorException: " . $e->getMessage() . "\033[0m\n\r");
    die(2);
} catch(\Exception $e) {
    echo ("\033[31mDB Installation Exception: " . $e->getMessage() . "\033[0m\n\r");
    die(1);
}

if(!$quiet) {
    echo "\033[32mDatabase installation complete \033[0m\n\r";
}

die(0);
