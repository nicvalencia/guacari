<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Selective\BasePath\BasePathMiddleware;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Csrf\Guard;

use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;

date_default_timezone_set("America/Bogota");
ini_set('display_errors', 0);
require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}


$container = new Container();
AppFactory::setContainer($container);
//$app->setBasePath('/');
// Create App
$app = AppFactory::create();
$responseFactory = $app->getResponseFactory();

$logger = new Logger('app');
$streamHandler = new StreamHandler(__DIR__ ."/../public/log/debug.log",100);
$logger->pushHandler($streamHandler);


$customErrorHandler = function (
    Request $request,
    \Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails    
) use ($app,$container,$logger) {
    $payload = ['error' => $exception->getMessage()];
    $response = $app->getResponseFactory()->createResponse();
    $error = $exception->getCode();
    $logger->error($exception);

    if ($exception->getCode() == 404) {
        $title = 'Page not found';
        $message = 'This page could not be found.';

        $response->getBody()->write(
          //json_encode($payload, JSON_UNESCAPED_UNICODE)
          <<<EOT
  <!DOCTYPE html>
  <html>
  <head>
    <title>$title - $error </title>
  
       <link rel="preconnect" href="https://fonts.googleapis.com" />
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
      <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet"
      />
  
      <!-- Icons. Uncomment required icon fonts -->
      <link rel="stylesheet" href="/assets/vendor/fonts/boxicons.css" />
  
      <!-- Core CSS -->
      <link rel="stylesheet" href="/assets/vendor/css/core.css" class="template-customizer-core-css" />
      <link rel="stylesheet" href="/assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
      <link rel="stylesheet" href="/assets/css/demo.css" />
  
      <!-- Vendors CSS -->
      <link rel="stylesheet" href="/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
  
      <!-- Page CSS -->
      <!-- Page -->
      <link rel="stylesheet" href="/assets/vendor/css/pages/page-misc.css" />
  </head>
  <body>
    <div class="container-xxl container-p-y">
        <div class="misc-wrapper">
          <h2 class="mb-2 mx-2">Page Not Found :(</h2>
          <p class="mb-4 mx-2">Oops! 😖 The requested URL was not found on this server.</p>
          <a href="javascript:history.back()" class="btn btn-primary">Regresar</a>
          <div class="mt-3">
            <img
              src="/assets/img/illustrations/page-misc-error-light.png"
              alt="page-misc-error-light"
              width="500"
              class="img-fluid"
              data-app-dark-img="illustrations/page-misc-error-dark.png"
              data-app-light-img="illustrations/page-misc-error-light.png"
            />
          </div>
        </div>
      </div>
  
  </body>
  </html>
  EOT
      );

    }else{        
        $response->getBody()->write(
          json_encode($payload, JSON_UNESCAPED_UNICODE)
        );
    }
   
return $response;
};


require  __DIR__ . '/container.php';

// Register middleware
(require __DIR__ . '/middleware.php');


// Register routes
(require __DIR__ . '/routes.php');



$app->addRoutingMiddleware();



$app->addBodyParsingMiddleware();
$app->add(new BasePathMiddleware($app));
$errorMiddleware = $app->addErrorMiddleware(true, true, true,$logger);
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);


require __DIR__ . '/dependencies.php';


return $app;
