<?php
namespace App\Controladores;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Selective\BasePath\BasePathMiddleware;
use Illuminate\Database\Connection;
use Slim\Exception\NotFoundException;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\App;
use Slim\Routing\RouteContext;
use Slim\Routing\RouteCollectorProxy;
use App\Controladores\HeaderController;


$app->get('/no_permisos', "ControladorLogin:permisos")->setName('Permisos');
$app->get('/regenerate_token', "ControladorToken:regenerate_token")->setName('Excepciones');
$app->get('/get_ip', "ControladorZonasCalor:convertir_direccion")->setName('Token');
$app->get('/formulario/{cliente}/{experiencia}/{form:[0-9]+}', "ControladorExperiencias:show_form")->setName('Excepciones');
$app->get('/json_swagger', "ControladorApi:genera_json_swagger")->setName('Excepciones');
$app->get('/mongodb', "ControladorApiMongoDB:genera_json_swagger")->setName('Excepciones');

$app->get('/', "ControladorIndex:index")->setName('Home');

$app->group('/login', function (RouteCollectorProxy $app) {
    $app->get('/ingreso', "ControladorLogin:index")->setName('Login');
    $app->post('/ingreso', "ControladorLogin:login")->setName('Ingreso');
    $app->get('/logout', "ControladorLogin:salir")->setName('Salida');

    $app->get('/new_pass', "ControladorLogin:index_new_pass")->setName('Newpass');
    $app->post('/generate_pass', "ControladorLogin:new_pass")->setName('Excepciones');
    $app->get('/reset_pass/{code}/{iduser:[0-9]+}', "ControladorLogin:template_entry_new_pass")->setName('Excepciones');
    $app->post('/change_pass', "ControladorLogin:active_pass_reminder")->setName('Excepciones');
})->add('csrf');


$app->group('/usuarios', function (RouteCollectorProxy $app) {
    $app->get('', "ControladorUsuario:index")->setName('Usuarios');
    $app->post('/add_usuario', "ControladorUsuario:save_edit")->setName('AddUsers');
    $app->get('/edit_user/{iduser:[0-9]+}', "ControladorUsuario:get_user")->setName('EditUser');
    $app->get('/all_usuario', "ControladorUsuario:all_usuarios");
    $app->get('/lista_usuario', "ControladorUsuario:lista_usuarios");
    $app->get('/activate/{code}/{iduser}', "ControladorUsuario:activar_users")->setName('Excepciones');
    $app->get('/fetch_user/{idusuario}', "ControladorUsuario:busca_usuario")->setName('Excepciones');
    $app->post('/change_state', "ControladorUsuario:update_state")->setName('Changestate');
})->add('csrf');


$app->group('/configuracion', function (RouteCollectorProxy $app) {
    $app->get('', "ControladorMenuBot:index")->setName('Configuracion');
    
    $app->post('/save_msg', "ControladorMenuBot:config_msginicial")->setName('Save_msg');
    $app->get('/grupos/{idsocial:[0-9]+}', "ControladorGrupos:list_bot_grupos")->setName('Listgrupos');
    
})->add('csrf');

$app->group('/grupos', function (RouteCollectorProxy $app) {
    $app->get('', "ControladorGrupos:index")->setName('Grupos');
    $app->get('/all', "ControladorGrupos:all_social_groups")->setName('AllGrupos');
    $app->get('/get_ticket/{idticket:[0-9]+}', "ControladorGrupos:get_ticket")->setName('AllTickets');
    $app->post('/save_edit', "ControladorGrupos:save_edit")->setName('SaveGrupos');
    
})->add('csrf');


$app->group('/bot', function (RouteCollectorProxy $app) {
    $app->get('', "ControladorBot:index")->setName('BOT');
    $app->get('/list_bot', "ControladorBot:list_bot")->setName('Excepciones');
    
    $app->post('/start', "ControladorBot:bot_funcionalidad")->setName('Excepciones');
    $app->get('/start', "ControladorBot:bot_funcionalidad")->setName('Excepciones');
    $app->get('/plantilla', "ControladorBot:plantilla")->setName('Excepciones');
    //$app->get('/configuracion', "ControladorBot:configuracion_bot");  

    /**menus dinamicos del bot */
    $app->get("/create_bot", "ControladorMenuBot:index")->setName('SaveMenu');
    $app->post("/add_menu", "ControladorMenuBot:upload_files_audios_images")->setName('AddMenu');
    $app->post("/save_menu", "ControladorMenuBot:guarda_menu")->setName('SaveMenu');
    $app->get("/get_item/{iditem:[0-9]+}", "ControladorMenuBot:get_item")->setName('Excepciones');
    $app->post("/menu_del", "ControladorMenuBot:delete_menu")->setName('BotDeletemenu');
    $app->post("/editar_menu", "ControladorMenuBot:edit_menu")->setName('BotEditmenu');
   
    $app->post('/pruebas', "ControladorBot:prueba")->setName('Excepciones');
})->add('csrf');

$app->group('/api', function (RouteCollectorProxy $app) {
    $app->get("/pruebas", "ControladorApi:pruebas")->setName('Api');
  

    

});

