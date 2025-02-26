<?php

namespace App\Controladores;

use DI\Container;
use App\Modelos\ModeloClientes as Clientes;
use App\Modelos\ModeloTiposIdentificacion as TipoIdentificacion;
use App\Modelos\ModeloIndustria as Industria;


use Slim\Views\Twig; // Las vistas de la aplicación
use Slim\Router; // Las rutas de la aplicación
use Respect\Validation\Validator as v; // para usar el validador de Respect

use Interop\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Carbon\Carbon;

/**
 * Clase de controlador para el usuario de la aplicación
 */

class ControladorClientes
{

    protected $view;
    // objeto de la clase Router
    protected $router;


    /**
     * Constructor de la clase Controller     
     * @param type Slim\Router $router - Ruta
     */
    /*public function __construct( Router $router)
    {
		
		$this->router = $router;
	}*/

    protected $container;
    public function __construct(Container $container)
	{
		$this->container = $container;		
	}


    /**
     * Verifica que los parametros que recibe el controlador sean correctos
     * @param type array $args - los argumentos a evaluar
     */


    /*-- Funciones del CRUD --*/

    public function  index(Request $request, Response $response, $args)
    {

        $tipo_identificacion = TipoIdentificacion::where('estados_idestados',1)->get();
        $industria = Industria::where('estados_idestados',1)->get();

        return Twig::fromRequest($request)->render($response,'admin/clientes/crear_listar_clientes.twig',['tipo_identificacion' => $tipo_identificacion,
                                                                                                'industria' => $industria]);        
        
    }

    public function listado_clientes(Request $request, Response $response, $args)
    {

        $clientes = Clientes::select('clientes.razon_social','tipo_documento.nom_tipo_identificacion','industria.nom_industria',
                                    'clientes.idclientes','estados.idestados','clientes.num_documento')
                            ->join('tipo_documento','tipo_documento.idtipo_documento','=','clientes.idtipo_documento')
                            ->join('industria','industria.idindustria','=','clientes.industria_idindustria')
                            ->join('estados','estados.idestados','=','clientes.estados_idestados')
                            ->whereNotIn('idclientes',[11])
                            ->get();

        return $response->withJson(['data'=>$clientes]);        
    }

    public function  index_editar_clientes(Request $request, Response $response, $args)
    {

        $tipo_identificacion = TipoIdentificacion::where('estados_idestados',1)->get();
        $industria = Industria::where('estados_idestados',1)->get();
        $cliente = Clientes::where('idclientes',$args['idcliente'])->first();

        return Twig::fromRequest($request)->render($response,'admin/clientes/editar_clientes.twig',['tipo_identificacion' => $tipo_identificacion,
                                                                                                    'industria' => $industria,
                                                                                                    'cliente' =>  $cliente ]);        
        
    }


    public function save_edit_clientes(Request $request, Response $response, $args){

        $param = $request->getParsedBody(); 
        $uploadedFiles = $request->getUploadedFiles();
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        
        if(empty($param['estado'])){ $param['estado'] = 2;}

        if($uploadedFiles['imgprofile']){			
            if ($uploadedFiles['imgprofile']->getError() === UPLOAD_ERR_OK) {
                if(round($uploadedFiles['imgprofile']->getSize() / 1024 / 1024, 2) < 10){
                    $content = base64_encode(file_get_contents($_FILES['imgprofile']['tmp_name']));                    						
                    $type_media = $uploadedFiles['imgprofile']->getClientMediaType();
                    $imagen = 'data:'.$type_media.';base64,'.$content;		
                }
            }			
        }

        if($param['idcliente']){

            Clientes::where('idclientes',$param['idcliente'])->update(['industria_idindustria'   => $param['industria'],
                                                                        'idtipo_documento'         => $param['tipo_documento'],
                                                                        'razon_social'             => $param['nombre'],
                                                                        'estados_idestados'        => $param['estado'],
                                                                        'imgprofile'               => $imagen,
                                                                        'num_documento'            => $param['num_documento']]);

            return $response->withJson([
                    'succes' => true , 
                    'tipo' => 'success',                    
                    'promp' => 1,
                    'redirect' => 1,
                    'url_redirect' => $routeParser->urlFor('Clientes'),
                    'close_modal' => 'open_modal_email',
                    'message'=>'Los datos se guardaron con éxito.']);	                                                                        
        }else{

            Clientes::insert(['industria_idindustria'   => $param['industria'],
                             'idtipo_documento'         => $param['tipo_documento'],
                             'razon_social'             => $param['nombre'],
                             'estados_idestados'        => $param['estado'],
                             'imgprofile'               => $imagen,
                             'num_documento'            => $param['num_documento']]);

        
            return $response->withJson([
                    'succes' => true , 
                    'tipo' => 'success',                    
                    'promp' => 1,                    
                    'message'=>'Cliente creado con éxito.'   
                    ]);	

        }
    }

    public function update_state(Request $request, Response $response, $args){

        $param = $request->getParsedBody();   
        $cliente_etrategia = Clientes::select('clientes.idclientes')
                                        ->join('estrategia','estrategia.clientes_idclientes','=','clientes.idclientes')
                                       ->where('clientes.idclientes',$param['idcliente'])
                                       ->count();

        if($cliente_etrategia <= 0){
            $msjestado = $param['estado'] == 1 ? 'activado' : 'inactivado';
            Clientes::where('idclientes',$param['idcliente'])->update(['estados_idestados' => $param['estado']]);

            return $response->withJson([
                'succes' => true , 
                'tipo' => 'success',                    
                'promp' => 1,      
                'redirect' => 1,      
                'message'=>'Cliente '.$msjestado.' con éxito.'   
                ]);	            
        }else{
        
            return $response->withJson([
            'succes' => false , 
            'tipo' => 'info',                    
            'promp' => 1,      
            'redirect' => 1,      
            'message'=> 'No es posible inactivar cliente, actualmente tiene estrategias activas' ,
            'data' => '2' 
            ]);	
        }
    }

    
}
