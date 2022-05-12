<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\MyController;
use App\Models\ComplejosModel;
use App\Models\AppModel;
use Illuminate\Http\Request;
use Exception;

class ComplejosController extends MyController{
    private $model;
    public  $appModel;
    private $endpoint   = 'administracion/complejos';
    
    function __construct(){
        $this->model = new ComplejosModel();
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
                case 'getState':
                    $pais   = $request->input('pais');
                    return $this->getState($pais);
                break;
                case 'getCities':
                    $pais   = $request->input('pais');
                    $estado = $request->input('estado');
                    return $this->getCity($pais,$estado);
                break;
                default:
                    $result = $this->model->get($user);
                break;
            }
            return $this->returnData($result);
        }else{  // permisos denegados.
            return $this->notPermission();
        }
        // echo $user;
        // return "ok";
        // $action = $request->input('action');

        // $result = $this->model->getComplejos();
        
        // return $this->returnData($result,);

        // print_r($action);
        // return "ok";
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
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

    public function show($id, Request $request){
        $user       = $this->getUser();
        $permission = $this->checkPermission($user,$this->endpoint,__FUNCTION__);   //cheque permisos para el endpoint y para el tipo de request.
        if($permission){
            try{
                $result = $this->model->get($user,$id);
                // dependencias
                $data = null;
                $dependencies = $request->input('dependencies');
                if(isset($dependencies)){
                    $dependencies = json_decode($dependencies);
                    $data = $this->getDependencies($dependencies,$result[0]);
                }
                return $this->returnData($result,'No se obtuvo resultado de complejo',$data);
            }catch(Exception $e){
                return $this->returnError('Error al obtener información de complejo');
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
                    // $objData = json_decode($request->getContent());
                    //     $status = $objData->status;
                    //     $result = $this->model->updateStatus($status,$id,$user);
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
                    try{
                        $result = $this->model->updateData($formulario,$id,$user);
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
