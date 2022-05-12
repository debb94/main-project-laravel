<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use App\Models\AppModel;
use Exception;

class MyController extends Controller{

    public $appModel;

    function __construct(){
        $this->appModel = new AppModel();
    }
    
    function returnData($data,$message=null,$addData=null){
        if(sizeof($data)>0 || $addData != null){
            if($addData != null){
                if(sizeof($data)>0){
                    $objData = array(
                        'success'   => true,
                        'data'      => $data
                    );
                }else{
                    $objData = array(
                        'success'   => true,
                        'data'      => null
                    );
                }
                foreach($addData as $key => $val){
                    $objData[$key] = $val;
                }
                return json_encode($objData);
            }else{
                $objData = array(
                    'success'   => true,
                    'data'      => $data
                );
            }
            return json_encode($objData);
        }else{
            $objData = array(
                'success'   => false,
                'data'      => null,
                'message'   => $message
            );
            return json_encode($objData);
        }
    }
    function returnOk($message){
        $objData = array(
            'success'   => true,
            'message'   => $message
        );
        return json_encode($objData);
    }

    function returnError($msg,$errorCode = '400'){
        $objData = array(
            'success'   =>false,
            'message'   =>$msg,
            'code'      => $errorCode
        );
        // return response()->json($objData,$errorCode);
        return response()->json($objData);
    }


    function desencrypt($var){
        return base64_decode(base64_decode($var));
    }

    function encrypt($var){
        return base64_encode(base64_encode($var));
    }

    function getUser(){
        // obtengo cabecera
        $header = apache_request_headers();

        if(isset($header['Authorization']) || isset($header['authorization'])){
            $authorization = isset($header['Authorization']) ? $header['Authorization'] : $header['authorization'];
            try{
                $authorization = JWT::decode($authorization,env('KEY_ACCESS'),array('HS256'));
                $userId = $this->desencrypt($authorization->userId);
                return $userId;
            }catch(Exception $e){
                echo $this->returnError('Acceso denegado');
                exit();
            }
        }else{
            // enviar a iniciar session.
            exit();
        }
    }

    function checkPermission($user,$endpoint,$function){
        $permisos = $this->appModel->checkPermission($user,$endpoint);
        // print_r($permisos);
        // return "ok";
        if(sizeof($permisos) > 0){            
            $permisos = explode('|',$permisos[0]->permisos);
            switch($function){
                case 'index':
                    return in_array('ver',$permisos,true);
                break;
                case 'show':
                    return in_array('ver',$permisos,true);
                break;
                case 'store':
                    return in_array('crear',$permisos,true);
                break;
                case 'update':
                    return in_array('editar',$permisos,true);
                break;
                case 'destroy':
                    return in_array('eliminar',$permisos,true);
                break;
                default:
                    return false;
                break;
            } 
        }
        return false;
    }

    function notPermission(){
        $objData = array(
            'success'   =>false,
            'message'   => "No tiene, permisos para ejecutar esta acción"
        );
        return json_encode($objData);
    }

    function getIp(){
        if (isset($_SERVER["HTTP_CLIENT_IP"])){
            return $_SERVER["HTTP_CLIENT_IP"];
        }elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
            return $_SERVER["HTTP_X_FORWARDED_FOR"];
        }elseif (isset($_SERVER["HTTP_X_FORWARDED"])){
            return $_SERVER["HTTP_X_FORWARDED"];
        }elseif (isset($_SERVER["HTTP_FORWARDED_FOR"])){
            return $_SERVER["HTTP_FORWARDED_FOR"];
        }elseif (isset($_SERVER["HTTP_FORWARDED"])){
            return $_SERVER["HTTP_FORWARDED"];
        }else{
            return $_SERVER["REMOTE_ADDR"];
        }
    }



    function getState($pais){
        try{
            $result = $this->appModel->getEstate($pais);
            return $this->returnData($result);
        }catch(Exception $e){
            return $this->returnError('Error al consultar estado');
        }
    }

    function getCity($country,$state){
        try{
            $result = $this->appModel->getCity($country,$state);
            return $this->returnData($result,'No se obtuvieron datos');
        }catch(Exception $e){
            return $this->returnError('Error al consultar ciudades');
        }
    }


    function getEdificios($complejo){
        try{
            $result = $this->appModel->getEdificios($complejo);
            return $this->returnData($result);
        }catch(Exception $e){
            return $this->returnError('Error al consultar Edificios');
        }
    }
    
    function getPropiedades($user,$complejo,$edificio){
        try{
            $result = $this->appModel->getPropiedades($user,$complejo,$edificio);
            return $this->returnData($result);
        }catch(Exception $e){
            return $this->returnError('Error al consultar propiedades');
        }
    }

    function getEstacionamientosPropiedad($propiedad_id){
        try{
            $result = $this->appModel->getEstacionamientosPropiedad($propiedad_id);
            return $this->returnData($result);
        }catch(Exception $e){
            return $this->returnError('Error al consultar estacionamientos');
        }
    }

    function getUsuarios(){
        try{
            $result = $this->appModel->getUsuarios();
            return $this->returnData($result);
        }catch(Exception $e){
            return $this->returnError('Error al consultar Edificios');
        }
    }



    // posiblemente eliminar
    function getParamsCheckout(){
        try{
            $result = $this->appModel->getCountry();
            return $this->returnData($result);
        }catch(Exception $e){
            return $this->returnError('Error al consultar parametros de pago');
        }
    }

    function getProfile($user){
        try{
            $result = $this->appModel->getProfile($user);
            return $result[0]->perfil_nombre;
        }catch(Exception $e){
            return $this->returnError('Error al consultar perfil de usuario');
        }
    }

    // GENERALES
    /**
	* @internal 		Funcion Entrega los indices de columnas de un archivo excel. partiendo del numero de columnas.
	* @param 			Int 	$columnas numero de columnas del archivo.
	* @return 			Array 	$letras - contiene la nomenclatura de los indices de las columnas hasta donde se especifico por paramentro.
	*
	* @author 			Daniel Bolivar - dbolivar@processoft.com.co - daniel.bolivar.freelance@gmail.com
	* @version 			1.0.0
	* @since 			19-06-2019
	*/
	public function getColumns($columnas){
		$letras = array();
		$index_vocabulary = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		if($columnas > 26){
			// $mod = $columnas%26; // si el mod es cero quiere decir que se esta pasando a otra combinacion de 2, 3, 4... n combinaciones.
			$combinaciones = intval($columnas / 26); 	// numero de letras combinadas.
			$estado_combinaciones = 0; 					// comienza siempre en 1 por que estamos en posiciones de columnas mayor a 26. 
			$posicion = 0;
			while($posicion <= $columnas){
				//$iterador_array = 26 * $estado_combinaciones - $columnas[posicion];
				if($posicion <26){
					$letras[] = substr($index_vocabulary,$posicion, 1);
					if($posicion == 25){
						$estado_combinaciones++;
					}
					$posicion++;
				}else{
					//$iterador_array = intval($columnas/26);
					for ($iterador=0; $iterador < $combinaciones ; $iterador++) { 
						// recorro 26 veces 
						// menos cuando ya se excede el numero de la posicion
						for ($i=0; $i < 26 ; $i++) { 
							$pos = $posicion - 26 * $estado_combinaciones;
							$letras[] = $letras[$iterador].substr($index_vocabulary,$pos,1);
							$posicion++;
						}
						$estado_combinaciones++;
					}
				}
			}
		}else{
			for($i=0; $i < $columnas; $i++) { 
				$letras[]=substr($index_vocabulary, $i,1);
			}
		}
		return $letras;
	}

    /**
     * @internal    Genera una llave aleatoria alfanumerica.
     * @param       length largo de la llave a generar.
     */
    public function generateKey($length){
        $permittedChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $key = "";
        $strlength = strlen($permittedChars);
        for($i = 0; $i < $length; $i++){
            $key.= $permittedChars[mt_rand(0,$strlength-1)];
        }
        return $key;
    }


    public function getDependencies($dependencies,$result){
        $data = [];
        foreach($dependencies as $value){
            switch($value){
                case 'estados':
                    $response = $this->appModel->getEstate($result->pais_codigo);
                    $data['estados'] = $response;
                break;
                case 'ciudades':
                    $response = $this->appModel->getCity($result->pais_codigo,$result->estado_codigo);
                    $data['ciudades'] = $response;
                break;
                default:
                break;
            }
        }
        return $data;
    }


}
