<?php

namespace App\Http\Controllers\Login;

use App\Http\Controllers\MyController;
use Illuminate\Http\Request;
use App\Models\RegisterModel;
use App\Models\PersonasModel;
use App\Models\UsuariosModel;
use App\Models\PerfilesUsuariosModel;
use App\Models\PropiedadesModel;
use Exception;


class RegisterController extends MyController{
    private $model;

    function __construct(){
        $this->model        = new RegisterModel();
        $this->personas     = new PersonasModel();
        $this->usuarios     = new UsuariosModel();
        $this->perfilesUsuarios = new PerfilesUsuariosModel();
        $this->propiedades  = new PropiedadesModel();
    }
    public function getPropiedad(Request $request){
        $action = $request->input('action');
        switch($action){
            case 'getComplejos':
                try{
                    $result = $this->model->getComplejos();
                    return $this->returnData($result);
                }catch(Exception $e){
                    return $this->returnError("Error al obtener complejos");
                }
            break;
            case 'getEdificios':
                try{
                    $complejo_id = $request->input('complejo');
                    $result = $this->model->getEdificios($complejo_id);
                    return $this->returnData($result);
                }catch(Exception $e){
                    return $this->returnError("Error al obtener edificios");
                }
            break;
            case 'getPropiedad':
                try{
                    $complejo_id = $request->input('complejo');
                    $edificio_id = $request->input('edificio');
                    $result = $this->model->getPropiedades($complejo_id,$edificio_id);
                    return $this->returnData($result);
                }catch(Exception $e){
                    return $this->returnError("Error al obtener propiedades");
                }
            break;
        }
    }

    public function register(Request $request){
        try{
            $objData    = json_decode($request->getContent());

            $formContent = new \stdClass();
            $formContent->persona_nombre1   = $objData->persona_nombre1;
            $formContent->persona_nombre2   = $objData->persona_nombre2;
            $formContent->persona_apellido1 = $objData->persona_apellido1;
            $formContent->persona_apellido2 = $objData->persona_apellido2;
            $formContent->persona_telefono  = $objData->persona_telefono;
            $formContent->usuario_username  = $objData->usuario_username;
            $formContent->usuario_password  = $this->desencrypt($objData->usuario_password);
            $formContent->propiedad_id      = $objData->propiedad_id;

            // chequeo que usuario no existe.
            $user = $this->model->checkEmail($formContent->usuario_username);
            if(sizeof($user)> 0){
                return $this->returnError('El usuario ya existe');
            }else{
                $result     = $this->model->insertRegister($formContent);
                return $this->returnOk('Datos enviados a revisión para su aprobación');
            }
        }catch(Exception $e){
            return $this->returnError('Se produjo un error al intentar guardar su información');
        }

    }

}
