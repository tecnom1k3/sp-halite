<?php
namespace Acme\Controller;

use Acme\Service\Message as MessageService;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Message
 * @package Acme\Controller
 */
class Message
{
    /**
     * @var MessageService
     */
    private $service;

    public function __construct(MessageService $service)
    {
        $this->setService($service);
    }

    /**
     * @param MessageService $service
     */
    public function setService(MessageService $service)
    {
        $this->service = $service;
    }

    /**
     * @param $userId
     * @param Application $app
     * @return JsonResponse
     */
    public function getList($userId, Application $app)
    {
        $arrMessages = $this->service->getList($userId);
        return $app->json($arrMessages);
    }

    /**
     * @param $userId
     * @param $messageId
     * @param Application $app
     * @return JsonResponse
     */
    public function get($userId, $messageId, Application $app)
    {
        return $app->json($this->service->get($userId, $messageId));
    }

    /**
     * @param $userId
     * @param Request $request
     * @param Application $app
     * @return JsonResponse
     */
    public function save($userId, Request $request, Application $app)
    {
        $parameters = json_decode($request->getContent(), true);

        $lastInsertId = $this->service->save($parameters['from'], $userId, $parameters['subject'],
            $parameters['message']);
        return $app->json(['messageId' => $lastInsertId]);
    }
}
