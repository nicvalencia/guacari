<?php
namespace App\Controladores;
use App\Modelos\ModeloMenuBot as MenuBotPlano;
use App\Modelos\ModeloConfiguracion as Configuracion;
use App\Modelos\ModeloGruposSociales as GruposSociales; 



use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Cache\DoctrineCache;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\File;
use Doctrine\Common\Cache\FilesystemCache;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;


class OnboardingConversation extends Conversation
{

    protected $arraypadres = array();
    protected $array_hijos = array();
    protected $opt_menu = 0;
    protected $nombre;
    protected $grupo_poblacional;




    public function run()
    {
        //self::inicia_conversa();            
        self::inicia_conversa();

    }

    public function inicia_conversa($error = null)
    {
        $mesnaje_inicial = Configuracion::where('id', 1)->first()->text_info;
        $this->say($mesnaje_inicial);
        self::solicita_nombre();       
    }

    public function solicita_nombre($error = null)
    {

        $question = "¿Cuál es tu nombre completo?";
        $this->ask($question, function ($answer) {
            $response = $answer->getValue() ?: $answer->getText();
            $this->nombre = $response;
            self::grupo_poblacionales();
        });        
    }

    public function grupo_poblacionales()
    {

        $grupos = GruposSociales::where('state_id', 1)->get()->toArray();

        foreach ($grupos as $list_grupos) {
            $buttons[] = Button::create($list_grupos['name'])->value($list_grupos['id']);
        }

        $question = Question::create('¿A qué grupo poblacional perteneces?')
            ->addButtons($buttons);

        // Enviar pregunta y manejar respuesta
        $this->ask($question, function ($answer) {
            if ($answer->isInteractiveMessageReply()) {
                $this->grupo_poblacional = $answer->getValue() ?: $answer->getText();
                
                self::obtener_padres($this->grupo_poblacional);
            }
        });

    }

    function obtenerUrlPrincipal()
    {
        // Protocolo (http o https)
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://';

        // Nombre del servidor (dominio)
        $host = $_SERVER['HTTP_HOST'];

        // Ruta base (si no está en la raíz)
        $ruta = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

        // URL completa
        return $protocolo . $host . $ruta;
    }

    public function isYouTubeUrl($url)
    {
        // Limpiamos la URL de parámetros adicionales que no necesitamos validar
        $url = preg_replace('/[?&]feature=.*/', '', $url);

        // Array de patrones para diferentes formatos de URLs de YouTube
        $patterns = [
            // Formato estándar: youtube.com/watch?v=VIDEO_ID
            '/^(https?:\/\/)?(www\.)?youtube\.com\/watch\?v=([A-Za-z0-9_-]{11})/',

            // Formato corto: youtu.be/VIDEO_ID
            '/^(https?:\/\/)?youtu\.be\/([A-Za-z0-9_-]{11})/',

            // Formato embed: youtube.com/embed/VIDEO_ID
            '/^(https?:\/\/)?(www\.)?youtube\.com\/embed\/([A-Za-z0-9_-]{11})/',

            // Formato directo: youtube.com/v/VIDEO_ID
            '/^(https?:\/\/)?(www\.)?youtube\.com\/v\/([A-Za-z0-9_-]{11})/'
        ];

        // Verificamos cada patrón
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    public function send_file_text($tiposms, $idopt)
    {

        $array_opt = MenuBotPlano::where('id', $idopt)->first();

        switch ($tiposms) {

            case '1'://texto

                $this->say(nl2br($array_opt->title));
                //self::menu_principal();            
                break;

            case '3'://texto

                $imagePath = '../public/uploads/temp_image.' . $array_opt->extension_file;
                file_put_contents($imagePath, base64_decode($array_opt->data_file));

                $imageUrl = $this->obtenerUrlPrincipal() . '/uploads/temp_image.' . $array_opt->extension_file;
                $image = new Image($imageUrl);

                $message = OutgoingMessage::create('')
                    ->withAttachment($image);

                // Envía el mensaje
                $this->say($message);

                break;

            case '2'://audio

                $audioPath = '../public/uploads/temp_audio.' . $array_opt->extension_file;
                file_put_contents($audioPath, base64_decode($array_opt->data_file));

                // Ruta o URL del audio                
                $audioUrl = $this->obtenerUrlPrincipal() . '/uploads/temp_audio.' . $array_opt->extension_file;

                // Crear el objeto Audio
                $audioAttachment = new Audio($audioUrl, [
                    'custom_payload' => 'Aquí tienes el audio que solicitaste.'
                ]);

                // Crear el mensaje con el audio adjunto
                $message = OutgoingMessage::create('')
                    ->withAttachment($audioAttachment);

                $this->say($message);
                //self::menu_principal();  
                break;

            case '6'://link
                $this->say((string) "<a target='_blank' href='" . $array_opt->title . "'> " . $array_opt->title . "</a>");
                //self::menu_principal();  
                break;

            case '4'://archivo

                // Ruta del archivo en el servidor
                $filePath = 'documento.pdf';

                // Ruta o URL del archivo (PDF o Word)
                //$link = '<a href="data:' . $$array_opt->mime_type_file . ';base64,' . $array_opt->data_file . '" download="documento.' . $array_opt->extension_file . '">'.$array_opt->name_file.'</a>';
                $link = '<a target="_blank" href="' . $_SESSION['urlpath'] . "/bot/download_file/" . $array_opt->id . '">' . $array_opt->name_file . '</a>';

                //$filePath = '../public/uploads/documento.' . $array_opt->extension_file;
                //file_put_contents($filePath, base64_decode($array_opt->data_file));

                $this->say((string) $link);
                break;

            case '5':

                if ($this->isYouTubeUrl($array_opt->url)) {

                    $videoUrl = $array_opt->url;
                    $thumbnailUrl = $this->obtenerUrlPrincipal() . '/assets/images/thum.png'; // Miniatura de YouTube

                    $attachment = new Image($thumbnailUrl, [
                        'custom_payload' => [
                            'url' => $videoUrl
                        ]
                    ]);

                    // Crear el mensaje con texto y la imagen adjunta
                    $message = OutgoingMessage::create(' 🎥<br>Haz clic <a href="' . $videoUrl . '" target="_blank">aqui</a> para verlo:')
                        ->withAttachment($attachment);


                } else {
                    // URL del archivo de video (asegúrate de que esté disponible públicamente)
                    $videoUrl = $array_opt->url;

                    // Crear el archivo adjunto
                    // $videoAttachment = new File($videoUrl);
                    $videoAttachment = new Video($videoUrl, [
                        'url' => $videoUrl
                    ]);

                    // Crear el mensaje con el archivo adjunto
                    $message = OutgoingMessage::create('')
                        ->withAttachment($videoAttachment);


                    // Enviar el mensaje

                }

                $this->say($message);
                break;

        }

        //self::menu_principal();  
        //self::menu_principal();
    }

    // Función para buscar y retornar el campo text_informativo donde exista
    private function getTextInformativoFromArray($items)
    {
        foreach ($items as $item) {
            if (!empty($item['text_informativo'])) {
                return [$item['text_informativo'], $item['title']];
            }
        }
        return null; // Retorna null si no se encuentra ningún text_informativo
    }

    public function obtener_padres($opt)
    {

        $array_padres = MenuBotPlano::where('parent', $opt)
        ->where('social_groups_id',$this->grupo_poblacional)
            ->orderby('display_order', 'ASC')
            ->get();

        //$this->say($opt);
        if (count($array_padres) == 1) {
            $hijos = self::obtener_hijos($array_padres[0]->id);
            $op = $array_padres[0]->id;
        } else if (count($array_padres) >= 1) {
            $hijos = self::obtener_hijos($opt);
            $op = $opt;
        } else {
            $padre = MenuBotPlano::where('parent', $this->opt_menu)->first();
            $hijos = self::obtener_hijos($padre->parent);
            //print_r($hijos);
        }


        if ($hijos['opt']) {
            $buttons = [];
            $text_informativo = $this->getTextInformativoFromArray($array_padres);

            if ($text_informativo[0] == 1) {//si tiene label es una lista de opciones

                $this->opt_menu = $op;
                // $this->say($opt);

                foreach ($hijos['opt'] as $list_hijos) {
                    if ($list_hijos['quetion'] == 0) {

                        $buttons[] = Button::create($list_hijos['title'])->value($list_hijos['id']);
                    }
                }

                $question = Question::create($text_informativo[1])
                    ->addButtons($buttons);

                $this->ask($question, function ($answer) {
                    if ($answer->isInteractiveMessageReply()) {
                        $response = $answer->getValue() ?: $answer->getText();
                        self::obtener_padres($response);
                    }
                });

            } else { //se envian todos los hijos si son varios, para actuar segun el tipo del mensaje (audio,texto,imagen)

                foreach ($hijos['array'] as $data_hijos) {
                    self::send_file_text($data_hijos['tipos_data_idtipos_data'], $data_hijos['id']);
                    //break;
                }

                self::menu_principal();
            }

        } else {
            //$this->say($array_padres[0]->title." ssss");
            self::send_file_text($array_padres[0]->tipos_data_idtipos_data, $array_padres[0]->id);
            self::menu_principal();
        }



    }

    public function obtener_hijos($padres)
    {

        $opt = [];
        $cont = 1;
        $this->array_hijos = MenuBotPlano::where('parent', $padres)
            ->orderby('display_order', 'ASC')
            ->get()->toarray();

        //print_r($padres);

        foreach ($this->array_hijos as $list_hijos) {
            $opt[] = array('id' => $list_hijos['id'], 'title' => $list_hijos['title'], 'quetion' => $list_hijos['text_informativo']);
        }

        return array('opt' => $opt, 'array' => $this->array_hijos);
    }


    public function menu_principal()
    {

        $buttons[] = Button::create("SI")->value("si");
        $buttons[] = Button::create("NO")->value("no");

        $question = Question::create('Deseas volver a el menú  anterior')
            ->addButtons($buttons);

        // Enviar pregunta y manejar respuesta
        $this->ask($question, function ($answer) {

            if ($answer->isInteractiveMessageReply()) {
                $response = $answer->getValue() ?: $answer->getText();
                if ($response == "si") {
                    $ultima_opt = MenuBotPlano::where('id', $this->opt_menu)->first();

                    if ($ultima_opt->text_informativo == 1) {
                        self::obtener_padres($ultima_opt->parent);
                    } else {
                        self::obtener_padres($this->opt_menu);
                    }

                } else {
                    self::inicia_conversa();
                }
            }

        });

    }


    public function despedida()
    {
        $this->say("Upsss! Muchas gracias por contactarnos, te esperamos en otra oportunidad. 👍");
    }

}
