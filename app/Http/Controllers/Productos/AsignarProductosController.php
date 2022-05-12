<?php

namespace App\Http\Controllers\Productos;
use App\Http\Controllers\MyController;
use App\Models\AppModel;
use App\Models\AsignarProductosModel;
use App\Models\GestionProductosModel;
use App\Models\TransaccionesProductosModel;
use Illuminate\Http\Request;
use Exception;

class AsignarProductosController extends MyController{
    private $model;
    public  $appModel;
    private $transaccionesProductosModel;
    private $endpoint   = 'productos/asignar-productos';
    
    function __construct(){
        $this->model    = new AsignarProductosModel();
        $this->transaccionesProductosModel = new TransaccionesProductosModel();
        $this->appModel = new AppModel();
    }

    public function index(Request $request){
        $user       = $this->getUser();
        $permission = $this->checkPermission($user,$this->endpoint,__FUNCTION__);
        $action = $request->input('action');
        if($permission){    // tengo permisos
            $action = $request->input('action');
            switch($action){
                case 'getParamsUpdate':
                    try{
                        $result = $this->model->getParamsUpdate();
                        return $this->returnData($result);
                    }catch(Exception $e){
                        return $this->returnError('Error al consultar parametros de productos');
                    }
                break;
                case 'getPrecioProduct':
                    try{
                        $producto_id = $request->input('producto_id');
                        $result = $this->model->getPrecioProduct($producto_id);
                        return $this->returnData($result);
                    }catch(Exception $e){
                        return $this->returnError('Error al consultar parametros de productos');
                    }
                break;
                default:
                    $result = $this->model->get($user);
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

                // identifico el usuario.
                $propiedad_id       = $form->propiedad_id;
                $usuarioPropiedad   = $this->appModel->getUserByProperty($propiedad_id);
                $usuario            = $usuarioPropiedad[0]->usuario_id;
                $propiedad_nombre   = $usuarioPropiedad[0]->propiedad_nombre;
                $form->usuario_id   = $usuario;

                // transaccion a registrar
                $tx = new \stdClass();
                // consulto producto.
                $product = $this->model->getProductById($form->producto_id);
                $product = $product[0];

                if($form->productoasignado_estado == 'PENDIENTE_ENTREGA'){
                    $form->productoasignado_pago = 'NOW()';
                    $estadoTransaccion  = 'A';
                    $tx->tx_fechapago   = 'NOW()';
                }else if($form->productoasignado_estado == 'ENTREGADO'){
                    $form->productoasignado_entrega = 'NOW()';
                    $estadoTransaccion  = 'A';
                    $tx->tx_fechapago   = 'NOW()';
                }else{ // pendiente de pago 
                    $estadoTransaccion = 'P';
                }
                
                $tx->tx_referencia  = "Propiedad - Producto: $propiedad_nombre - $product->producto_nombre";
                $tx->propiedad_id   = $form->propiedad_id;
                $tx->usuario_id     = $usuario;
                $tx->tx_valortotalpagar = $form->productoasignado_total;
                $tx->tx_descuento   = 0;
                $tx->tx_estado      = $estadoTransaccion;

                $result = $this->transaccionesProductosModel->insertData($tx,$user);
                if(sizeof($result)>0){
                    $tx_id          = $result[0]->tx_id;
                    $form->tx_id    = $tx_id;
                    $result         = $this->model->insertData($form,$user);
                    return $this->returnOk('Datos guardados exitosamente');
                }else{
                    return $this->returnError('Se produjo un error al insertar transacción');
                }
            }catch(Exception $e){
                return $this->returnError('Se produjo un error al insertar datos');
            }
        }else{
            return $this->notPermission();
        }
    }

    public function show($id){
        $user       = $this->getUser();
        $permission = $this->checkPermission($user,$this->endpoint,__FUNCTION__);   //cheque permisos para el endpoint y para el tipo de request.
        if($permission){
            try{
                $result = $this->model->get($user,$id);
                return $this->returnData($result,'No se pudo obtener información');
            }catch(Exception $e){
                return $this->returnError('Error al consultar información');
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
                    $objData = json_decode($request->getContent());
                    try{
                        $status = $objData->status;
                        if($id == 'null'){
                            $codigos = $objData->codigos;
                        }else{
                            $codigos = null;
                        }

                        // obtengo id de transaccion
                        $productoAsignado = $this->model->getAssingnedProduct($id);
                        $tx_id = $productoAsignado[0]->tx_id;

                        if($status != 'PENDIENTE_PAGO' && $productoAsignado[0]->productoasignado_estado != $status){    
                            // actualizo y cierro la transaccion.
                            $status_tx = 'A';
                            $result = $this->model->updateTransactionProductos($user,$tx_id,$status_tx);
                            $result = $this->model->updateStatus($status,$id,$codigos,$user);
                            return $this->returnOk('Actualizado correctamente');
                        }else if($status == 'PENDIENTE_PAGO'){
                            $status_tx = 'P';
                            $result = $this->model->updateTransactionProductos($user,$tx_id,$status_tx);
                            $result = $this->model->updateStatus($status,$id,$codigos,$user);
                            return $this->returnOk('Actualizado correctamente');
                        }else{
                            return $this->returnOk('La asignación de producto no pudo ser actualizada, verifique el estado');
                        }
                    }catch(Exception $e){
                        return $this->returnError('Se produjo un error al intentar actualizar estado');
                    }
                break;
                default:
                    $objData = json_decode($request->getContent());
                    $formulario = $objData->formulario;
                    $result = $this->model->updateData($formulario,$id,$user);
                    try{
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
