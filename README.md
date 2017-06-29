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

### Create & Install the DB

This package relies upon a number of database tables to store user records. You must create the database & tables before you can use this package.

We highly recomend installing the DB and tables with our install scripts found in the erdiko/user-admin repo (`scripts/install-db.sh`). More information can be found on the erdiko/user-admin README file.

If you would like to install the database manually, please use the `users\sql\dumps\user-admin.sql` to create the database defintion.

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
