<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MyController;
use Illuminate\Http\Request;
use App\Models\UsuariosModel;
use App\Models\AppModel;


class UsuariosController extends MyController{
    private $model;
    public $appModel;
    private $endpoint = "configuracion/usuarios";

    function __construct(){
        $this->model = new UsuariosModel();
        $this->appModel = new AppModel();
    }

    public function index(Request $request){
        $user       = $this->getUser();
        $permission = $this->checkPermission($user,$this->endpoint,__FUNCTION__);
        if($permission){    // tengo permisos
            $result =  $this->model->get($user);
            return $this->returnData($result);
        }else{  // permisos denegados.
            return $this->notPermission();
        }
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
