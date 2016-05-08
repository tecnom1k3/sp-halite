<?php
chdir(dirname(__DIR__));
require_once('vendor/autoload.php');

use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;
use Acme\Provider\User as UserProvider;

$app = new Application;

$app->register(new ServiceControllerServiceProvider);

$app->mount('/users', new UserProvider);

$app->run();
