<?php
chdir(dirname(__DIR__));
require_once('vendor/autoload.php');

use Silex\Application;
use Acme\Provider\User as UserProvider;

$app = new Application;

$app->mount('/users', new UserProvider);

$app->run();
