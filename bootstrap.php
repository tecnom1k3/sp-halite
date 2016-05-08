<?php
require_once('vendor/autoload.php');

use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;
use Acme\Provider\User as UserProvider;
use Silex\Provider\DoctrineServiceProvider;

$app = new Application;

$app['debug'] = true;

$app->register(new ServiceControllerServiceProvider);

$app->register(new DoctrineServiceProvider, [
    'db.options' => [
        'driver' => 'pdo_mysql',
        'host' => 'localhost',
	'dbname' => 'messages',
	'user' => 'root',
	'password' => 'root',
	
    ]
]);

$app->mount('/users', new UserProvider);

$app->run();
