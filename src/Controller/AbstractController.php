<?php

declare(strict_types=1);

namespace App\Controller;

use Pimple\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Twig\Error\Error;

abstract class AbstractController
{
    protected $container;

    /**
     * @param Container $container
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * @param       $data
     * @param int   $status
     * @param array $headers
     *
     * @return JsonResponse
     */
    protected function json($data, int $status = 200, array $headers = array()): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * @param string        $view
     * @param array         $parameters
     * @param Response|null $response
     *
     * @return Response
     * @throws \Exception
     */
    protected function render(string $view, array $parameters = array(), Response $response = null): Response
    {
        try {
            $content = $this->container['twig']->render($view, $parameters);
        } catch (Error $exception) {
            $this->container['logger']->error($exception->getMessage(), [
                'trace' => $exception->getTraceAsString(),
            ]);

            return new Response('Some error occurred', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (null === $response) {
            $response = new Response();
        }

        $response->setContent($content);

        return $response;
    }
}
