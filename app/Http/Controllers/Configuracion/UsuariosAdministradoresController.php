<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\MyController;
use App\Models\UsuariosAdministradoresModel;
use App\Models\AppModel;
use Illuminate\Http\Request;

class UsuariosAdministradoresController extends MyController{
    private $model;
    public  $appModel;
    private $endpoint   = 'configuracion/usuarios-administradores';
    
    function __construct(){
        $this->model    = new UsuariosAdministradoresModel();
        $this->appModel = new AppModel();
    }
    public function index(Request $request){
        $user       = $this->getUser();
        $permission = $this->checkPermission($user,$this->endpoint,__FUNCTION__);
        if($permission){    // tengo permisos
            $action = $request->input('action');
            switch($action){
                case 'getParamsUpdate':
                    try{
                        $result = $this->model->getParamsUpdate();
                        return $this->returnData($result);
                    }catch(Exception $e){
                        return $this->returnError('Error al consultar usuarios o complejos');
                    }
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
                $objData = json_decode($request->getContent());
                $form = $objData->formulario;
                $result = $this->model->insertData($form,$user);
                return $this->returnOk('Datos guardados exitosamente');
            }catch(Exception $e){
                return $this->returnError('Se produjo un error al insertar datos');
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
                return $this->returnData($result,'No se obtuvo resultado de usuario administrador');
            }catch(Exception $e){
                return $this->returnError('Error al obtener información de usuario administrador');
            }
        }else{
            return $this->notPermission();
        }
    }

    public function update(Request $request, $id){
        $action     = $request->input('action');
        $user       = $this->getUser();
        $permission = $this->checkPermission($user,$this->endpoint,__FUNCTION__);   //cheque permisos para el endpoint y para el tipo de request.
        if($permission){
            switch($action){
                case 'updateStatus':
                    $objData = json_decode($request->getContent());
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
                default:
                    $objData = json_decode($request->getContent());
                    $formulario = $objData->formulario;
                    $result = $this->model->updateData($formulario,$id,$user);
                    try{
                        // $result = $this->model->updateData($formulario,$id,$user);
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
}
