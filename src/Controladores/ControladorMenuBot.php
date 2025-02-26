<?php

namespace App\Controladores;
use DI\Container;
use App\Modelos\ModeloMenuBot as MenuBot; // para usar el modelo de usuario
use App\Modelos\ModeloConfiguracion as Configuracion;

use Slim\Views\Twig; // Las vistas de la aplicación
use Slim\Router; // Las rutas de la aplicación
use Respect\Validation\Validator as v; // para usar el validador de Respect
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;


use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Capsule\Manager as DB;
/**
 * Clase de controlador para el usuario de la aplicación
 */

class ControladorMenuBot
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
    /*-- Funciones del CRUD --*/
    /* public function buildMenu($items, $class = 'dd-list')
     {
         $result = null;


         $html = "<ol class=\"" . $class . "\" id=\"menu-id\">";

         $icon = [
             "1" => '<i class="fa  fa-file-text-o fa-2x"></i>',
             "2" => '<i class="fa fa-file-audio-o fa-2x"></i>',
             "3" => '<i class="fa fa-file-image-o fa-2x" alt="Imagen"></i>',
             "4" => '<i class="fa fa-file-pdf-o"></i>',
             "5" => '<i class="fa fa-film"></i>',
             "6" => '<i class="fa fa-film"></i>'
         ];

         foreach ($items as $key => $value) {
             $html .= '<li class="dd-item dd3-item" data-id="' . $value['id'] . '" >
                     <div class="dd-handle dd3-handle">
                     <img src="assets/img/group.svg" alt="icon" class="icon" />
                     </div>

                     <div class="col-md-2 text-right icon_align"> 
                         <a href="#editModal" class="edit_toggle" data-id="' . $value['id'] . '"  data-toggle="modal"><i class="fa fa-pencil fa-2x"></i></a>
                         <a href="#deleteModal" class="delete_toggle" data-id="' . $value['id'] . '" data-toggle="modal"><i class="fa fa-trash fa-2x"></i></a></span> 
                     </div>

                     <div class="dd3-content text_content mt-4">
                     <div class="row"> 
                     <div class="col-md-12"> 
                     <div class="col-md-11 mt-3"> 
                     <span id="display-4 label_show' . $value['id'] . '" style="font-size: 17px;">' . $value['label'] . '</span> 
                     <span id="text-muted" >Creado ' . $value['fecha'] . '</span> 
                         </div>
                         <div class="col-md-2 text-center"> 
                             '.$icon[$value['tipos_data_idtipos_data']].'
                         </div>
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
     }*/


    public function index(Request $request, Response $response, $args)
    {

        $config = Configuracion::join('type_message','type_message.id','=','configuration.type_message_id')
        ->where('configuration.id', 1)
        ->get();

        return Twig::fromRequest($request)->render($response, 'bot/config.twig', [
            'config' => $config
        ]);

    }

    public function get_item(Request $request, Response $response, $args)
    {
        $id = $args['iditem'];
        $data = MenuBot::where('id', $id)->first();
        return $response->withJson($data);
    }

    public function edit_menu($request, $response, $args)
    {
        $param = $request->getParsedBody();
        MenuBot::where('id', $param['idmenus'])->update(['title' => $param['title']]);
        return $response->withJson([
            'succes' => true,
            'message' => SAVE,
            'tipo' => 'success',
            'toast' => 1,
            'close_modal' => 'editModal',
            'data' => $param
        ]);
    }

    public function delete_menu($request, $response, $args)
    {
        $param = $request->getParsedBody();

        $hijos = MenuBot::where('parent', $param['id'])->get();

        if (count($hijos) > 0) {
            return $response->withJson([
                'succes' => false,
                'message' => 'No se pueden eliminar items con dependencias',
                'tipo' => 'error',
                'data' => $param,
                'redirec' => '1',
            ]);
        } else {

            MenuBot::where('id', $param['id'])->delete();

            return $response->withJson([
                'succes' => true,
                'message' => 'Los datos se eliminaron con exito',
                'tipo' => 'success',
                'data' => $param
            ]);
        }

    }

    public function update_position($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $menu = new MenuBot;
        MenuBot::where('id', $data['idreg'])->update(['display_order' => $data['pos'], 'parent' => $data['padre']]);
        return $response->withJson([
            'succes' => true,
            'message' => 'Los datos se guardaron con exito',
            'tipo' => 'success'
        ]);
    }

    public function parseJsonArray($jsonArray, $parentID = 0)
    {
        $return = array();
        foreach ($jsonArray as $subArray) {
            $returnSubSubArray = array();
            if (isset($subArray->children)) {
                $returnSubSubArray = $this->parseJsonArray($subArray->children, $subArray->id);
            }

            $return[] = array('id' => $subArray->id, 'parentID' => $parentID);
            $return = array_merge($return, $returnSubSubArray);
        }
        return $return;
    }

    public function guarda_menu(Request $request, Response $response, $args)
    {
        $param = $request->getParsedBody();
        $inserdata = [];
        $data = json_decode($param['data']);
        $readbleArray = $this->parseJsonArray($data);
        $i = 0;

        //print_r($readbleArray);		

        foreach ($readbleArray as $row) {
            $i++;
            MenuBot::where('id', '=', $row['id'])->update(['parent' => $row['parentID'], 'display_order' => $i]);
            //$db->exec("update tbl_menu set parent = '".$row['parentID']."', sort = '".$i."' where id = '".$row['id']."' ");
        }

        return $response->withStatus(200)->withJson([
            'succes' => true,
            'message' => 'Los datos se guardaron con exito',
            'tipo' => 'success',
            'toast' => 1,
            'redirec' => '1'
        ]);

    }

    public function upload_files_audios_images(Request $request, Response $response, $args)
    {
        $param = $request->getParsedBody();
        $file = $param['imagen'];
        $name = $file['name'];
        $path = $file['tmp_name'];
        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['imagen'];

        if ($param['attach'] == "frmimagen") {

            if ($_FILES['imagen']['error'] != UPLOAD_ERR_NO_FILE) {
                $maxSize = UploadedFile::getMaxFilesize();

                if ($uploadedFile->getSize() <= $maxSize) {
                    $uploadedFile = $uploadedFiles['imagen'];
                    $content = base64_encode(file_get_contents($_FILES['imagen']['tmp_name']));
                    $menu = new MenuBot;
                    // asigna cada elemento del arreglo $atr con su columna en la tabla 
                    $nombre_archivo = pathinfo($uploadedFile->getClientFilename(), PATHINFO_FILENAME);
                    $menu->name_file = $nombre_archivo;
                    $menu->data_file = $content;
                    $menu->mime_type_file = $uploadedFile->getClientMediaType();
                    $menu->extension_file = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                    $menu->title = "Imagen " . $nombre_archivo;
                    $menu->parent = 0;
                    $menu->social_groups_id = $param['idgroup'];
                    $menu->tipos_data_idtipos_data = 3;
                    $menu->save(); //guarda el registro										


                    return $response->withJson([
                        'succes' => true,
                        'message' => 'Los datos se guardaron con exito',
                        'tipo' => 'success',
                        'data' => $param
                    ]);

                } else {
                    //si la imagen excede el tamaño permitido
                    return $response->withJson([
                        'succes' => false,
                        'tipo' => 'error',
                        'message' => 'El archivo excede el maximo permitido 8MB',
                        'data' => null,
                        'redirec' => 1
                    ]);

                }

            } else {
                //si no selecciono imagen
                return $response->withJson([
                    'succes' => false,
                    'tipo' => 'error',
                    'message' => 'Por favor selecciona una imagen',
                    'data' => null
                ]);

            }

        } elseif ($param['attach'] == 'frmvideo') {
            $uploadedFile = $uploadedFiles['video'];
            if ($_FILES['video']['error'] != UPLOAD_ERR_NO_FILE) {
                if (round($_FILES['imagen']['size'] / 1024 / 1024) < 16) {
                    $content = base64_encode(file_get_contents($_FILES['video']['tmp_name']));
                    /*cambia el nombre del archivo */
                    $nom_video = bin2hex(random_bytes(32)) . '.' . pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
                    $menu = new MenuBot;
                    $menu->name_file = pathinfo($uploadedFile->getClientFilename(), PATHINFO_FILENAME);
                    $menu->data_file = $content;
                    $menu->mime_type_file = $uploadedFile->getClientMediaType();
                    $menu->extension_file = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                    $menu->title = 'Video ' . $nom_video;
                    $menu->tipos_data_idtipos_data = 5;
                    $menu->social_groups_id = $param['idgroup'];
                    $menu->save(); //guarda el registro

                    return $response->withStatus(200)->withJson([
                        'succes' => true,
                        'message' => 'Los datos se guardaron con exito',
                        'tipo' => 'success',
                        'data' => $param
                    ]);

                } else {
                    //si la imagen excede el tamaño permitido
                    return $response->withJson([
                        'succes' => false,
                        'tipo' => 'error',
                        'message' => 'El archivo excede el maximo permitido 1.5MB',
                        'data' => null
                    ]);

                }
            } else {
                //si no selecciono video
                return $response->withJson([
                    'succes' => false,
                    'tipo' => 'error',
                    'message' => 'Por favor debes adjuntar un video',
                    'data' => null
                ]);

            }

        } elseif ($param['attach'] == 'frmaudio') {

            $uploadedFile = $uploadedFiles['audio'];
            if ($_FILES['audio']['error'] != UPLOAD_ERR_NO_FILE) {
                if (round($_FILES['imagen']['size'] / 1024 / 1024) < 16) {
                    $content = base64_encode(file_get_contents($_FILES['audio']['tmp_name']));
                    $menu = new MenuBot;

                    /*cambia el nombre del archivo */
                    $nom_audio = bin2hex(random_bytes(32)) . '.' . pathinfo($_FILES['audio']['name'], PATHINFO_EXTENSION);
                    $tmp_name = $_FILES["audio"]["tmp_name"];

                    // asigna cada elemento del arreglo $atr con su columna en la tabla 
                    $nombre_archivo = pathinfo($uploadedFile->getClientFilename(), PATHINFO_FILENAME);
                    $menu->name_file = $nombre_archivo;
                    $menu->data_file = $content;
                    $menu->mime_type_file = $uploadedFile->getClientMediaType();
                    $menu->extension_file = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                    $menu->title = "Archivo " . $nombre_archivo;
                    $menu->parent = 0;
                    $menu->social_groups_id = $param['idgroup'];
                    $menu->tipos_data_idtipos_data = 2;
                    $menu->save(); //guarda el registro		



                    return $response->withJson([
                        'succes' => true,
                        'message' => 'Los datos se guardaron con exito',
                        'tipo' => 'success',
                        'data' => $param
                    ]);

                } else {
                    //si la imagen excede el tamaño permitido
                    return $response->withJson([
                        'succes' => false,
                        'tipo' => 'error',
                        'message' => 'El archivo excede el maximo permitido 1.5MB',
                        'data' => null
                    ]);

                }
            } else {
                //si no selecciono audio
                return $response->withJson([
                    'succes' => false,
                    'tipo' => 'error',
                    'message' => 'Por favor debes adjuntar un audio',
                    'data' => null
                ]);

            }

        } elseif ($param['attach'] == 'frmtexto') {
            $menu = new MenuBot;
            // asigna cada elemento del arreglo $atr con su columna en la tabla usuarios
            $menu->title = $param['title'];
            $menu->parent = 0;
            $menu->tipos_data_idtipos_data = 1;
            $menu->social_groups_id = $param['idgroup'];
            $menu->save(); //guarda el registro


            return $response->withJson([
                'succes' => true,
                'message' => 'Los datos se guardaron con exito',
                'tipo' => 'success',
                'data' => $param
            ]);

        } elseif ($param['attach'] == 'frmlabel') {
            $menu = new MenuBot;
            // asigna cada elemento del arreglo $atr con su columna en la tabla usuarios
            $menu->title = $param['title_lbl'];
            $menu->parent = 0;
            $menu->tipos_data_idtipos_data = 1;
            $menu->text_informativo = 1;
            $menu->social_groups_id = $param['idgroup'];

            $menu->save(); //guarda el registro


            return $response->withJson([
                'succes' => true,
                'message' => 'Los datos se guardaron con exito',
                'tipo' => 'success',
                'data' => $param
            ]);

        } elseif ($param['attach'] == 'frmlink') {
            $menu = new MenuBot;
            // asigna cada elemento del arreglo $atr con su columna en la tabla usuarios
            $menu->title = $param['texto_inf'];
            $menu->parent = 0;
            $menu->social_groups_id = $param['idgroup'];
            $menu->tipos_data_idtipos_data = 1;
            $menu->text_informativo = 1;
            $menu->save(); //guarda el registro
        } elseif ($param['attach'] == 'frmarchivo') {

            $uploadedFile = $uploadedFiles['archivo'];
            if ($_FILES['archivo']['error'] != UPLOAD_ERR_NO_FILE) {
                if (round($_FILES['archivo']['size'] / 1024 / 1024) < 100) {
                    $uploadedFile = $uploadedFiles['archivo'];
                    $content = base64_encode(file_get_contents($_FILES['archivo']['tmp_name']));
                    $menu = new MenuBot;
                    // asigna cada elemento del arreglo $atr con su columna en la tabla 
                    $nombre_archivo = pathinfo($uploadedFile->getClientFilename(), PATHINFO_FILENAME);
                    $nom_img = bin2hex(random_bytes(32)) . '.' . pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);
                    $tmp_name = $_FILES["archivo"]["tmp_name"];
                    $menu->name_file = $nombre_archivo;
                    $menu->data_file = $content;
                    $menu->mime_type_file = $uploadedFile->getClientMediaType();
                    $menu->extension_file = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                    $menu->title = "archivo " . $nombre_archivo;
                    $menu->parent = 0;
                    $menu->tipos_data_idtipos_data = 4;
                    $menu->social_groups_id = $param['idgroup'];


                    $menu->save(); //guarda el registro		


                    return $response->withJson([
                        'succes' => true,
                        'message' => 'Los datos se guardaron con exito',
                        'tipo' => 'success',
                        'data' => $param
                    ]);

                } else {
                    //si la imagen excede el tamaño permitido
                    return $response->withJson([
                        'succes' => false,
                        'tipo' => 'error',
                        'message' => 'El archivo excede el maximo permitido 1.5MB',
                        'data' => null
                    ]);

                }
            } else {
                //si no selecciono audio
                return $response->withJson([
                    'succes' => false,
                    'tipo' => 'error',
                    'message' => 'Por favor debes adjuntar un audio',
                    'data' => null
                ]);

            }

        }

    }


    public function config_msginicial($request, $response, $args)
    {

        $param = $request->getParsedBody();

        Configuracion::where('id', 1)->update([
            'text_info' => $param['text_info']
        ]);

        return $response->withJson([
            'succes' => true,
            'message' => SAVE,
            'tipo' => 'success'
        ]);

    }


}
