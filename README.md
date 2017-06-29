# Users

[![Package version](https://img.shields.io/packagist/v/erdiko/users.svg?style=flat-square)](https://packagist.org/packages/erdiko/users)
[![CircleCI](https://img.shields.io/circleci/project/github/Erdiko/users/develop.svg?style=flat-square)](https://circleci.com/gh/Erdiko/users)
[![license](https://img.shields.io/github/license/erdiko/users.svg?style=flat-square)](https://github.com/Erdiko/users/blob/master/LICENSE)

**Erdiko Users**

The `erdiko/users` is a package adding Service Models and AJAX endpoints for user management in a Erdiko application or your custom application. It will allow you to authenticate and authorize your users as well as create a user entity stored in a database.

Erdiko users leverages our [authenticate](https://github.com/Erdiko/authenticate) and [authorization](https://github.com/Erdiko/authorize) packages.

Installation
------------
### Install the package via Composer

Add package using composer

`composer require erdiko/users`

### Create / Install the DB

This package relies upon a number of database tables to store user records. You must create the database & tables before you can use this package.

**Please Note**: If you are using this package along with our [erdiko/user-admin](https://github.com/Erdiko/user-admin) package, these tables will be created for you with our installation/quick start process.

Add required tables into your database running in order the .sql files placed in sql directory inside the package:

### Create the Database

1. Log in to you Database server with a user that has permissions to create databases
2. Create the database: 

	`mysql> CREATE DATABASE 'users';`
3. Run the SQL script to create the required tables: 
   
   `mysql> source [PATH TO LOCAL REPO]\sql\dumps\user-admin.sql;`

### Add the required routes to your Erdiko application

Below are examples of the minimum required routes to interact with the `users` package:

* Login Controller OR UserAuthenticationAjax Controller Route
    * The Login Controller exposes self-contained login/logout actions and views, these methods expose an HTML form to allow users to login
        * `"/[ROUTE NAME]/:action": "\erdiko\users\controllers\admin\UserAjax"`
    * The UserAuthenticationAjax controller provides actions to manage login/logout and password related situations as forgotPass and changePassword. This route is for AJAX login & logout.
        * `"/[ROUTE NAME]/:action": "\erdiko\users\controllers\UserAuthenticationAjax"`
* Userajax Controller Route
    * Provides actions relative to manage users without privileges, to have it accessible.
        * `"/[ROUTE NAME]]/:action": "\erdiko\users\controllers\UserAjax"`
* admin\Userajax Controller Route
    * Provides actions relative to manage users as admin level
        * `"/ROUTE NAME]/:action": "\erdiko\users\controllers\admin\Userajax"`

##### Example Route Config

Below is an example config containing all the AJAX endpoints exposed by the package:

```
 {
     "routes": {
         "/ajax/users/admin/:action": "\erdiko\users\controllers\admin\UserAjax",
         "/ajax/users/:action": "\erdiko\users\controllers\UserAjax",
         "/ajax/roles/:action": "\erdiko\users\controllers\RoleAjax",
         "/ajax/auth/:action": "\erdiko\users\controllers\UserAuthenticationAjax",
         "/users/:action": "\erdiko\users\controllers\Login"
     }
 }
```

Project Documentation
---------------------

Complete project documentation can be found on our Erdiko documentation site (coming soon). 

Special Thanks
--------------

Arroyo Labs - For sponsoring development, [http://arroyolabs.com](http://arroyolabs.com)
