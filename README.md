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

2. Add UserAuthenticationAjax controller's route.
 
 It provides actions to manage login/logout and password related situations as forgotpass and changePass, 
 to have it accessible, edit your `routes.json` like this:

```
{
    "routes": {
        "/": "\app\controllers\Front",
        ...
        "/users/authentication/:action": "\erdiko\users\controllers\UserAuthenticationAjax"
    }
}
```
3. Add \admin\Userajax controller's route.
 
 It provides actions relative to manage users as admin level. All the actions requires to be in session firts, 
 to have it accessible, edit your `routes.json` like this:

```
{
    "routes": {
        "/": "\app\controllers\Front",
        ...
        "/users/:action": "\erdiko\users\controllers\admin\Userajax"
    }
}
```

4. Add Userajax controller's route.
 
 It provides actions relative to manage users without privileges, to have it accessible, edit your `routes.json` like this:

```
{
    "routes": {
        "/": "\app\controllers\Front",
        ...
        "/users/:action": "\erdiko\users\controllers\Userajax"
    }
}
``` 

Special Thanks
--------------

Arroyo Labs - For sponsoring development, [http://arroyolabs.com](http://arroyolabs.com)