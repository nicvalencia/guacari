<?php
namespace App\Controladores;
use Slim\Views\Twig; // Las vistas de la aplicación
use Slim\Router; // Las rutas de la aplicación
use Respect\Validation\Validator as v; // para usar el validador de Respect
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Csrf\Guard as Guard;
use Slim\Psr7\Factory\ResponseFactory;


class CsrfExtension extends \Twig\Extension\AbstractExtension 
{
    protected $view;
	// objeto de la clase Router
	protected $guard ;
    
    

    public function __construct()
    {
        $responseFactory = new ResponseFactory(); // Note that you will need to import
        $this->guard = new Guard($responseFactory);        
    }


    public function getFunctions(){
        return [
            new \Twig\TwigFunction('csrf_field', array($this,'csrfField')),
        ];        
    }


    public function csrfField()
    {
        // CSRF token name and value
        //print_r($this->guard);       
        $keyPair = $this->guard->generateToken();
		$nameKey = $this->guard->getTokenNameKey();
		$valueKey = $this->guard->getTokenName();
	
		
		$name = $this->guard->getTokenValueKey();
		$value = $this->guard->getTokenValue();
        //print_r($keyPair);
        return '<input type="hidden" name="'. $nameKey.'" value="'.$valueKey.'">
        <input type="hidden" name="'. $name.'" value="'.$value.'">';

       /* return [
            'csrf'   => [
                'keys' => [
                    'name'  => $csrfNameKey,
                    'value' => $csrfValueKey
                ],
                'name'  => $csrfName,
                'value' => $csrfValue
            ]
        ];*/

        
    }

    
}
?>