<?php
chdir(dirname(__DIR__));
require_once('vendor/autoload.php');

use Silex\Application;

$app = new Application;

$app->get('/', function() use ($app){
	return 'hello world';
});

$app->run();
