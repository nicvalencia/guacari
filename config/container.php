<?php
use DI\Container;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteCollector;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Slim\Routing\RouteContext;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Flash\Messages;
use Selective\BasePath\BasePathDetector;
use Slim\Csrf\Guard;
use Slim\Psr7\Factory\ResponseFactory;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
$capsule = new Capsule;

/** @var \Slim\App $app */
$responseFactory = $app->getResponseFactory();


$basePath = (new BasePathDetector($_SERVER))->getBasePath();
$app->setBasePath($basePath);
$_SESSION['urlpath']= $app->getBasePath();

// Dependencia del contenedor para la traducción
$container->set(TranslatorInterface::class, function (Container $container) {
    $translator = new Translator('es'); // Configura el idioma por defecto
    $translator->addLoader('array', new ArrayLoader());

    // Cargar los mensajes de traducción
    $translator->addResource('array', [
        'authentication.failed' => 'Las credenciales son incorrectas.',
        'authentication.throttled' => 'Demasiados intentos. Inténtelo más tarde.',
        'Your account has not been activated yet.' => 'Su cuenta aún no ha sido activada.'
        // Más mensajes personalizados
    ], 'es');

    return $translator;
});

$container->set('csrf', function () use ($responseFactory) {
    //return new Guard($responseFactory);
    $guard = new Guard($responseFactory);
    $guard->setFailureHandler(function (Request $request, RequestHandlerInterface $handler) {
        $response = $handler->handle($request);
        $routeContext = RouteContext::fromRequest($request);
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        $route = $routeContext->getRoute();
        $routeName = $route->getName();

        $request = $request->withAttribute("csrf_status", false);
        if ($request->getAttribute("csrf_status") === false) {
            $authorize_route = ['Api'];


            if (in_array($routeName, $authorize_route)) {
               return $handler->handle($request);
            }

            $url = $routeParser->urlFor('Permisos');
            return $response->withStatus(400)->withHeader('Location', $url);


        } else {
           return $handler->handle($request);
        }

        //$handler->handle($request);
    });

    return $guard;
});

//$app->add('csrf');

$container->set('flash', function () {
    $storage = [];
    return new Messages($storage);
});



$container->set('view', function (Container $container) use ($app) {

    $storage = [];
    $twig = Twig::create(dirname(__DIR__) . '/templates', ['cache' => false, 'debug' => true]);

    $twig->addExtension(new App\Controladores\CsrfExtension());
    $twig->addExtension(new App\Controladores\ControladorMenu());
    $twig->addExtension(new \Twig\Extension\DebugExtension());
    $twig->addExtension(new App\Controladores\TwigMessagesRuntime());

    $environment = $twig->getEnvironment();
    $environment->addGlobal('session', $_SESSION);

    return $twig;
});

$twig = $container->get('view');
$app->add(TwigMiddleware::create($app, $twig));

$container->set('mailer', function (Container $container) {

    $view = $container->get('view');
    $mailer = new \Semhoun\Mailer\Mailer($view, [
        'host' => 'mail.grupointec.co',  // SMTP Host
        'port' => '465',  // SMTP Port
        'username' => 'notificaciones@grupointec.co',  // SMTP Username
        'password' => 'm(Ao&8TTQYTo',  // SMTP Password
        'protocol' => 'SSL'   // SSL or TLS
    ]);

    // Set the details of the default sender
    $mailer->setDefaultFrom('notificaciones@grupointec.co', 'Webmaster');

    return $mailer;
});



//controladores    
$container->set('db', function ($container) use ($capsule) {
    return $capsule;
});

$container->set('pdo', function (Container $container) {
    return $container->get('db')->getPdo();
});

$container->set('ControladorIndex', function (Container $container) use ($app) {
    return new App\Controladores\ControladorIndex($container);
});

$container->set('ControladorUsuario', function (Container $container) use ($app) {
    return new App\Controladores\ControladorUsuario($container);
});

$container->set('ControladorToken', function (Container $container) use ($app) {
    return new App\Controladores\ControladorToken($container);
});

$container->set('ControladorLogin', function (Container $container,TranslatorInterface $translator, Sentinel $sentinel) use ($app) {
    return new App\Controladores\ControladorLogin($container, $translator,  $sentinel);
});

$container->set('ControladorClientes', function (Container $container) use ($app) {
    return new App\Controladores\ControladorClientes($container);
});

$container->set('ControladorGrupos', function (Container $container) use ($app) {
    return new App\Controladores\ControladorGrupos($container);
});

$container->set('ControladorBot', function (Container $container) use ($app) {
    return new App\Controladores\ControladorBot($container);
});

$container->set('ControladorMenuBot', function (Container $container) use ($app) {
    return new App\Controladores\ControladorMenuBot($container);
});

