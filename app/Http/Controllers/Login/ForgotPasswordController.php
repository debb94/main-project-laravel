<?php

namespace App\Http\Controllers\Login;
use App\Http\Controllers\MyController;
use Illuminate\Http\Request;
use App\Models\ForgotPasswordModel;
use Exception;
use App\Http\Controllers\util\BoldMail;

class ForgotPasswordController extends MyController{
    private $model;

    function __construct(){
        $this->model        = new ForgotPasswordModel();
    }
    public function forgotPassword(Request $request){
        $action = $request->input('action');
        switch($action){
            case 'request-password-change':
                // try{
                    $objData = json_decode($request->getContent());
                    $correo = $objData->fcorreo;
                    // genero llave aleatoria
                    $key = $this->generateKey(30);
                    // Actualizo usuario para que pueda modificar la contraseña.
                    $result = $this->model->flagPasswordUser($correo,$key);
                    if(sizeof($result) > 0){
                        // consulto persona - para obtener nombre.
                        $user       = $result[0]->usuario_id;
                        $persona    = $this->model->getPerson($user);
                        $client     = $persona[0]->nombre_completo;
                        // envio correo 
                        $mail = new BoldMail();
                        $data = [
                            'subject'   => "Pago Easy - Recuperar Contraseña",
                            'recipient' => $correo,
                            'client'    => $client,
                            'keyRecovery'  =>$key
                        ];
                        // ENVIO DE CORREO DE RECUPERACION
                        $send = $mail->sendTemplate('PASSWORD-RECOVERY',$data);
                        if($send){
                            return $this->returnOk("Por favor verifique su bandeja de correo, proximamente llegará un correo para recuperar su contraseña");
                        }else{
                            return $this->returnError("Se produjo un error al intentar enviar correo de recuperación, intente mas tarde.");
                        }
                    }else{
                        return $this->returnError("Usuario invalido o desactivado");
                    }
                // }catch(Exception $e){
                //     return $this->returnError("Se produjo un error al intentar solicitar el cambio de contraseña, intente mas tarde.");
                // }
            break;
            case 'change-password':
                try{
                    $objData    = json_decode($request->getContent());
                    $keyRecovery= $objData->keyRecovery;
                    $pass       = $this->desencrypt($objData->pass);
                    $options = ['cost'=> 12];
                    $pass = password_hash($pass,PASSWORD_DEFAULT,$options);
                    $result = $this->model->updatePassword($pass,$keyRecovery);
                    if(sizeof($result)>0){
                        return $this->returnOk('Contraseña actualizada correctamente');
                    }else{
                        return $this->returnError('Error al intentar cambiar contraseña, su enlace de recuperación ya venció.');
                    }
                }catch(Exception $e){
                    return $this->returnError("Error al intentar cambiar contraseña");
                }
            break;
        }
    }
}
