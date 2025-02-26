<?php
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Connection;
$settings = [];

// Slim settings
$settings['displayErrorDetails'] = true;
$settings['determineRouteBeforeAppMiddleware'] = true;
$settings['debug'] = true;
// Path settings
$settings['root'] = dirname(__DIR__);
$settings['temp'] = $settings['root'] . '/tmp';
$settings['public'] = $settings['root'] . '/public';

// View settings


// Database settings
$settings['db']['host'] = 'localhost';
//LgDBtdkATeCw4sZO
//dbmanitoba user
$settings['db']['username'] = 'appgrupo_masivo';
$settings['db']['password'] = 'Zp%IsHc?dKt6';
$settings['db']['database'] = 'appgrupo_masivo';


$settings['db']['charset'] = 'utf8mb4';
//$settings['db']['collation'] = 'utf8_unicode_ci';
$settings['db']['driver'] = 'mysql';

return $settings;