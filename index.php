<?php

require_once './vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Dotenv\Dotenv;
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
use Symfony\Component\Yaml\Yaml;

//setup .env
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env', __DIR__ . '/.env.sample');

//setup cache
$client = RedisAdapter::createConnection(
    $_ENV['REDIS_DNS']
);
$cache = new RedisAdapter($client);

//read config
$cache->get('SMS_APIS', function () {
    return Yaml::parseFile(__DIR__ . '/config/sms_api.yaml');
});

//setup eloquent
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => $_ENV['DB_DRIVER'],
    'host' => $_ENV['DB_HOST'],
    'database' => $_ENV['DB_DATABASE'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'charset' => $_ENV['DB_CHARSET'],
    'collation' => $_ENV['DB_COLLATION'],
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();


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
