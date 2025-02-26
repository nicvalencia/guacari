<?php
namespace App\Controladores;
use Slim\Handlers\NotFound;
use Slim\Views\Twig;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/*class NotFoundHandler extends NotFound {

    private $view;
    private $templateFile;

    public function __construct(Twig $view, $templateFile) {
        $this->view = $view;
        $this->templateFile = $templateFile;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response,Throwable $exception, bool $displayErrorDetails) {
        parent::__invoke($request, $response);

        $this->view->render($response, $this->templateFile);

        return $response->withStatus(404);
    }

}*/