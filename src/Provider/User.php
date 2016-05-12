<?php
namespace Acme\Provider;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Acme\Service\User as UserService;
use Acme\Service\Message as MessageService;
use Acme\Controller\User as UserController;
use Acme\Controller\Message as MessageController;

class User implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['service.user'] = $app->share(function() use ($app) {
             return new UserService($app['db']);
        });

	$app['service.message'] = $app->share(function() use($app) {
 	    return new MessageService($app['db'], $app['service.user']);
	});

        $app['controller.user'] = $app->share(function() use ($app) {
            return new UserController($app['service.user']);
        });

	$app['controller.message'] = $app->share(function() use($app){
	    return new MessageController($app['service.message']);
	});

        $controllers = $app['controllers_factory'];
        $controllers->get('/', 'controller.user:getList');
	$controllers->get('/{userId}/messages', 'controller.message:getList');
	$controllers->post('/{userId}/messages', 'controller.message:save');
        return $controllers;
    }
}
