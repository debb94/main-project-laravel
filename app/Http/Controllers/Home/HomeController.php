<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\MyController;
use Illuminate\Http\Request;
use App\Models\HomeModel;
use App\Models\AppModel;
use Exception;

class HomeController extends MyController{
    private $model;
    public  $appModel;
    
    function __construct(){
        $this->model    = new HomeModel();
        $this->appModel = new AppModel();
    }

    public function index(){
        // try{
            $user = $this->getUser();
            $profile = $this->getProfile($user);
            $chart = null;
            switch($profile){
                case 'Superusuario':
                    $result = $this->model->getMainDataAdminSuperusuario();
                    $chart  = $this->model->getChartAdminSuperusuario();
                    $perfil= 'admin';
                break;
                case 'Empresa administración':
                    $result = $this->model->getMainDataAdminEmpresa($user);
                    $chart  = $this->model->getChartAdminEmpresa($user);
                    $perfil= 'admin';
                break;
                case 'Administrador':
                    $result = $this->model->getMainData($user);
                    $perfil = 'comun';
                break;
                case 'Junta':
                    $result = $this->model->getMainData($user);
                    $perfil = 'comun';
                break;
                case 'Titular':
                    $result = $this->model->getMainData($user);
                    $perfil = 'comun';
                break;
            }
            $extra = [
                'perfil'    => $perfil,
                'chart'     => $chart
            ];
            return $this->returnData($result,null,$extra);
        // }catch(Exception $e){
        //     return $this->returnError('Se produjo un error al consultar pantalla inicial');
        // }
    }

    public function create(){
        //
    }

    public function store(Request $request){
        //
    }

    public function show($id){

    }

    public function edit($id){
        //
    }

    public function update(Request $request, $id){
        //
    }

    public function destroy($id){
        //
    }
}
