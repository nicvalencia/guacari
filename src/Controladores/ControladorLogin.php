<?php

namespace App\Controladores;

use DI\Container;
use App\Modelos\ModeloUsuario as Usuario; // para usar el modelo de usuario
use App\Modelos\ModeloUserClientes as UserClientes;
use App\Modelos\ModeloClientes as Clientes;

use Slim\Views\Twig; // Las vistas de la aplicación
use Slim\Router; // Las rutas de la aplicación
use Respect\Validation\Validator as v; // para usar el validador de Respect
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Illuminate\Support\Facades\DB;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Psr\Container\ContainerInterface;
use Slim\Flash\Messages;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * Clase de controlador para el usuario de la aplicación
 */

class ControladorLogin
{

	protected $view;
	// objeto de la clase Router
	protected $router;
	protected $flash;
	protected $translator;
	protected $sentinel;


	/**
	 * Constructor de la clase Controller     
	 * @param type Slim\Router $router - Ruta
	 */
	/*public function __construct( Router $router)
	   {
		   
		   $this->router = $router;
	   }*/

	protected $container;
	public function __construct(Container $container, TranslatorInterface $translator, Sentinel $sentinel)
	{
		$this->container = $container;
		$this->flash = new Messages;
		$this->translator = $translator;
		$this->sentinel = $sentinel;

	}

	/*-- Funciones del CRUD --*/
	public function index(Request $request, Response $response, $args)
	{
		$param = $request->getParsedBody();

		return Twig::fromRequest($request)->render($response, 'template_login.twig');
	}

	public function permisos(Request $request, Response $response, $args)
	{
		$param = $request->getParsedBody();
		return Twig::fromRequest($request)->render($response, 'no_permisos.twig');
	}


	public function login(Request $request, Response $response, $args)
	{

		$param = $request->getParsedBody();
		$routeParser = RouteContext::fromRequest($request)->getRouteParser();
		//Sentinel::logout();

		try {
			
			$data = Sentinel::authenticate(
				array(
					'email' => $param['email'],
					'password' => $param['passwd']
				)
			);

			if (empty($data->id)) {
				$this->flash->addMessage('error', 'La contraseña es incorrectas');
				$url = $routeParser->urlFor('Login');
				
				return $response->withStatus(200)->withRedirect($url);

			} else {
				$rol = Sentinel::findById($data->id)->roles()->first();		
				
				$_SESSION['idusuario'] = $data->id;
				$_SESSION['rol'] = $rol->id;
				$_SESSION['rol_name'] = $rol->name;
				$_SESSION['nombre'] = $data->first_name;
				$_SESSION['email'] = $data->email;
				
				$url = $routeParser->urlFor('Home');
				return $response->withStatus(200)->withRedirect($url);

			}

		} catch (\Cartalyst\Sentinel\Checkpoints\NotActivatedException $e) {
			$error = $this->translator->trans($e->getMessage());
			$url = $routeParser->urlFor('Login');
			$this->flash->addMessage('error', $error);
			return $response->withRedirect($url);
		} catch (\Cartalyst\Sentinel\Checkpoints\ThrottlingException $e) {
			$error = $this->translator->trans($e->getMessage());
			$delay = round($e->getDelay() / 60);
			$this->flash->addMessage('error', "Tu cuenta fue bloqueda durante {$delay} minuto(s) por que recibimos demasiados intentos fallidos.");
			$url = $routeParser->urlFor('Login');
			return $response->withRedirect($url);
		}

	}

	public function salir(Request $request, Response $response, $args)
	{
		Sentinel::logout();
		$routeParser = RouteContext::fromRequest($request)->getRouteParser();
		$url = $routeParser->urlFor('Login');
		return $response->withStatus(200)->withRedirect($url);
	}


	public function index_new_pass(Request $request, Response $response, $args)
	{

		return Twig::fromRequest($request)->render($response, 'usuarios/template_login_reset.twig');
	}

	function generarContrasenaSegura($length = 8)
	{
		return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()'), 0, $length);
	}

	public function new_pass(Request $request, Response $response, $args)
	{
		$param = $request->getParsedBody();
		$routeParser = RouteContext::fromRequest($request)->getRouteParser();

		$user = Sentinel::findByCredentials(['login' => $param['email']]);
		if ($user) {
			$contra = $this->generarContrasenaSegura(8);
			Sentinel::update($user, ['password' => $contra]);

			//envia correo con la contraseña
			try {
				$this->container->get('mailer')->sendMessage('emails/restaurar_contrasena.twig', ['pass' => $contra], function ($message) use ($param ) {
					$message->setTo($param["email"], "Restaurar contraseña");
					$message->setSubject('Recordatorio de contraseña ');
				});

			} catch (\Throwable $th) {
				//throw $th;
			}


			$url = $routeParser->urlFor('Login');
			return $response->withStatus(200)->withJson([
				'succes' => true,
				'tipo' => 'success',
				'message' => 'El email fue enviado con éxito, por favor verifique la bandeja de entrada de su correo electrónico.',
				'redirect' => 1,
				'promp' => 1,
				'url_redirect' => $url
			]);

		} else {

			//$this->flash->addMessage('error', "El email falló, por favor verifique su conexión."); 
			$url = $routeParser->urlFor('Login');
			//return $response->withRedirect($url);
			return $response->withStatus(200)->withJson([
				'succes' => true,
				'tipo' => 'error',
				'promp' => 1,
				'message' => 'Proceso fallo<br>No se encontro el correo en el sistema, por favor verifique su correo electrónico.',
				'redirect' => 1,
				//'url_redirect' => $url
			]);
		}
	}

	public function template_entry_new_pass(Request $request, Response $response, $args)
	{

		return Twig::fromRequest($request)->render($response, 'template_entry_new_pass.twig', ['args' => $args]);
	}

	public function active_pass_reminder(Request $request, Response $response, $args)
	{

		$param = $request->getParsedBody();
		$routeParser = RouteContext::fromRequest($request)->getRouteParser();
		$url = $routeParser->urlFor('Login');
		$sentinelUser = Sentinel::findById($param['iduser']);

		if (v::keyValue('password_confirmation', 'equals', 'passwd')->validate($param)) {
			if (Sentinel::getReminderRepository()->exists($sentinelUser)) {
				if ($reminder = Sentinel::getReminderRepository()->complete($sentinelUser, $param['code'], $param['passwd'])) {

					//envia correo de activacion
					$this->container->get('mailer')->sendMessage('emails/cambio_contrasena.twig', ['user' => $reminder], function ($message) use ($param, $sentinelUser) {
						$message->setTo($sentinelUser->email, $sentinelUser->first_name);
						$message->setSubject('Cambio de contraseña exítoso');
					});

					return $response->withJson([
						'succes' => true,
						'tipo' => 'success',
						'message' => 'El cambio de contraseña se realizo con éxito',
						'promp' => 1,
						'redirect' => 1,
						'url_redirect' => $url
					]);
				} else {
					return $response->withJson([
						'succes' => false,
						'tipo' => 'error',
						'message' => 'El cambio de contraseña no se pudo completar por favor verifica el link enviado a tu correo',
						'promp' => 1,
						'redirect' => 1,
						'url_redirect' => $url
					]);
				}
			} else {
				return $response->withJson([
					'succes' => false,
					'tipo' => 'error',
					'message' => 'No se ha generado una solicitud para cambio de contraseña para este correo electronico',
					'promp' => 1,
					'redirect' => 1,
					'url_redirect' => $url
				]);
			}
		} else {
			return $response->withJson([
				'succes' => false,
				'tipo' => 'error',
				'message' => 'Las contraseñas no coinciden',
				'redirect' => 1
			]);
		}
	}


}