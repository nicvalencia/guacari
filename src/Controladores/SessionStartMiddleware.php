<?php
namespace App\Controladores;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Flash\Messages;

class SessionStartMiddleware implements MiddlewareInterface
{
    /**
     * @var Messages
     */
    private $flash;

    public function __construct(Messages $flash)
    {
        
        $this->flash = $flash;
        
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }                
        // Change the storage
        $this->flash->__construct($_SESSION);


        return $handler->handle($request);
    }
}