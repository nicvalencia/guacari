<?php

namespace App\Controladores;

use DI\Container;
use App\Modelos\ModeloUsuarios as Usuarios;
use App\Modelos\ModeloEstados as Estados;
use App\Modelos\ModeloMenuBot as MenuBot;

use Slim\Views\Twig; // Las vistas de la aplicación
use Slim\Router; // Las rutas de la aplicación
use Respect\Validation\Validator as v; // para usar el validador de Respect
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Illuminate\Database\Capsule\Manager as DB;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Slim\Routing\RouteContext;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;



/**
 * Clase de controlador para el usuario de la aplicación
 */

class ControladorGrupos
{

	protected $view;
	// objeto de la clase Router
	protected $router;

	protected $container;
	public function __construct(Container $container)
	{
		$this->container = $container;
	}



	/*-- Funciones del CRUD --*/
	public function index(Request $request, Response $response, $args)
	{
		$estados = DB::table('state')->get();
		return Twig::fromRequest($request)->render($response, 'grupo_poblacionales/grupos.twig', ["estados" => $estados]);
	}

	public function all_social_groups(Request $request, Response $response, $args)
	{

		$total = DB::table('social_groups')->count();
		$data = DB::table('social_groups')->select(
			'social_groups.name',
			'social_groups.id',
			'state.description as estado',
			'state.id as idestado'
		)->join('state', 'state.id', '=', 'social_groups.state_id')
			->when(
				$_GET['columns'][0]['search']['value'] != '',
				function ($q) {
					return $q->where('social_groups.name', 'LIKE', "%" . $_GET['columns'][0]['search']['value'] . "%");
				}
			)

			->when(
				$_GET['columns'][1]['search']['value'] != '',
				function ($q) {
					return $q->where('state.description', 'LIKE', "%" . $_GET['columns'][1]['search']['value'] . "%");
				}
			)

			->orderby('social_groups.name', 'ASC')
			->when(
				$_GET['length'] >= 1,
				function ($q) {
					return $q->offset($_GET['start'])->limit($_GET['length']);
				}
			)
			->get();


		return $response->withStatus(200)->withJson([
			"draw" => $_GET['draw'],
			"recordsTotal" => $total,
			"recordsFiltered" => $_GET['draw'],
			'data' => $data
		]);

	}

	public function get_social_groups(Request $request, Response $response, $args)
	{
		$ticket = DB::table('social_groups')->find($args['idticket']);
		return $response->withStatus(200)->withJson($ticket);
	}

	public function save_edit(Request $request, Response $response, $args)
	{

		$param = $request->getParsedBody();

		if ($param['idgrupo']) {
			if (!empty($param['estado'])) {
				DB::table('social_groups')->where('id', $param['idgrupo'])->update([
					'name' => $param['name_grupo'],
					'state_id' => $param['estado']
				]);

				return $response->withStatus(200)->withJson([
					'succes' => true,
					'tipo' => 'success',
					'message' => EDIT
				]);
			} else {
				return $response->withStatus(200)->withJson([
					'succes' => false,
					'redirect' => 1,
					'tipo' => 'error',
					'message' => "Debes seleccionar un estado"
				]);
			}

		} else {

			if (!empty($param['estado'])) {

				DB::table('social_groups')->insert([
					'name' => $param['name_grupo'],
					'state_id' => $param['estado']
				]);

				return $response->withStatus(201)->withJson([
					'succes' => true,
					'tipo' => 'success',
					'message' => SAVE
				]);


			} else {
				return $response->withStatus(200)->withJson([
					'succes' => false,
					'redirect' => 1,
					'tipo' => 'error',
					'message' => "Debes seleccionar un estado"
				]);
			}

		}

	}


	public function buildMenu($items, $class = 'dd-list')
	{
		$result = null;


		$html = "<ol class=\"" . $class . "\" id=\"menu-id\">";

		$icon = [
			"1" => '<i class="fa-solid fa-file-word fa-2x"></i>',
			"2" => '<i class="fa-solid fa-volume-high fa-2x"></i>',
			"3" => '<i class="fa-solid fa-images fa-2x"></i>',
			"4" => '<i class="fa-solid fa-file-pdf fa-2x"></i>',
			"5" => '<i class="fa fa-film fa-2x"></i>',
			"6" => '<i class="fa fa-film fa-2x"></i>'
		];

		foreach ($items as $key => $value) {
			$html .= '<li class="dd-item dd3-item" data-id="' . $value['id'] . '" >
					<div class="dd-handle dd3-handle">
                    <img src="' . $_SESSION['urlpath'] . '/assets/img/group.svg" alt="icon" class="icon" />
                    </div>

                    <div class="col-md-2 text-right icon_align"> 
						<a href="#editModal" class="edit_toggle" data-id="' . $value['id'] . '"  data-toggle="modal"><i class="fa fa-pencil fa-2x"></i></a>
						<a href="#deleteModal" class="delete_toggle" data-id="' . $value['id'] . '" data-toggle="modal"><i class="fa fa-trash fa-2x"></i></a></span> 
					</div>

					<div class="dd3-content text_content mt-4">
					<div class="row"> 
					
						<div class="col-md-1 text-center p-0"> 
							'.$icon[$value['tipos_data_idtipos_data']].'
						</div>
						
					<div class="col-md-11 p-0"> 
					<span id="display-4 label_show' . $value['id'] . '" style="font-size: 17px;">' . $value['label'] . '</span> 
					<span id="text-muted" >Creado ' . $value['fecha'] . '</span> 
					</div>
					
						</div>
						
						
					
					</div>';
			if (array_key_exists('child', $value)) {
				$html .= $this->buildMenu($value['child'], 'child');
			}
			$html .= "</li>";
		}
		$html .= "</ol>";

		return $html;
	}


	public function list_bot_grupos(Request $request, Response $response, $args)
	{

		$ref = [];
		$items = [];

		$menu = MenuBot::select(
			'main_bot.id',
			'main_bot.title',
			'main_bot.description',
			'main_bot.parent',
			'main_bot.display_order',
			'main_bot.tipos_data_idtipos_data',
			'main_bot.created_at'
		)
			->join('group_socials_user', 'group_socials_user.social_groups_id', '=', 'main_bot.social_groups_id')
			->where('group_socials_user.users_id', $_SESSION['idusuario'])
			->where('group_socials_user.social_groups_id', $args['idsocial'])
			->orderBy('display_order', 'asc')
			->distinct()
			->get();

		foreach ($menu as $data) {

			$thisRef = &$ref[$data->id];

			$thisRef['parent'] = $data->parent;
			$thisRef['label'] = $data->title;
			$thisRef['id'] = $data->id;
			$thisRef['tipos_data_idtipos_data'] = $data->tipos_data_idtipos_data;
			$thisRef['fecha'] = $data->created_at;

			if ($data->parent == 0) {
				$items[$data->id] = &$thisRef;
			} else {
				$ref[$data->parent]['child'][$data->id] = &$thisRef;
			}

		}

		$listado = $this->buildMenu($items);
		$inf = ['menu' => $listado, 'idgroup' => $args['idsocial']];
		return Twig::fromRequest($request)->render($response, 'bot/bot.twig', $inf);

	}

	public function save_menu(Request $request, Response $response, $args)
	{
		$param = $request->getParsedBody();
		$id = $param['id'];
		$label = $param['label'];
		$parent = $param['parent'];
		$tipos_data_idtipos_data = $param['tipos_data_idtipos_data'];
		$display_order = $param['display_order'];
		$title = $param['title'];
		$description = $param['description'];
		$created_at = $param['created_at'];
		$updated_at = $param['updated_at'];
		/*$menu = new MenuBot();
						  $menu->id = $id;
						  $menu->label = $label;
						  $menu->parent = $parent;
						  $menu->tipos_data_idtipos_data = $tipos_data_idtipos_data;
						  $menu->display_order = $display_order;
						  $menu->title = $title;
						  $menu->description = $description;
						  $menu->created_at = $created_at;
						  $menu->updated_at = $updated_at;
						  $menu->save();*/
		return $response->withStatus(200)->withJson([
			'succes' => true,
			'tipo' => 'success',
			'message' => SAVE
		]);
	}


}