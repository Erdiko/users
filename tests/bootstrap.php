<?php
// boot up Erdiko

// This is for standard installations
$bootstrap = dirname(dirname(dirname(dirname(__DIR__)))).'/app/bootstrap.php';

// This is for Docker (works within docker and Travis CI)
if(!file_exists($bootstrap))
	$bootstrap = '/code/app/bootstrap.php';

// This is for relative local dev
if(!file_exists($bootstrap))
	$bootstrap = dirname(dirname(__DIR__)).'/user-admin/app/bootstrap.php';

require_once $bootstrap;
