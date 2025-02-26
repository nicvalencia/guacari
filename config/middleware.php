<?php

namespace App\Controladores;

use DI\Container;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Routing\RouteCollectorProxy;
use Slim\Psr7\Response;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteContext;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Slim\Flash\Messages;
use Slim\Psr7\Factory\ResponseFactory;

use App\Controladores\SessionStartMiddleware;
use App\Controladores\AuthMiddleware;
use App\Controladores\HttpNotFoundExceptionf;





$app->add(function (Request $request, RequestHandler $handler) {

    $routeContext = RouteContext::fromRequest($request);
    $routeParser = $routeContext->getRouteParser();
    $route = $routeContext->getRoute();
    $routeName = $route ? $route->getName() : null;

    $publicRoutesArray = [
        'Login',
        'register',
        'Ingreso',
        'login/new_pass',
        'login/ingreso',
        'Excepciones',
        'Token',
        'Permisos',
        'Newpass',
        'Salida'
    ];

    // Si la ruta es pública, permitir la solicitud
    if (in_array($routeName, $publicRoutesArray)) {
        return $handler->handle($request);
    }

    // Si el usuario no está autenticado, detener la ejecución inmediatamente
    if (!Sentinel::check()) {
        $response = $handler->handle($request);
        /*$response = new Response();
        $response->getBody()->write(json_encode([
            'error' => 'No autenticado',
            'redirect' => $routeParser->urlFor('Login')
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);*/

        $url = $routeParser->urlFor('Login');
        return $response->withRedirect($url);
    }

    // Obtener permisos del usuario
    $path = substr($request->getUri()->getPath(), 1);
    $new_rol = Sentinel::findRoleById($_SESSION['rol']);
    $permisos = array_keys($new_rol->permissions);

    // Verificar permisos, si no tiene acceso, detener ejecución
    if (!Sentinel::hasAnyAccess($path) && !in_array($routeName, $permisos)) {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => 'Acceso no autorizado'
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    // Si el usuario tiene permisos, procesar la solicitud
    return $handler->handle($request);
});


$app->add(SessionStartMiddleware::class);
// Registrar middleware de autenticación
