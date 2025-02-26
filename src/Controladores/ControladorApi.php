<?php

namespace App\Controladores;

use DI\Container;

use App\Modelos\ModeloClientes as Clientes;
use App\Modelos\ModeloTiposIdentificacion as TipoIdentificacion;
use App\Modelos\ModeloIndustria as Industria;
use App\Modelos\ModeloEstrategias as Estrategias;
use App\Modelos\ModeloExperiencias as Experiencias;
use App\Modelos\ModeloUsuario as Usuario; // para usar el modelo de usuario
use App\Modelos\ModeloUserClientes as UserClientes;
use App\Modelos\ModeloConfUsuarioExperiencia as ConfExperiencia;
use App\Modelos\ModeloEventos as Eventos;
use App\Modelos\ModeloZonas as Zonas;
use App\Modelos\ModeloConfMecanica as Confmecanica;



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
use MongoDB\Client as Mongo;
use OpenApi\Annotations as OA;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Slim\Csrf\Guard;
use Slim\Psr7\Factory\ResponseFactory;


/**
 * @OA\Info(
 *     title="API Flexu admin",
 *     version="1.0"
 * )
 *  @OA\Server(
 *     url="https://flexu.grupointec.co/api",
 *     description="API server"
 * )
 * @OA\SecurityScheme(
 *   description="Api Key for authorization.",
 *   securityScheme="apikey",
 *   type="apiKey",
 *   in="header",
 *   name="apikey"
 * )
 *
 * @OA\OpenApi(
 *   security={
 *     {"apikey":{}}
 *   }
 * )
 */
class OpenApi
{
}

/**
 * Clase de controlador para el usuario de la aplicación
 */

class ControladorApi
{

	protected $view;
	// objeto de la clase Router
	protected $router;

	protected $container;
	public function __construct(Container $container)
	{
		$this->container = $container;
	}


	public function genera_json_swagger(Request $request, Response $response, $args)
	{
		$openapi = \OpenApi\Generator::scan(['../src/Controladores/ControladorApi.php']);
		header('Content-Type: application/json');
		echo $openapi->toJson();
		return $response;
	}

	/**
	 * @OA\Post(
	 *     path="/login",
	 *     tags={"Clientes"},
	 *     @OA\RequestBody(
	 *         @OA\MediaType(
	 *             mediaType="application/json",
	 *             @OA\Schema(
	 *                 @OA\Property(
	 *                     property="usuario",
	 *                     type="string"
	 *                 ),
	 *                 @OA\Property(
	 *                     property="passwd",
	 *                     type="string"
	 *                 ),
	 *                 example={"usuario": "a3fb6", "passwd": "000"}
	 *             )
	 *         )
	 * ),
	 *     @OA\Response(
	 *         response="200",
	 *          description="Json with data",
	 *         @OA\MediaType(
	 *             mediaType="application/json"
	 *         ),
	 *     )
	 * )
	 */

	public function login(Request $request, Response $response, $args)
	{

		$param = $request->getParsedBody();



		$data = Sentinel::authenticate(
			array(
				'email' => $param['usuario'],
				'password' => $param['passwd']
			)
		);

		if (empty($data->id)) {

			return $response->withJson("La contraseña es incorrecta");
		} else {

			$idcliente = UserClientes::where('users_id', $data->id)->first();
			$fechaActual = Carbon::now();
			$experiencias = UserClientes::select('experiencia.idexperiencia', 'experiencia.nombre', 'experiencia.aforo_permitido as aforo', 'experiencia.fecha_fin as fecha_finalizacion')
				->join('register_users_form', 'register_users_form.users_id', '=', 'users_clientes.users_id')
				->join('info_formularios', 'info_formularios.idinfo_formularios', '=', 'register_users_form.idinfo_formularios')
				->join('experiencia', 'experiencia.idexperiencia', '=', 'info_formularios.idexperiencia')
				->join('users', 'users.id', '=', 'register_users_form.users_id')
				->where('users.email', $data->email)
				->where('experiencia.fecha_fin', '>=', $fechaActual)
				->get()->toArray();

			$final = array_merge(
				[
					'id' => $data->id,
					'email' => $data->email,
					'name' => $data->first_name . " " . $data->last_name,
					'idcliente' => $idcliente->clientes_idclientes
				],
				['experience' => $experiencias]
			);
			return $response->withJson($final);
		}
	}



	public function listado_clientes(Request $request, Response $response, $args)
	{

		$clientes = Clientes::select(
			'clientes.razon_social',
			'tipo_documento.nom_tipo_identificacion',
			'industria.nom_industria',
			'clientes.idclientes',
			'estados.idestados',
			'clientes.num_documento'
		)
			->join('tipo_documento', 'tipo_documento.idtipo_documento', '=', 'clientes.idtipo_documento')
			->join('industria', 'industria.idindustria', '=', 'clientes.industria_idindustria')
			->join('estados', 'estados.idestados', '=', 'clientes.estados_idestados')
			->get();

		return $response->withJson($clientes);
	}



	public static function list_estrategias_byclientes(Request $request, Response $response, $args)
	{
		//$param = $request->getParsedBody();  
		$res_name = Estrategias::select('nom_estrategia', 'idestrategia', 'site', 'clientes_idclientes')
			->where('clientes_idclientes', $args['idcliente'])
			->where('estados_idestados', 1)
			->get();

		return $response->withJson($res_name);
	}



	/**
	 * @OA\Get(
	 *     path="/listar_experiencias_clientes/{id}",
	 *     tags={"Experiencias"},
	 *    @OA\Parameter(
	 *          name="id",
	 *          in="path",
	 *          required=true,
	 *          description="Buscar experiencias de clientes" ,
	 *      @OA\Schema(
	 *              type="string"
	 *          )    
	 *   ),
	 *    
	 *     @OA\Response(
	 *         response="200",
	 *          description="Json with data",
	 *         @OA\MediaType(
	 *             mediaType="application/json"
	 *         ),
	 *     )
	 * )
	 */
	public static function listar_experiencias_clientes(Request $request, Response $response, $args)
	{

		$fechaActual = Carbon::now();
		$estrategias = Experiencias::select(
			'experiencia.nombre',
			'clientes.razon_social',
			'experiencia.idexperiencia',
			'experiencia.estado_inicio',
			'experiencia.idexperiencia'

		)
			->join('clientes', 'clientes.idclientes', '=', 'experiencia.clientes_idclientes')
			->where('clientes.idclientes', $args['idcliente'])
			->where('experiencia.fecha_fin', '>=', $fechaActual)
			->get();


		return $response->withJson($estrategias);
	}


	/**
	 * @OA\Get(
	 *     path="/valid_date_experience/{id}",
	 *     tags={"Experiencias"},	 
	 *    @OA\Parameter(
	 *          name="id",
	 *          in="path",
	 *          required=true,
	 *			description = "Validar si una fecha de una experiencia esta dentro de los rangos",
	 *      @OA\Schema(
	 *              type="string"
	 *          )    
	 *   ),
	 *    
	 *     @OA\Response(
	 *         response="200",
	 *          description="Json with data",
	 *         @OA\MediaType(
	 *             mediaType="application/json"
	 *         ),
	 *     )
	 * )
	 */
	public function validar_fecha_finalizacion_experiencia(Request $request, Response $response, $args)
	{
		$experiencias = Experiencias::find($args['idexperience']);

		// Convierte $fecha_fin a una instancia de Carbon
		$fechaFin = Carbon::parse($experiencias->fecha_fin);
		// Obtén la fecha actual
		$fechaActual = Carbon::now();

		// Compara si la fecha de vencimiento es anterior a la fecha actual
		if ($fechaFin->lessThan($fechaActual)) {
			return $response->withJson('false');
		} else {
			return $response->withJson('true');
		}
	}


	public static function listar_mecanicas_experiencias(Request $request, Response $response, $args)
	{

		$atributos = ConfExperiencia::select(
			'tipo_mecanicas.nombre as nom_tipo',
			'mecanica.created_at',
			'conf_mecanicas.index',
			'conf_mecanicas.media',
			'mecanica.idmecanica',
			'conf_mecanicas.idconf_mecanicas',
			'conf_mecanicas.estados_idestados',
			'mecanica.espacio'
		)
			->join('mecanica', 'mecanica.idmecanica', '=', 'conf_usuario_experiencia.mecanica_idmecanica')
			->join('conf_mecanicas', 'conf_mecanicas.mecanica_idmecanica', '=', 'mecanica.idmecanica')
			->join('tipo_mecanicas', 'tipo_mecanicas.idtipo_mecanicas', '=', 'conf_mecanicas.idtipo_mecanicas')
			->where('conf_usuario_experiencia.experiencia_idexperiencia', $args['idexperiencia'])
			->whereraw('conf_mecanicas.deleted_at is null')
			->distinct()
			->get();

		foreach ($atributos as $key => $data) {
			$estado = ($data->estados_idestados == 1) ? true : false;

			$info[] = [
				"MechanicName" => $data->nom_tipo,
				"IsActive" => $estado,
				"ActiveUntil" => $data->created_at,
				"Animation" => "pending",
				"Index" => $data->index,
				"url" => $data->media,
				"Espacio" => $data->espacio,
				"id" => $data->idconf_mecanicas
			];
		}

		return $response->withJson($info);
	}


	/**
	 * @OA\Get(
	 *     path="/listar_mecanicas_byespacios/{idexperiencia}",
	 *     tags={"Experiencias"},
	 *    @OA\Parameter(
	 *          name="idexperiencia",
	 *          in="path",
	 *          required=true,
	 *          description="ID de la experiencia" ,	 
	 *      @OA\Schema(
	 *              type="integer"
	 *          )    
	 *   ),   
	 *     @OA\Response(
	 *         response="200",
	 *          description="Json with data",
	 *         @OA\MediaType(
	 *             mediaType="application/json"
	 *         ),
	 *     )
	 * )
	 */
	public static function listar_mecanicas_experienciasbyespacios(Request $request, Response $response, $args)
	{


		$atributos = Experiencias::select(
			'tipo_mecanicas.nombre as nom_tipo',
			'conf_mecanicas.created_at',
			'objetos.index',
			'conf_mecanicas.content',
			'zonas.idzonas',
			'conf_mecanicas.estados_idestados',
			'conf_mecanicas.idconf_mecanicas'
		)
			->join('conf_mecanicas', 'conf_mecanicas.idexperiencia', '=', 'experiencia.idexperiencia')
			->join('objetos', 'objetos.idobjetos', '=', 'conf_mecanicas.objetos_idobjetos')
			->join('tipo_mecanicas', 'tipo_mecanicas.idtipo_mecanicas', '=', 'objetos.idtipo_mecanicas')
			->join('zonas', 'zonas.idzonas', '=', 'objetos.zonas_idzonas')
			->where('conf_mecanicas.idexperiencia', $args['idexperiencia'])

			->distinct()
			->get();



		foreach ($atributos as $key => $data) {
			$estado = ($data->estados_idestados == 1) ? true : false;

			$info[] = [
				"MechanicName" => $data->nom_tipo,
				"IsActive" => $estado,
				"ActiveUntil" => $data->created_at,
				"Animation" => "pending",
				"Index" => $data->index,
				"url" => $data->content,
				"Zona" => $data->idzonas
			];
		}

		return $response->withJson($info);
	}


	/**
	 * @OA\Get(
	 *     path="/listar_atributos/{idexperiencia}",
	 *     tags={"Mecanicas"},  
	 *    @OA\Parameter(
	 *          name="idexperiencia",
	 *          in="path",
	 *          required=true,
	 *          description="lista los atributos de las mecanicas incluidas en una experiencia por medio del ID de la experiencia" ,
	 *      @OA\Schema(
	 *              type="string"
	 *          )    
	 *   ),
	 *  
	 *     @OA\Response(
	 *         response="200",
	 *          description="lista los atributos de las mecanicas incluidas en una experiencia por medio del ID de la experiencia",
	 *         @OA\MediaType(
	 *             mediaType="application/json"
	 *         ),
	 *     )
	 * )
	 */
	public function listar_atributos_mecanicas(Request $request, Response $response, $args)
	{


		$atributos = Experiencias::select(
			'tipo_mecanicas.nombre',
			'conf_mecanicas.created_at',
			'objetos.index',
			'conf_mecanicas.estados_idestados',
			'conf_mecanicas.content',
			'zonas.nom_zona as espacio',
			'conf_mecanicas.idconf_mecanicas'
		)->join('conf_mecanicas', 'conf_mecanicas.idexperiencia', '=', 'experiencia.idexperiencia')
			->join('objetos', 'objetos.idobjetos', '=', 'conf_mecanicas.objetos_idobjetos')
			->join('tipo_mecanicas', 'tipo_mecanicas.idtipo_mecanicas', '=', 'objetos.idtipo_mecanicas')
			->join('zonas', 'zonas.idzonas', '=', 'objetos.zonas_idzonas')
			->where('conf_mecanicas.idexperiencia', $args['idexperiencia'])
			->get();

		/*$atributos = ConfExperiencia::select(
																		'tipo_mecanicas.nombre as nom_tipo',
																		'mecanica.created_at',
																		'conf_mecanicas.index',
																		'conf_mecanicas.media',
																		'mecanica.idmecanica',
																		'conf_mecanicas.idconf_mecanicas',
																		'conf_mecanicas.estados_idestados',
																		'mecanica.espacio'
																	)
																		->join('mecanica', 'mecanica.idmecanica', '=', 'conf_usuario_experiencia.mecanica_idmecanica')
																		->join('conf_mecanicas', 'conf_mecanicas.mecanica_idmecanica', '=', 'mecanica.idmecanica')
																		->join('tipo_mecanicas', 'tipo_mecanicas.idtipo_mecanicas', '=', 'conf_mecanicas.idtipo_mecanicas')
																		->where('conf_usuario_experiencia.experiencia_idexperiencia', $args['idexperiencia'])
																		->whereraw('conf_mecanicas.deleted_at is null')
																		->distinct()
																		->get();*/

		foreach ($atributos as $key => $data) {
			$estado = ($data->estados_idestados == 1) ? true : false;

			$info[] = [
				"MechanicName" => $data->nombre,
				"IsActive" => $estado,
				"ActiveUntil" => $data->created_at,
				"Animation" => "",
				"Index" => $data->index,
				"url" => $data->content,
				"Espacio" => $data->espacio,
				"id" => $data->idconf_mecanicas
			];
		}

		return $response->withJson($info);
	}


	/**
	 * @OA\Post(
	 *     path="/insert_events",
	 *     tags={"Eventos"},
	 * 	   description = "Agregar eventos en la BD de Mongo",
	 *     @OA\RequestBody(
	 *         @OA\MediaType(
	 *             mediaType="application/json",
	 *             @OA\Schema(
	 *                 @OA\Property(
	 *                     property="data",
	 *                     type="string"
	 *                 ),
	 *                 example={"firstname": "a3fb6", "lastname": "Jessica Smith"}
	 *             )
	 *         )
	 * ),
	 *    
	 *     @OA\Response(
	 *         response="200",
	 * description="Json with data",
	 *         @OA\MediaType(
	 *             mediaType="application/json"
	 *         ),
	 *     )
	 * )
	 */
	public function insert_events(Request $request, Response $response, $args)
	{

		$param = $request->getParsedBody();

		/* $client = new \GuzzleHttp\Client();
		$res = $client->request(
			'post',
			'https://webhook.site/fbb0c3fb-66f1-40fc-8d25-153396c4e8cc',
			[
				'json' => [$param]
			]
		); */

		Eventos::insert(['json_evento' => json_encode($param)]);



		return $response->withJson($param);
	}


	/**
	 * @OA\Post(
	 *     path="/search_events",
	 *     tags={"Eventos"},
	 * 	   description = "Filtrar los eventos por medio de cualquier campo",
	 *     @OA\RequestBody(
	 *         @OA\MediaType(
	 *             mediaType="application/json",
	 *             @OA\Schema(
	 *                 @OA\Property(
	 *                     property="info",
	 *                     type="string"
	 *                 ),
	 *                 example={"userPlayer.experience[0].idexperiencia":2}
	 *             )
	 *         )
	 * ),
	 *    
	 *     @OA\Response(
	 *         response="200",
	 * description="Json with data",
	 *         @OA\MediaType(
	 *             mediaType="application/json"
	 *         ),
	 *     )
	 * )
	 */
	public function search_events(Request $request, Response $response, $args)
	{

		$param = $request->getParsedBody();
		$keys = array_keys($param);
		$name = $keys[0];
		//$data = Eventos::whereJsonContains('json_evento->' . $name, json_encode($param[$name]))->limit(10)->get();
		//$data = Eventos::whereraw("JSON_CONTAINS(json_evento,'".json_encode($param)."')")->limit(10)->get();
		$data = Eventos::whereraw("JSON_UNQUOTE(JSON_EXTRACT(json_evento,'$." . $name . "')) = '" . $param[$name] . "'")->limit(10)->orderby('ideventos', 'desc')->get();
		return $response->withJson($data);

	}





}
