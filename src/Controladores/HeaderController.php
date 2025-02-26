<?php
namespace App\Controladores;

use App\Modelos\ModeloClientes as Clientes;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Container\ContainerInterface;
use Slim\Routing\RouteContext;

class HeaderController 
{
    
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        $slimHeaders = $request->getHeaders();
        if($slimHeaders['cglicencia'][0]){
            $key = $slimHeaders['cglicencia'][0];
        }else{
            $key = $slimHeaders['Cglicencia'][0];
        }
        return $handler->handle($request);
        //$licencia = Clientes::select('licencia','apikey','key360','canal')->where('licencia',$key)->where('estados_idestados',1)->first();


        /*$url2 = "https://webhook.site/9504b4ed-36a9-461d-a9b2-28fefa70d601";                
        $client2 =  new \GuzzleHttp\Client();
        $data2 = $client2->post($url2, ['json' =>  [$slimHeaders,'aaaaa'=>$licencia] ]);*/

        /*if($licencia->licencia){
            define('TOKEN',$licencia->apikey);             
            define('APIKEY',$licencia->key360);
            define('CANAL',$licencia->canal);
            return $handler->handle($request);
        }else{
           
            $responseFactory = new \Slim\Psr7\Factory\ResponseFactory();
            $response = $responseFactory->createResponse(404);
            return $response;
        }*/
                
    }
}