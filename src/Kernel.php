<?php

declare(strict_types=1);

namespace App;

use App\Service\PaymentService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimple\Container;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Kernel
{
    /** @var Container */
    protected $container;

    protected $routeCollection;

    /**
     * @return string
     */
    public static function getProjectDir(): string
    {
        return __DIR__ . '/..';
    }

    /**
     * @return string
     */
    public static function getCacheDir(): string
    {
        return self::getProjectDir() . '/var/cache';
    }

    /**
     * @return string
     */
    public static function getLogsDir(): string
    {
        return self::getProjectDir() . '/var/log';
    }

    /**
     * @return RouteCollection
     */
    protected function getRouteCollection(): RouteCollection
    {
        if (null === $this->routeCollection) {
            $locator = new FileLocator([self::getProjectDir() . '/config']);
            $loader = new YamlFileLoader($locator);
            $this->routeCollection = $loader->load(self::getProjectDir() . '/config/routes.yaml');
        }

        return $this->routeCollection;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    protected function matchRoute(Request $request): array
    {
        $matcher = new UrlMatcher($this->getRouteCollection(), (new RequestContext())->fromRequest($request));

        return $matcher->match($request->getPathInfo());
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws \Exception
     */
    public function handle(Request $request): Response
    {
        $container = $this->getContainer();
        $container['request'] = $request;
        $container['session']->start();
        $container['event_dispatcher']->addListener(KernelEvents::CONTROLLER, function (ControllerEvent $event) use ($container) {
            $event->getController()[0]->setContainer($container);
        });

        try {
            $route = $this->matchRoute($request);
        } catch (ResourceNotFoundException $exception) {
            return new Response('Page not found', Response::HTTP_NOT_FOUND);
        }

        $request->attributes->add($route);

        try {
            $response = $container['http_kernel']->handle($request);
        } catch (\Exception|\Error $e) {
            $container['logger']->error($e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $response = new Response('Some error occurred', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $response;
    }

    /**
     * @return string
     */
    public static function getDatabaseUrl(): string
    {
        $locator = new FileLocator([self::getProjectDir() . '/config']);
        $file = $locator->locate('parameters.yaml');
        $parameters = Yaml::parseFile($file);

        return $parameters['database_url'];
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        if (null === $this->container) {
            $this->initContainer();
        }

        return $this->container;
    }

    protected function initContainer(): void
    {
        $this->container = new Container([
            'kernel' => $this,
            'project_dir' => Kernel::getProjectDir(),
            'logs_dir' => Kernel::getLogsDir(),
            'event_dispatcher' => function () {
                return new EventDispatcher();
            },
            'controller_resolver' => function () {
                return new ControllerResolver();
            },
            'argument_resolver' => function () {
                return new ArgumentResolver();
            },
            'http_kernel' => function ($c) {
                return new HttpKernel($c['event_dispatcher'], $c['controller_resolver'], null, $c['argument_resolver']);
            },
            'session' => function () {
                return new Session();
            },
            'em' => function ($c) {
                $config = Setup::createAnnotationMetadataConfiguration(
                    [Kernel::getProjectDir() . '/src/Entity'],
                    false,
                    null,
                    null,
                    false
                );

                return EntityManager::create(['url' => $c['database_url']], $config);
            },
            'twig' => function ($c) {
                $loader = new FilesystemLoader($c['project_dir'] . '/templates');

                return new Environment($loader, [
                    'cache' => Kernel::getCacheDir() . '/twig',
                ]);
            },
            'logger' => function ($c) {
                return (new Logger('app'))
                    ->pushHandler(new StreamHandler($c['logs_dir'] . '/app.log', Logger::DEBUG));
            },
            'payment_service' => function () {
                return new PaymentService();
            },
        ]);

        $locator = new FileLocator([self::getProjectDir() . '/config']);
        $file = $locator->locate('parameters.yaml');
        $parameters = Yaml::parseFile($file);

        foreach ($parameters as $key => $value) {
            $this->container[$key] = $value;
        }
    }
}
