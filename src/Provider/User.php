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
        /*
         * define the user service.
         *
         * Requires the doctrine entity manager
         */
        $app['service.user'] = $app->share(function () use ($app) {
            return new UserService($app['doctrine.entityManager']);
        });

        /*
         * define the message service.
         *
         * Requires the doctrine entity manager, the user service, and the halite service
         */
        $app['service.message'] = $app->share(function () use ($app) {
            return new MessageService($app['doctrine.entityManager'], $app['service.user']);
        });

        /*
         * define the user controller
         *
         * requires the user service
         */
        $app['controller.user'] = $app->share(function () use ($app) {
            return new UserController($app['service.user']);
        });

        /*
         * define the message controller
         *
         * requires the message service
         */
        $app['controller.message'] = $app->share(function () use ($app) {
            return new MessageController($app['service.message']);
        });

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];
        /*
         * routes GET requests to /user to the getList method on user controller
         */
        $controllers->get('/', 'controller.user:getList');
        /*
         * routes GET requests to /user/{userId}/messages to the getList method on the message controller
         */
        $controllers->get('/{userId}/messages', 'controller.message:getList');
        /*
         * routes POST requests to /user/{userId}/messages to the save method onn the message controller
         */
        $controllers->post('/{userId}/messages', 'controller.message:save');
        /*
         * routes GET requests to /users/{userId}/messages/{messageId} to the get method on the message controller
         */
        $controllers->get('/{userId}/messages/{messageId}', 'controller.message:get');
        return $controllers;
    }
}
