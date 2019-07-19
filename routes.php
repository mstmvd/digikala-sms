<?php
/**
 * Created by PhpStorm.
 * User: mostafa
 * Date: 7/19/19
 * Time: 2:18 PM
 */

use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router;

try {
    $fileLocator = new FileLocator(array(__DIR__));

    $requestContext = new RequestContext();
    $requestContext->fromRequest(Request::createFromGlobals());

    $router = new Router(
        new YamlFileLoader($fileLocator),
        'config/routes.yaml',
        array('cache_dir' => __DIR__ . '/cache'),
        $requestContext
    );

    // Find the current route
    $parameters = $router->match($requestContext->getPathInfo());
    $controller = $parameters['controller'];
    $method = $parameters['method'];

    $controller = new $controller();
    if (method_exists($controller, $method)) {
        $controller->$method();
    }
} catch (ResourceNotFoundException $e) {
    echo $e->getMessage();
}