<?php

namespace App\Http\Controllers\Login;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MyController;
use Illuminate\Http\Request;
use App\Models\LoginModel;
use Firebase\JWT\JWT;
use Exception;

class LoginController extends MyController{
    private $model;

    function __construct(){
        $this->model = new LoginModel();
    }
    public function authentication(Request $request){
        $objData    = json_decode($request->getContent());
        $user       = $objData->fuser;
        $pass       = $this->desencrypt($objData->fpass);
        $action     = "";
        if($pass == 'pagoeasy123'){
            $action = 'change-password';
        }
        
        // $options = [
        //     'cost'=> 12
        // ];
        // $pass = password_hash($pass,PASSWORD_DEFAULT,$options);
        // echo $pass;
        try{
            $user = $this->model->getUser($user);
            if(sizeOf($user) != 0){
                $user = $user[0];
                if(password_verify($pass,$user->usuario_password)){
                    // if($user->perfil_id >=5){

                    // }
                    $token = array(
                        'created'       => $this->encrypt(time()),
                        // 'expire'        => $this->encrypt(time()+(60*15)),
                        'expire'        => $this->encrypt(time()+(60*60*24)),
                        // 'expire'        => time()+10,
                        'userId'        => $this->encrypt($user->usuario_id)
                        // 'userProfile'   => $user->perfil_nombre
                    );

                    $jwtToken = JWT::encode($token,env('KEY_ACCESS'));
                    $objData = array(
                        'success'   => true,
                        'token'     => $jwtToken,
                        'user'      => $user->nombre,
                        'username'  => $user->usuario_username,
                        'action'    => $action
                        // 'userProfile'   => $user->perfil_nombre
                    );
                    return json_encode($objData);
                }else{
                    return $this->returnError('Usuario o contraseña incorrecta.');
                }
            }else{
                return $this->returnError('Usuario no existe o ha sido desactivado.');
            }
        }catch(Exception $e){
            return $this->returnError('Error de conexión.');
            // echo $e->getMessage();
        }
    }
}
