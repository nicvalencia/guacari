<?php
use Symfony\Component\Dotenv\Dotenv;
$dotenv = new Dotenv();
$dotenv->load('../.env');
$capsule = new \Illuminate\Database\Capsule\Manager;
$charset = 'utf8mb4';
$collate = 'utf8mb4_unicode_ci';


//conexion a la BD
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_PERSISTENT => false,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset COLLATE $collate",
    "outputBuffering" => true
];

$config = [
    'driver' => 'mysql',
    'host' => $_ENV['HOST_BD_MYSQL'],
    'database' => $_ENV['BD_MYSQL'],
    'username' => $_ENV['USER_BD_MYSQL'],
    'password' => $_ENV['PASS_BD_MYSQL'],
    'charset'  => 'utf8mb4',                              
    $options
];


$capsule->addConnection($config);
$capsule->setAsGlobal();
$capsule->bootEloquent();


define('SAVE','Los datos se guardarón con éxito');
define('EDIT','Los datos se modificarón con éxito');
define('DEL','Los datos se eliminarón con éxito');
define('DELERROR','El registro no se logro eliminar');
define('ERROR','Ocurrio un problema en el sistema');
define('ERROR_FUERA_RANGO','Por favor seleccione una opcion dentro del rango dado');
define('SECRETHASH','25assac6c7ff35b9979b151f2136cd13b0ff');
