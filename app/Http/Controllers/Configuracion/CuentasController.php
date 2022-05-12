<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MyController;
use Illuminate\Http\Request;
use App\Models\CuentasModel;
use App\Models\AppModel;

class CuentasController extends MyController{
    
    private $model;
    public  $appModel;
    private $endpoint = "configuracion/cuentas";

    function __construct(){
        $this->model = new CuentasModel();
        $this->appModel = new AppModel();
    }

    public function index(Request $request){
        $user       = $this->getUser();
        $permission = $this->checkPermission($user,$this->endpoint,__FUNCTION__);
        if($permission){    // tengo permisos
            $action = $request->input('action');
            switch($action){
                case 'getParamsUpdate':
                    $result = $this->model->getParamsUpdate();
                break;
                case 'info':
                    $result = $this->model->getInfo($user);
                break;
                case 'cuenta':
                    $result = $this->model->getCuenta($user);
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
            $action = $request->input('action');
            switch($action){
                case 'change-password':
                    try{
                        $objData = json_decode($request->getContent());
                        $pass = $this->desencrypt($objData->pass);
                        $options = ['cost'=> 12];
                        $pass = password_hash($pass,PASSWORD_DEFAULT,$options);
                        $result = $this->model->updatePassword($pass,$user);
                        if(sizeof($result)>0){
                            return $this->returnOk("Contraseña actualizada correctamente.");
                        }else{
                            return $this->returnError("Se produjo un error al intentar actualizar su contraseña, por favor intente mas tarde.");
                        }
                    }catch(Exception $e){
                        return $this->returnError('Se produjo un error al intentar actualizar su contraseña');
                    }
                break;
                case 'change-info':
                    try{
                        $objData = json_decode($request->getContent());
                        $persona_id = $objData->persona_id;
                        unset($objData->persona_id);
                        $result = $this->model->updateData($objData,$persona_id,$user);
                        if($result){
                            return $this->returnOk("Información actualizada correctamente.");
                        }else{
                            return $this->returnError("Se produjo un error al intentar actualizar su información, por favor intente mas tarde.");
                        }
                    }catch(Exception $e){
                        return $this->returnError('Se produjo un error al intentar actualizar su información');
                    }
                break;
            }
        }else{
            return $this->notPermission();
        }
    }

    public function show($id){
    }

    public function update(Request $request, $id){
    }

    public function destroy($id){    
    }
}
