<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\MyController;
use App\Models\AppModel;

class GeneralController extends MyController{

    public function __construct(){
        $this->model = new AppModel();
    }
    
    public function index(Request $request){
        $user       = $this->getUser();
        $action     = $request->input('action');
        switch($action){
            case 'getStates':
                try{
                    $country     = $request->input('country');
                    $result = $this->model->getStates($country);
                    return $this->returnData($result);
                }catch(Exception $e){
                    return $this->returnError('Error al consultar Estados');
                }
            break;
            case 'getCities':
                try{
                    $country    = $request->input('country');
                    $state      = $request->input('state');
                    $result = $this->model->getCities($country,$state);
                    return $this->returnData($result);
                }catch(Exception $e){
                    return $this->returnError('Error al consultar Ciudades');
                }
            break;
            default:
                // $result = $this->model->get($user);
            break;
        }
        return $this->returnData($result,"No hay registros");
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
