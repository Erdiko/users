# Users

**Erdiko Users**

A users package to add user functionality to your application.  It will allow you to authenticate and authorize your users as well as create a user entity stored in a database.

Erdiko users leverages our authenticate and authorization packages.

**@note do not use in production yet, under heavy development**


Installation
------------
Add package using composer 

`composer require erdiko/users`

Add required tables into your database running in order the `.sql` files placed in `sql` directory inside the package.

How to Use
----------
1. Add Login controller's route.
 
 It provides a self-contained login/logout actions and views, to have it accessible, edit your 
 `routes.json` like this:

```
{
    "routes": {
        "/": "\app\controllers\Front",
        ...
        "/users/:action": "\erdiko\users\controllers\Login"
    }
}
```

It also provides an AJAX interface that expose endpoints you can use to interact with users, authentication and roles. 

Special Thanks
--------------

Arroyo Labs - For sponsoring development, [http://arroyolabs.com](http://arroyolabs.com)