<?php

require_once './vendor/autoload.php';

use Symfony\Component\Config\FileLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;

$fileLocator = new FileLocator(array(__DIR__));

$requestContext = new RequestContext();
$requestContext->fromRequest(Request::createFromGlobals());

$router = new Router(
    new YamlFileLoader($fileLocator),
    'config/routes.yaml',
    array('cache_dir' => __DIR__ . '/cache'),
    $requestContext
);

$request = Request::createFromGlobals();

$matcher = new UrlMatcher($router->getRouteCollection(), new RequestContext());

$dispatcher = new EventDispatcher();
$dispatcher->addSubscriber(new RouterListener($matcher, new RequestStack()));

$controllerResolver = new ControllerResolver();
$argumentResolver = new ArgumentResolver();

$kernel = new HttpKernel($dispatcher, $controllerResolver, new RequestStack(), $argumentResolver);

$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
