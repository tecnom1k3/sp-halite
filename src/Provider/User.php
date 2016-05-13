<?php
namespace Acme\Provider;

use Acme\Controller\Message as MessageController;
use Acme\Controller\User as UserController;
use Acme\Service\Message as MessageService;
use Acme\Service\User as UserService;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

/**
 * Class User
 * @package Acme\Provider
 */
class User implements ControllerProviderInterface
{
    /**
     * @param Application $app
     * @return ControllerCollection
     */
    public function connect(Application $app)
    {
        $app['service.user'] = $app->share(function () use ($app) {
            return new UserService($app['db']);
        });

        $app['service.message'] = $app->share(function () use ($app) {
            return new MessageService($app['db'], $app['service.user'], $app['service.halite']);
        });

        $app['controller.user'] = $app->share(function () use ($app) {
            return new UserController($app['service.user']);
        });

        $app['controller.message'] = $app->share(function () use ($app) {
            return new MessageController($app['service.message']);
        });

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];
        $controllers->get('/', 'controller.user:getList');
        $controllers->get('/{userId}/messages', 'controller.message:getList');
        $controllers->post('/{userId}/messages', 'controller.message:save');
        $controllers->get('/{userId}/messages/{messageId}', 'controller.message:get');
        return $controllers;
    }
}
