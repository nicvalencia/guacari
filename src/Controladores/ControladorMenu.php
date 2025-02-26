<?php

namespace App\Controladores;

use App\Modelos\ModeloUsuario as Usuario;
use App\Modelos\ModeloMenu as Menu; // para usar el modelo de usuario
use Slim\Views\Twig; // Las vistas de la aplicación
use Slim\Router; // Las rutas de la aplicación
use Respect\Validation\Validator as v; // para usar el validador de Respect
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Database\Capsule\Manager as DB;


/**
 * Clase de controlador para el usuario de la aplicación
 */

class ControladorMenu extends \Twig\Extension\AbstractExtension
{

	protected $view;
	// objeto de la clase Router
	protected $router;


	public function getFunctions()
	{

		return [
			new \Twig\TwigFunction('menu_list', array($this, 'obtener_padres')),
			new \Twig\TwigFunction('menubot', array($this, 'option_asistentes'))
		];

	}


	public function option_asistentes()
	{

		$grupos = DB::table('social_groups')
			->select('social_groups.name', 'social_groups.id')
			->join('group_socials_user', 'group_socials_user.social_groups_id', '=', 'social_groups.id')
			->when(
				$_SESSION['rol'] != 1,
				function ($query) {
					$query->where('group_socials_user.users_id', $_SESSION['idusuario']);
				}
			)
			->distinct()
			->get();

		$html = '<a class="nav-link" href="javascript:void(0)">		
		<i class="fas fa-robot menu-icon" style="margin-left: 6px;"></i> BOT <i class="fa-solid fa-caret-down menu-icon" style="margin-left: 6px;"></i>
		
				 
					</a>';
		foreach ($grupos as $grupo) {
			$html .= '
			<a href="' . $_SESSION['urlpath'] . "/configuracion/grupos/" . $grupo->id . '" class="nav-link">
				<i class="fa-solid fa-people-group menu-icon"></i> ' . $grupo->name . '						
			</a>';
		}
		#Retorna un html		
		return $html;

	}

	public function getChildren($data, $line)
	{
		$children = [];
		foreach ($data as $line1) {
			if ($line['id'] == $line1['parent']) {
				$children = array_merge($children, [array_merge($line1, ['submenu' => $this->getChildren($data, $line1)])]);
			}
		}
		return $children;
	}


	public function obtener_padres()
	{

		$html = "";
		if (Sentinel::check()) {
			$rol = Sentinel::findRoleById($_SESSION['rol']);
			$opt_menu = array_keys($rol->permissions);
			//print_r($opt_menu);
			$result = Menu::wherein('menu_aplicacion.url', $opt_menu)
				->where('menu_aplicacion.parent', '0')
				->where('menu_aplicacion.view', '1')
				->orderby('pid', 'ASC')
				->get();

			#obtenemos los valores devueltos por la consulta                    
			foreach ($result as $row) {

				if (!empty($this->obtener_hijos($row->idmenus))) {
					$html .= '<li class="menu-item " id="' . str_replace(' ', '', $row->title) . '">
					<a class="menu-link menu-toggle" href="javascript:void(0)">
					<i class="menu-icon ' . $row->icon_class . '"></i>
					<div  data-i18n="' . $row->title . '">' . $row->title . '</div>
					</a>';

					$html .= $this->obtener_hijos($row->idmenus);

				} else {

					$html .= '
					<a href="' . $_SESSION['urlpath'] . "/" . $row->url . '" class="nav-link">
						<i class="menu-icon ' . $row->icon_class . '"></i> ' . $row->title . '						
					</a>
				';
				}
				$html .= '</li>';
			}
		}
		#Retorna un html
		return $html;
	}

	function obtener_hijos($padre)
	{

		$rol = Sentinel::findRoleById($_SESSION['rol']);
		$opt_menu = array_keys($rol->permissions);

		$html = "";
		#Consulta para obtener los hijos
		$menu2 = Menu::where('menu_aplicacion.parent', $padre)
			->wherein('url', $opt_menu)
			->where('menu_aplicacion.view', '1')
			->orderby('parent', 'ASC')
			->get();
		if (count($menu2) > 0) {
			$html .= '<ul class="menu-sub">';
			foreach ($menu2 as $row2) {
				if (empty($this->obtener_hijos($row2->idmenus))) {
					$html .= '<li class="menu-item"><a class="menu-link" href="' . $_SESSION['urlpath'] . "/" . $row2->url . '"><div class="hijos" data-i18n="' . $row2->title . '">' . $row2->title . '</div></a></li>';
				} else {
					$html .= '<li class="menu-item"><a class="menu-link" href="#"><div class="hijos" data-i18n="' . $row2->title . '">' . $row2->title . '</div></a></li>';
					$html .= $this->obtener_hijos($row2->idmenus);
				}
			}
			$html .= '</ul>';
		}
		#Retorna html
		return $html;
	}


	public function update_position($request, $response, $args)
	{
		$data = $request->getParsedBody();
		$menu = new Menu;
		Menu::where('idmenus', $data['idreg'])->update(['pid' => $data['pos'], 'parent' => $data['padre']]);
		return $response->withJson([
			'succes' => true,
			'message' => 'Los datos se guardaron con exito',
			'tipo' => 'success',
			'data' => $param
		]);
	}



}