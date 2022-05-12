<?php

namespace App\Http\Middleware;
use Firebase\JWT\JWT;
use App\Http\Controllers\MyController;
use Closure;
use Exception;

class Authentication extends MyController{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next){

        $header = apache_request_headers();
        try{
            if(isset($header['Authorization'])){
                $jwt = $header['Authorization'];
            }else{
                $jwt = $header['authorization'];
            }
            $key = env('KEY_ACCESS');
            $decode = JWT::decode($jwt,$key,array('HS256'));
            if(is_object($decode)){
                $expire  = $this->desencrypt($decode->expire);
                if($expire <= time()){
                    $objData = array(
                        'success'   => false,
                        'message'   => 'Su sesión ha caducado',
                        'action'    => 'closeSession'
                    );
                    echo json_encode($objData);
                    exit();
                }else{
                    // echo "aun es valido";
                    return $next($request);
                }
                // return $decode;
            }else{
                // echo "error de token";
                $objData = array(
                    'success'   => false,
                    'message'   => 'Su sesión ha caducado',
                    'action'    => 'closeSession'
                );
                echo json_encode($objData);
                exit();
                // return "error";
            }
        }catch(Exception $e){
            $objData = array(
                'success'   => false,
                'message'   => 'Su sesión ha caducado',
                'action'    => 'closeSession'
            );
            echo json_encode($objData);
            exit();
        }
        // $jwt = $header['Authorization'];
        // $key = env('KEY_ACCESS');
        // $decode = JWT::decode($jwt,$key,array('HS256'));
        // if(is_object($decode)){
        //     $expire  = $this->desencrypt($decode->expire);
        //     if($expire <= time()){
        //         $objData = array(
        //             'success'   => false,
        //             'message'   => 'Su sesión ha caducado',
        //             'action'    => 'closeSession'
        //         );
        //         echo json_encode($objData);
        //         exit();
        //     }else{
        //         // echo "aun es valido";
        //         return $next($request);
        //     }
        //     // return $decode;
        // }else{
        //     // echo "error de token";
        //     exit();
        //     // return "error";
        // }

        // return $next($request);
    }
}
