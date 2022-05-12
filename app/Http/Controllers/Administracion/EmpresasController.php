<?php

namespace App\Http\Controllers\Administracion;
use App\Http\Controllers\MyController;
use App\Models\EmpresasModel;
use App\Models\AppModel;
use Illuminate\Http\Request;
use Exception;

class EmpresasController extends MyController{
    private $model;
    public  $appModel;
    private $endpoint   = 'administracion/empresas';
    
    function __construct(){
        $this->model = new EmpresasModel();
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
                        return $this->returnError('Error al consultar paises, estados y ciudades');
                    }
                break;
                case 'getEstado':
                    $pais   = $request->input('pais');
                    return $this->getState($pais);
                break;
                case 'getCiudad':
                    $pais   = $request->input('pais');
                    $estado = $request->input('estado');
                    return $this->getCity($pais,$estado);
                break;
                default:
                    $result = $this->model->get();
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
                return $this->returnError('Se produjo un error al insertar datos, o ya existe la empresa');
            }
        }else{
            return $this->notPermission();
        }
    }

    public function show($id,Request $request){
        $user       = $this->getUser();
        $permission = $this->checkPermission($user,$this->endpoint,__FUNCTION__);   //cheque permisos para el endpoint y para el tipo de request.
        if($permission){
            try{
                $result = $this->model->get($id);
                // dependencias
                $data = null;
                $dependencies = $request->input('dependencies');
                if(isset($dependencies)){
                    $dependencies = json_decode($dependencies);
                    $data = $this->getDependencies($dependencies,$result[0]);
                    // try{
                    // }catch(Exception $e){
                    //     $this->returnError('Error #601: Error al procesar dependencias, por favor reporte este error.');
                    // }
                }
                return $this->returnData($result,'No se obtuvo resultado de la empresa',$data);
            }catch(Exception $e){
                return $this->returnError('Error al obtener información de la empresa');
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
                    try{
                        $objData = json_decode($request->getContent());
                        $formulario = $objData->formulario;
                        $result = $this->model->updateData($formulario,$id,$user);
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