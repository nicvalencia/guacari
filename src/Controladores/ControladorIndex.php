<?php

namespace App\Controladores;

use DI\Container;
use Slim\Views\Twig; // Las vistas de la aplicación
use Slim\Router; // Las rutas de la aplicación
use Respect\Validation\Validator as v; // para usar el validador de Respect
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Exceptions\AllOfException;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Luecano\NumeroALetras\NumeroALetras;

use App\Controladores\ControladorConsultas as Consultasws;


/**
 * Clase de controlador para el usuario de la aplicación
 */

class ControladorIndex 
{

	protected $view;
	// objeto de la clase Router
	protected $router;

	protected $container;	
	public function __construct(Container $container)
	{
		$this->container = $container;		
	}

	/*public function __get($property)
	{
		if ($this->container->{$property}) {
			return $this->container->{$property};
		}
	}*/

    /**
     * Verifica que los parametros que recibe el controlador sean correctos
     * @param type array $args - los argumentos a evaluar
     */

	
	/*-- Funciones del CRUD --*/
	public function index(Request $request, Response $response, $args)
    {      
							   

    return Twig::fromRequest($request)->render($response,'bodyindex.twig');
			
	}
	
	public function page_pagos(Request $request, Response $response, $args){	
		$param = $request->getParsedBody(); 	    
		return Twig::fromRequest($request)->render($response,'template_consulta_pagos.twig');
	}

	public function page_certificados(Request $request, Response $response, $args){	
		$param = $request->getParsedBody(); 	  
		
		switch ($args['tipo']){
	
			case "retefuente":
				$args['titulo'] = "CERTIFICADO DE RENTENCION EN LA FUENTE";
				 $args['cuenta'] = '2365';
				 $retenciones = Consultasws::getCertificadoRetencion($request, $response, $args);				 
				 break;
			
			case "reteica":
				$args['titulo'] = "CERTIFICADO DE INDUSTRIA, COMERCIO Y AVISOS";  	
				 $args['cuenta'] = '2368';			 
				 $retenciones = Consultasws::getCertificadoReteIva($request, $response, $args);
				 //var_dump($retenciones);
				 break;
			
			case "retecree":
				$args['titulo'] = "CERTIFICADO DE RENTENCION CRE";		
				 $args['cuenta'] = '2369';		 
				 $retenciones = Consultasws::getCertificadoRetencion($request, $response, $args);
				 break;
			
			case "reteiva":
				$args['titulo'] = "CERTIFICADO DE RENTENCION POR IVA";
				 $periodo = intval($_GET['periodo']);
				 $args['cuenta'] = '2367';
				 $args['anno_anterior'] = date('Y')-1;
				 $args['anno'] = date('Y');

				 switch($periodo){
					case 1:
						$periodo_nombre = "NOVIEMBRE - DICIEMBRE DE " . $anno_anterior;
						break;
					case 2:
						$periodo_nombre = "ENERO - FEBRERO DE " . $anno;
						break;
					case 3:
						$periodo_nombre = "MARZO - ABRIL DE " . $anno;
						break;
					case 4:
						$periodo_nombre = "MAYO - JUNIO DE " . $anno;
						break;
					case 5:
						$periodo_nombre = "JULIO - AGOSTO DE " . $anno;
						break;
					case 6:
						$periodo_nombre = "SEPTIEMBRE - OCTUBRE DE " . $anno;
						break;
					case 7:
						$periodo_nombre = "NOVIEMBRE - DICIEMBRE DE " . $anno;
						break;
				 }
				 $retenciones = Consultasws::getCertificadoReteIva($request, $response, $args);
				 break;	 
			}

		$data = json_decode($retenciones,true); //$response->withJson($retenciones); 
		
		return Twig::fromRequest($request)->render($response,'template_listado_certificados.twig',['data'=>$data,'args' => $args]);
	}

	public function page_estado_facturas(Request $request, Response $response, $args){
		$param = $request->getParsedBody(); 	    
		return Twig::fromRequest($request)->render($response,'template_estado_facturas.twig');		
	}

	public function genera_pdf_documento(Request $request, Response $response, $args){
		
		$directory = $this->container->get('upload_directory');
		$mpdf = new \Mpdf\Mpdf(['tempDir' => $directory]);	
		
		$detalle = json_decode(Consultasws::getDetalleEgreso($request,$response,$args),true);
		$detallePago = json_decode(Consultasws::getPagoEgreso($request,$response,$args));
		$detalleEgreso = json_decode(Consultasws::getDetalleCuentasEgreso($request,$response,$args),true);
		
		$fecha = Carbon::now();
		$valor = $detallePago[0]->{'valor'};
		$razon_social = $detalleEgreso[0]['razon_social'];
		
		$html = '<style>
		#letras_sum{
			text-transform:uppercase;
			
			width:350px;
		
		
		}
		
		#datos{
			width:900px;
		}
		
		#datos td{
			width:120px;
		}
		
		#tabla_contenedora{
			margin-top:80px;
		}
		
		</style><table width="100%" border="0" align="center" id="datos">
				<tr>
				<td colspan="2" rowspan="2" align="center" valign="top"><p>SURTIFAMILIAR SA<br />
					NIT. : 805028991-6<br />
					CL 9B 23C 65<br />
					CALI</p>
					<p>Tel: 487 30 41</p></td>
				<td valign="top">&nbsp;</td>';

		$html .='</tr>
					<tr>
					<td align="left" valign="top">Numero: '.$args['documento'].'<br />
						Fecha: '.$fecha.'</td>
					</tr>
					<tr>
					<td>&nbsp;</td>
					<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
					<td width="42%">Tercero : '.$razon_social.'</td>
					<td colspan="2">CC NIT: '.$_SESSION['nit'].'</td>
					</tr>
					<tr>
					<td>&nbsp;</td>
					<td colspan="2">Ciudad: Cali</td>
					</tr>
					<tr>
					<td rowspan="2" valign="top" nowrap="nowrap">&nbsp; <div id="letras_sum"></div></td>
					<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
					<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
					<td>&nbsp;</td>
					<td width="15%">&nbsp;</td>
					<td width="43%">&nbsp;</td>
					</tr>
					<tr>
					<td>Por concepto de: CANCELACION DE FACTURAS</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					</tr>
					<tr>
					<td>Valor consignado: '.number_format($valor,2).'</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					</tr>
					<tr>
					<td>La suma de: '.self::num2letras($valor).'</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					</tr>
					<tr>
					<td colspan="3">
					
					</td>
					</tr>
					<tr>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					</tr>
				</table>';

				$html .= '<table  border="0" align="center" id="datos">
								<tr>
								<th >AUXILIAR</th>
								<th >CO</th>
								<th >TERCERO</th>
								<th >RAZON SOCIAL</th>
								<th >DEBITOS</th>
								<th >CREDITOS</th>
							</tr>';
								
								
								$total_db = 0;
								$total_cr = 0;
								foreach($detalleEgreso as $det){ 
									$total_db = $total_db + $det['total_db'];
									$total_cr = $total_cr + $det['total_cr'];
							
				$html .= '		<tr>
								<td align="left">'.$det['id_cuenta'].'</td>
								<td align="left">'.$det['id_co'].'</td>
								<td align="left">'.$det['nit'].'</td>
								<td align="left">'.$det['razon_social'].'</td>
								<td align="left">'.number_format($det['total_db']).'</td>
								<td align="left">'.number_format($det['total_cr']).'</td>
								</tr>';								
								}
				$html .= '		<tr>
								<td></td>
								<td></td>
								<td></td>
								<td>Sumas iguales:</td>
								<td>'.number_format($total_db,2).'</td>
								<td>'.number_format($total_cr,2).'</td>
							</tr>
						</table><br><br>';


			    $html .= '<table  border="0" align="center" id="datos">
							<tr>
							<th >C.O</th>
							<th >TERCERO</th>
							<th >DCTO CRUCE</th>
							<th >DESCUENTOS</th>
							<th >RETENCIONES</th>
							<th >VALOR FACTURA</th>
						</tr>';

				foreach($detalle['data'] as $documento){ 				  
				
				$html .= '<tr>
				  <td align="left">'.$documento['id_co'] . " " . $documento['co_descripcion'].'</td>
				  <td align="left">'.$_SESSION['nit'].'</td>
				  <td align="left">'.$documento['documento'].'</td>
				  <td align="left">'.number_format((float) $documento['descuentos'],2).'</td>
				  <td align="left">'.number_format((float) $documento['retenciones'],2).'</td>
				  <td align="left">'.number_format((float) $documento['valor_neto'],2).'</td>
				</tr>';				
				  }
				
				$html .= '</table>';
				//return $html;
				$mpdf->WriteHTML($html);
				$mpdf->Output();
				$res = $response
			->withHeader('Content-Type', 'application/pdf')
			->withHeader('Content-Disposition', 'attachment; filename="'.$args['documento'].'.pdf"');
				return $res;
	}

	public function enviar_comprobante_email(Request $request, Response $response, $args){

		$param = $request->getParsedBody(); 
		$directory = $this->container->get('upload_directory');
		$mpdf = new \Mpdf\Mpdf(['tempDir' => $directory]);		
		
		$detalle = json_decode(Consultasws::getDetalleEgreso($request,$response,$param),true);
		$detallePago = json_decode(Consultasws::getPagoEgreso($request,$response,$param));
		$detalleEgreso = json_decode(Consultasws::getDetalleCuentasEgreso($request,$response,$param),true);
		$fecha = Carbon::now();
		$valor = $detallePago[0]->{'valor'};
		$razon_social = $detalleEgreso[0]['razon_social'];
		
		$html = '<style>
		#letras_sum{
			text-transform:uppercase;
			
			width:350px;
		
		
		}
		
		#datos{
			width:900px;
		}
		
		#datos td{
			width:120px;
		}
		
		#tabla_contenedora{
			margin-top:80px;
		}
		
		</style><table width="100%" border="0" align="center" id="datos">
				<tr>
				<td colspan="2" rowspan="2" align="center" valign="top"><p>SURTIFAMILIAR SA<br />
					NIT. : 805028991-6<br />
					CL 9B 23C 65<br />
					CALI</p>
					<p>Tel: 487 30 41</p></td>
				<td valign="top">&nbsp;</td>';

		$html .='</tr>
					<tr>
					<td align="left" valign="top">Numero: '.$param['documento'].'<br />
						Fecha: '.$fecha.'</td>
					</tr>
					<tr>
					<td>&nbsp;</td>
					<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
					<td width="42%">Tercero : '.$razon_social.'</td>
					<td colspan="2">CC NIT: '.$_SESSION['nit'].'</td>
					</tr>
					<tr>
					<td>&nbsp;</td>
					<td colspan="2">Ciudad: Cali</td>
					</tr>
					<tr>
					<td rowspan="2" valign="top" nowrap="nowrap">&nbsp; <div id="letras_sum"></div></td>
					<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
					<td colspan="2">&nbsp;</td>
					</tr>
					<tr>
					<td>&nbsp;</td>
					<td width="15%">&nbsp;</td>
					<td width="43%">&nbsp;</td>
					</tr>
					<tr>
					<td>Por concepto de: CANCELACION DE FACTURAS</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					</tr>
					<tr>
					<td>Valor consignado: '.number_format($valor,2).'</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					</tr>
					<tr>
					<td>La suma de: '.self::num2letras($valor).'</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					</tr>
					<tr>
					<td colspan="3">
					
					</td>
					</tr>
					<tr>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					</tr>
				</table>';

				$html .= '<table  border="0" align="center" id="datos">
								<tr>
								<th >AUXILIAR</th>
								<th >CO</th>
								<th >TERCERO</th>
								<th >RAZON SOCIAL</th>
								<th >DEBITOS</th>
								<th >CREDITOS</th>
							</tr>';
								
								
								$total_db = 0;
								$total_cr = 0;
								foreach($detalleEgreso as $det){ 
									$total_db = $total_db + $det['total_db'];
									$total_cr = $total_cr + $det['total_cr'];
							
				$html .= '		<tr>
								<td align="left">'.$det['id_cuenta'].'</td>
								<td align="left">'.$det['id_co'].'</td>
								<td align="left">'.$det['nit'].'</td>
								<td align="left">'.$det['razon_social'].'</td>
								<td align="left">'.number_format($det['total_db']).'</td>
								<td align="left">'.number_format($det['total_cr']).'</td>
								</tr>';								
								}
				$html .= '		<tr>
								<td></td>
								<td></td>
								<td></td>
								<td>Sumas iguales:</td>
								<td>'.number_format($total_db,2).'</td>
								<td>'.number_format($total_cr,2).'</td>
							</tr>
						</table><br><br>';


			    $html .= '<table  border="0" align="center" id="datos">
							<tr>
							<th >C.O</th>
							<th >TERCERO</th>
							<th >DCTO CRUCE</th>
							<th >DESCUENTOS</th>
							<th >RETENCIONES</th>
							<th >VALOR FACTURA</th>
						</tr>';

				foreach($detalle['data'] as $documento){ 				  
				
				$html .= '<tr>
				  <td align="left">'.$documento['id_co'] . " " . $documento['co_descripcion'].'</td>
				  <td align="left">'.$_SESSION['nit'].'</td>
				  <td align="left">'.$documento['documento'].'</td>
				  <td align="left">'.number_format((float) $documento['descuentos'],2).'</td>
				  <td align="left">'.number_format((float) $documento['retenciones'],2).'</td>
				  <td align="left">'.number_format((float) $documento['valor_neto'],2).'</td>
				</tr>';				
				  }
				
				$html .= '</table>';
				//return $html;
				$mpdf->WriteHTML($html);
				$mpdf->Output( 'uploads/'.$param['documento'].'.pdf','F');
			
		$data = ['destinatario' => "nicvalencia@gmail.com",
				 'file'  => $directory."/".$param['documento'].'.pdf',
				 'asunto' => "Documento de pago proveedor No.".$param['documento'],
				 'body'	 => "Envio automatico de comprobante de pago No.".$param['documento'],
				 'nom_prove' => $_SESSION['proveedor']];

		$info = Emails::send_email('email_comprobante',$data);

		return $response->withJson([
			'succes' => true , 
			'tipo' => 'success',
			'redirect'=> 1,
			'close_modal' => 'open_modal_email',
			'message'=>'El correo se envio correctamente',
			'data' => $info
			]);	
	}


	public function genera_certificados(Request $request, Response $response, $args){

			$params = explode('/', $args['params']);
			$pdfOptions = new Options();		
			$pdfOptions->set('isRemoteEnabled', true);	
			$dompdf = new Dompdf($pdfOptions);
		
			$formatter = new NumeroALetras();
			$formatter->conector = 'Y';

			$retenciones = Consultasws::downloadcertificados($params[0], $params[1]);	
			$data = json_decode($retenciones,true);
			
			$valtotretencion = array_sum(array_column($data, 'valor_retencion'));
			$titulo = array_column($data, 'titulo');
			$gtotal = $formatter->toMoney($valtotretencion, 2, 'pesos', 'centavos');
			
			$dompdf->loadHtml($this->container->get('view')->fetch('pdf/certificados.twig',['retenciones' 		=> $data,
																							'titulo'			=>	$titulo[0],
																							'periodo_nombre'	=>	$periodo_nombre,
																							'periodo'			=> $params[0],
																							'totalretencines'	=> $gtotal]));	 
			$dompdf->render();
			$dompdf->stream("certificacion.pdf", array('Attachment' => 1));	

			/*echo $this->container->get('view')->fetch('pdf/certificados.twig',['retenciones' 		=> $data,
			'titulo'			=>	$titulo,
			'periodo_nombre'	=>	$periodo_nombre,
			'totalretencines'	=> $gtotal]);*/
			return $response;
			
	}


	public function getpagosadmin(Request $request, Response $response, $args)
    {   
    	return Twig::fromRequest($request)->render($response,'admin/template_consulta_pagos_terceros.twig');			
	}

	public function getfacturasadmin(Request $request, Response $response, $args)
    {   
    	return Twig::fromRequest($request)->render($response,'admin/template_estado_facturas_terceros.twig');			
	}

}