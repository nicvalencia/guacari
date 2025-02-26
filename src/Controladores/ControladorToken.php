<?php

namespace App\Controladores;
use DI\Container;
use App\Modelos\ModeloMensajeros as Mensajeros;
use App\Controladores\CsrfExtension as Token;


//use App\Http\Controllers\DateTime;
use DateTime;
use DatePeriod;
use DateInterval;
//use App\Controladores\ControladorIndex as Controindexqr;

use Slim\Views\Twig; // Las vistas de la aplicación
use Slim\Router; // Las rutas de la aplicación
use Respect\Validation\Validator as v; // para usar el validador de Respect
use Illuminate\Database\Capsule\Manager as DB;

use Interop\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Csrf\Guard as Guard;
use Slim\Psr7\Factory\ResponseFactory;

 

/**
 * Clase de controlador para el usuario de la aplicación
 */

class ControladorToken 
{

	protected $view;
	// objeto de la clase Router
	protected $router;
	protected $guard;

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
	/*public function __construct(Guard $csrf)
    {
        $this->csrf = $csrf;
    }*/

	/*public function __get($property)
	{
		if ($this->container->{$property}) {
			return $this->container->{$property};
		}
	}*/

    /**
     * Verifica que los parametros que recibe el controlador sean correctos
     * @param type array $args - los argumentos a evaluar
     */


	 
	

	public function validacreacampana($args)
    {

		//print_r($args);
        $valid = [
            // verifica que la id sea un entero
            //v::intVal()->validate($args['idmenu']),
        //  v::stringType()->length(2)->validate($args['grupos_idgrupos']),
					v::stringType()->validate($args['list_contact']),					            
					// verifica que no esté en blanco la contraseña
					//v::notBlank()->validate($args['estado_idestado']),
				//	v::notBlank()->validate($args['descripcion']),
					v::notBlank()->validate($args['nombre']),
					v::notBlank()->validate($args['sms']),
				//	v::intVal()->validate($args['clientes_idclientes']),
				];
					
				 return $valid;   
				                               
	}
	
	
	/**
	* Verifica la correctud de un conjunto de validaciones
	* @param type array $validaciones - el conjunto de validaciones a evaluar
	* @throws \Exception cuando las validaciones no están en un arreglo
	*/
	public static function verifica($validaciones)
	{
		
		if(!is_array($validaciones)){
			throw new \Exception('Las validaciones deben estar en un arreglo');
		} else {
			foreach($validaciones as $v){
				if ($v == false) {
					//echo $v;
					return false; // todas las validaciones deben cumplirse para que sea correcto
				}
			}
			return true;
			//echo "hhh";
		}
	}

	/*-- Funciones del CRUD --*/
	public function regenerate_token(Request $request, Response $response, $args)
    {   
	
		$responseFactory = new ResponseFactory(); // Note that you will need to import
        $this->guard = new Guard($responseFactory);      

	    $keyPair = $this->guard->generateToken();
		$nameKey = $this->guard->getTokenNameKey();
		$valueKey = $this->guard->getTokenName();
	
		
		$name = $this->guard->getTokenValueKey();
		$value = $this->guard->getTokenValue();


		return $response->withJson( [		
				'keys' => [
					'name'  => $nameKey,
					'value' => $valueKey
				],
				'values' => ['name'  => $name,
				'value' => $value]
			
		]);
	   
	}


	
		
	

}