<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\MyController;
use App\Models\MenuModel;
use Illuminate\Http\Request;
use Exception;


class MenuController extends MyController{
    private $model;
    
    function __construct(){
        $this->model = new MenuModel();
    }
    

    function getMenu(){
        $userId = $this->getUser();
        try{
            $result = $this->model->getMenu($userId);
            return $this->returnData($result,'No tiene permisos en la aplicación actualmente.');
        }catch(Exception $e){
            return $this->returnError('Error al intentar obtener permisos de menu.');
        }
    }


}
