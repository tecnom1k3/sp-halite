<?php
namespace Acme\Controller;

use Acme\Service\User as UserService;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class User
 * @package Acme\Controller
 */
class User
{
    /**
     * @var UserService
     */
    private $service;

    /**
     * User constructor.
     * @param UserService $service
     */
    public function __construct(UserService $service)
    {
        $this->setService($service);
    }

    /**
     * @param UserService $service
     */
    public function setService(UserService $service)
    {
        $this->service = $service;
    }

    /**
     * @param Application $app
     * @return JsonResponse
     */
    public function getList(Application $app)
    {
        $arrUsers = $this->service->getList();
        return $app->json($arrUsers);
    }
}
