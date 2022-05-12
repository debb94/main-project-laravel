<?php

namespace App\Http\Controllers\Administracion;
use App\Http\Controllers\MyController;
use Illuminate\Http\Request;
use App\Models\UsuariosModel;
use App\Models\PersonasModel;
use App\Models\PerfilesUsuariosModel;
use App\Models\AppModel;
use Exception;

class UsuariosController extends MyController{
    private $model;
    public  $appModel;
    private $endpoint   = 'administracion/usuarios';
    
    function __construct(){
        $this->model    = new UsuariosModel();
        $this->appModel = new AppModel();
        $this->personas = new PersonasModel();
        $this->perfilesUsuarios = new PerfilesUsuariosModel();
    }
    public function index(Request $request){
        $user       = $this->getUser();
        $permission = $this->checkPermission($user,$this->endpoint,__FUNCTION__);
        if($permission){    // tengo permisos
            $action = $request->input('action');
            switch($action){
                case 'getParamsUpdate':
                    try{
                        $result = $this->model->getParamsUpdate($user);
                        return $this->returnData($result);
                    }catch(Exception $e){
                        return $this->returnError('Error al consultar parametros');
                    }
                break;
                case 'administradores':
                    try{
                        $result = $this->model->getUsuariosAdministradores($user);
                        return $this->returnData($result);
                    }catch(Exception $e){
                        return $this->returnError('Error al consultar usuarios administradores');
                    }
                break;
                case 'all':
                    $result = $this->model->get($user);
                break;
                case 'aprobar-usuarios':
                    $result = $this->model->getUsuariosToApprove($user);
                break;
                default:
                    $result = $this->model->get($user);
                break;
            }
            return $this->returnData($result);
        }else{  // permisos denegados.
            return $this->notPermission();
        }
    }

    public function store(Request $request){
        $user       = $this->getUser();
        $permission = $this->checkPermission($user,$this->endpoint,__FUNCTION__);   //cheque permisos para el endpoint y para el tipo de request.
        if($permission){
            try{
                $objData    = json_decode($request->getContent());
                $form       = $objData->formulario;     // usuarios
                $form2      = $objData->formulario2;    // personas
                $form3      = $objData->formulario3;    // perfiles
                
                // persona
                $persona_id = $this->personas->insertData($form2,$user);            // return persona_id
                if(sizeof($persona_id) > 0){
                    $persona_id = $persona_id[0]->persona_id;
                }else{
                    return $this->returnError('Error: #101 Error al registrar usuario');
                }

                // usuario
                $usuario_id = $this->model->insertData($form,$user,$persona_id);    //return codigo
                if(sizeof($usuario_id) > 0){
                    $usuario_id = $usuario_id[0]->usuario_id;
                }else{
                    $result = $this->model->deletePersona($persona_id);
                    return $this->returnError('Error: #102 Error al registrar usuario');
                }
                //  perfiles usuarios
                $result     = $this->perfilesUsuarios->insertData($form3,$user,$usuario_id);
                if($result){
                    return $this->returnOk('Datos guardados exitosamente');
                }else{
                    $result = $this->model->deletePersona($persona_id);
                    $result = $this->model->deleteUsuario($usuario_id);
                    return $this->returnError('Error: #103 Error al registrar perfil de usuario');
                }
            }catch(Exception $e){
                if(isset($persona_id)){
                    $result = $this->model->deletePersona($persona_id);
                }else if(isset($usuario_id)){
                    $result = $this->model->deleteUsuario($usuario_id);
                }else{
                    $result = $this->model->deletePerfil($usuario_id);
                }
                return $this->returnError('Se produjo un error al insertar datos, o ya existe el usuario');
            }
        }else{
            return $this->notPermission();
        }
    }

    public function show($id){
        $user       = $this->getUser();
        $permission = $this->checkPermission($user,$this->endpoint,__FUNCTION__);   //cheque permisos para el endpoint y para el tipo de request.
        if($permission){
            try{
                $result = $this->model->get($user,$id);
                return $this->returnData($result,'No se obtuvo resultado de usuario');
            }catch(Exception $e){
                return $this->returnError('Error al obtener información de usuario');
            }
        }else{
            return $this->notPermission();
        }
    }

    public function update(Request $request, $id){
        $action     = $request->input('action');
        $user       = $this->getUser();
        $permission = $this->checkPermission($user,$this->endpoint,__FUNCTION__);   //chequea permisos para el endpoint y para el tipo de request.
        if($permission){
            switch($action){
                case 'updateStatus':
                    try{
                        $objData = json_decode($request->getContent());
                        $status = $objData->status;
                        if($id == 'null'){
                            $codigos = $objData->codigos;
                        }else{
                            $codigos = null;
                        }
                        $result = $this->model->updateStatus($status,$id,$codigos,$user);
                        return $this->returnOk('Actualizado correctamente');
                    }catch(Exception $e){
                        return $this->returnError('Se produjo un error al intentar actualizar estado');
                    }
                break;
                case 'aprobar':
                    $objData = json_decode($request->getContent());
                    if($id == 'null'){
                        $codigos =  $objData->codigos;
                    }else{
                        $codigos = null;
                    }
                    $status = $objData->status;
                    $result = $this->setUserFromUserToApprove($status,$id,$codigos,$user);
                    return $result;
                break;
                default:
                    try{
                        $objData = json_decode($request->getContent());
                        $formulario     = $objData->formulario;     // usuario
                        $formulario2    = $objData->formulario2;    // personas
                        $formulario3    = $objData->formulario3;    // perfil
                        $persona_id     = $this->model->updateData($formulario,$id,$user);
                        $persona_id     = $persona_id[0]->persona_id;
                        $result        = $this->personas->updateData($formulario2,$persona_id,$user);
                        $result        = $this->perfilesUsuarios->updateData($formulario3,$id,$user);
                        return $this->returnOk('Actualizado correctamente');
                    }catch(Exception $e){
                        return $this->returnError('Se produjo un error al intentar actualizar');
                    }
                break;
            }
        }else{
            return $this->notPermission();
        }
    }

    public function destroy($id){
        $user       = $this->getUser();
        $permission = $this->checkPermission($user,$this->endpoint,__FUNCTION__);   //cheque permisos para el endpoint y para el tipo de request.
        if($permission){
            try{
                $result  = $this->model->inactive($id);
                return $this->returnOk('Registro desactivado');
            }catch(Exception $e){
                return $this->returnError('No se pudo desactivar el registro');
            }
        }else{
            return $this->notPermission();
        }
    }


    public function setUserFromUserToApprove($status,$id,$codigos,$user){
        try{
            if($codigos == null){
                $codigos = array($id);
            }
            $users = $this->model->getUsuariosToApprove($user,$codigos);
            if(sizeof($users)>0){
                if($status == 1){
                    return $this->model->createUsersFromUserToApprove($users,$user);
                }else{
                    return $this->model->inactivateUsersToApprove($users,$user);
                }
            }
        }catch(Exception $e){
            return $this->returnError('Se produjo un error al intentar asociar usuario y propiedad');
        }
    }
}
