<?php

namespace App\Controladores;

use DI\Container;
use App\Modelos\ModeloUsuarios as Usuarios;
use App\Modelos\ModeloEstados as Estados;


use Slim\Views\Twig; // Las vistas de la aplicación
use Slim\Router; // Las rutas de la aplicación
use Respect\Validation\Validator as v; // para usar el validador de Respect
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Illuminate\Database\Capsule\Manager as DB;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Cartalyst\Sentinel\Activations\EloquentActivation;
use Slim\Routing\RouteContext;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;


/**
 * Clase de controlador para el usuario de la aplicación
 */

class ControladorUsuario
{

	protected $view;
	// objeto de la clase Router
	protected $router;
	protected $messages;
	protected $decoded;
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


	/*-- Funciones del CRUD --*/
	public function index(Request $request, Response $response, $args)
	{
		$estados = Estados::all();
		$roles = Sentinel::getRoleRepository()->all();
		$usuarios = Usuarios::select('email', 'first_name', 'last_name', 'roles.name as role', 'users.id', 'role_users.role_id as idrol', 'activations.completed')
			->join('role_users', 'role_users.user_id', '=', 'users.id')
			->join('roles', 'roles.id', '=', 'role_users.role_id')
			->leftjoin('activations', 'activations.user_id', '=', 'users.id')
			->get();

		$grupos = DB::table('social_groups')->select('name', 'id')->get();

		return Twig::fromRequest($request)->render($response, 'usuarios/usuarios.twig', [
			'estados' => $estados,
			'roles' => $roles,
			'usuarios' => $usuarios,
			'grupos' => $grupos
		]);
	}

	public function get_user(Request $request, Response $response, $args)
	{
		$usuarios = Usuarios::select('email', 'first_name', 'last_name', 'roles.name as role', 'users.id', 'role_users.role_id as idrol')
			->join('role_users', 'role_users.user_id', '=', 'users.id')
			->join('roles', 'roles.id', '=', 'role_users.role_id')
			->where('users.id', $args['iduser'])
			->first();

		$grupos_seleted = DB::table('social_groups')->select('group_socials_user.social_groups_id', 'social_groups.id', 'social_groups.name')
			->join('group_socials_user', 'group_socials_user.social_groups_id', '=', 'social_groups.id')
			->where('group_socials_user.users_id', $args['iduser'])
			->get();

		$state = EloquentActivation::where('user_id', $args['iduser'])->where('completed', true)->first(['completed']);

		return $response->withStatus(200)->withJson(['data_user' => $usuarios, 'grupos_seleted' => $grupos_seleted, 'estado' => $state]);
	}


	public function save_edit(Request $request, Response $response, $args)
	{

		$param = $request->getParsedBody();
		$routeParser = RouteContext::fromRequest($request)->getRouteParser();

		if ($param['idusuario']) {

			$user = Sentinel::findById($param['idusuario']);

			$infousu['first_name'] = $param['nombre'];
			$infousu['last_name'] = $param['apellido'];
			$infousu['email'] = $param['email'];

			if ($param['passwd']) {
				$infousu['password'] = $param['passwd'];
			}
			Sentinel::update($user, $infousu);

			//actualizacion del rol
			$rol = Sentinel::findById($user->id)->roles()->first();
			if ($param['rol'] != $rol->id) {
				$rol->users()->detach($user);
				$new_rol = Sentinel::findRoleById($param['rol']);
				$new_rol->users()->attach($user);
			}


			//deshabilita o habilita el usuario
			if ($param['estado'] == 2) {
				$user = Sentinel::findById($param['idusuario']);
				Sentinel::getActivationRepository()->remove($user);
			} else {
				$user = Sentinel::findById($param['idusuario']);
				Sentinel::getActivationRepository()->remove($user);
				$activation = Sentinel::getActivationRepository()->create($user);
				Sentinel::getActivationRepository()->complete($user, $activation->code);
				//EloquentActivation::where('user_id', $param['idusuario'])->update(['completed' => true,'completed_at' => Carbon::now()]);
			}

			//actualiza los grupos del usuario
			DB::table('group_socials_user')->where('users_id', $param['idusuario'])->delete();
			if (!empty($param['listgrupos'])) {
				foreach ($param['listgrupos'] as $id=>$grupo) {
					DB::table('group_socials_user')->insert([
						'social_groups_id' => $id,
						'users_id' => $param['idusuario']
					]);
				}
			}


			return $response->withStatus(200)->withJson([
				'succes' => true,
				'tipo' => 'success',
				'message' => 'Datos actualizados',
				'data' => $param['grupos']

			]);

		} else {

			$exist = Usuarios::select('email')->where('email', $param['email'])->first();
			if (empty($exist->email)) {
				$user = Sentinel::register([
					'email' => $param['email'],
					'password' => $param['passwd'],
					'last_name' => $param['apellido'],
					'first_name' => $param['nombre']
				]);


				$role = Sentinel::findRoleById($param['rol']);
				$role->users()->attach($user);

				if ($param['estado'] == 2) {
					Sentinel::getActivationRepository()->remove($user);
				} else {
					$activation = Sentinel::getActivationRepository()->create($user);
					Sentinel::getActivationRepository()->complete($user, $activation->code);
				}

				if (!empty($param['grupos'])) {
					foreach ($param['grupos'] as $grupo) {
						DB::table('group_socials_user')->insert([
							'social_groups_id' => $grupo,
							'users_id' => $user->id
						]);
					}
				}

				return $response->withStatus(201)->withJson([
					'succes' => true,
					'tipo' => 'success',
					'message' => 'El usuario fue creado',
					'close_modal' => 'modal_edit_usuarios'
				]);

			} else {

				return $response->withJson([
					'succes' => false,
					'tipo' => 'info',
					'message' => 'El correo con el que intentas crear la cuenta ya se encuentra registrado'
				]);
			}
		}
	}



}