<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });


// apiRoute::post('login','Login\LoginController@authentication');

Route::group(['middleware'=>'cors'],function(){
    Route::post('/login','Login\LoginController@authentication');
    Route::post('/register','Login\RegisterController@register');
    Route::get('/register','Login\RegisterController@getPropiedad');
    Route::post('/forgot-password','Login\ForgotPasswordController@forgotPassword');
    //Route::get('/desencriptacion','util\EncryptRSAController@index');
});

Route::group(['middleware'=>['cors','authentication']],function(){
    // home
    Route::apiResource('/home','Home\HomeController');
    Route::post('/support-message','util\SupportMessageController@sendMessage');
    // ADMINISTRACION
    Route::apiResource('/administracion/empresas','Administracion\EmpresasController');
    Route::apiResource('/administracion/complejos','Administracion\ComplejosController');
    Route::apiResource('/administracion/edificios','Administracion\EdificiosController');
    Route::apiResource('/administracion/propiedades','Administracion\PropiedadesController');
    Route::apiResource('/administracion/usuarios','Administracion\UsuariosController');
    Route::apiResource('/administracion/juntas','Administracion\JuntasController');
    Route::apiResource('/administracion/comunicados','Administracion\ComunicadosController');
    Route::apiResource('/administracion/aprobar-usuarios','Administracion\UsuariosController');
    Route::apiResource('/administracion/estacionamientos','Administracion\EstacionamientosController');
    // Transacciones
    Route::apiResource('/transacciones/pendientes','Transacciones\TxPendientesController'); // pendientes
    Route::apiResource('/transacciones/realizadas','Transacciones\TxRealizadasController'); // realizadas
    Route::apiResource('/transacciones/todas','Transacciones\TxTodasController'); // todas las transacciones
    // Productos
    Route::apiResource('/productos/gestion-productos','Productos\GestionProductosController');
    Route::apiResource('/productos/asignar-productos','Productos\AsignarProductosController');
    Route::apiResource('/productos/checkout','Productos\TxProductosController');
    // Multas
    Route::apiResource('/multas/gestion-multas','Multas\MultasController');
    Route::apiResource('/multas/asignar-multas','Multas\AsignarMultasController');
    Route::apiResource('/multas/checkout','Multas\TxMultasController');
    // Derramas
    Route::apiResource('/derramas/asignar-derramas','Derramas\AsignarDerramasController');
    Route::apiResource('/derramas/checkout','Derramas\TxDerramasController');
    
    Route::apiResource('/transacciones/checkout','Transacciones\CheckoutController');       // checkout
    // configuracion
    Route::apiResource('/configuracion/administrar-tarjetas','Configuracion\AdministrarTarjetasController');
    Route::apiResource('/configuracion/debitos-automaticos','Configuracion\DebitosAutomaticosController');
    Route::apiResource('/configuracion/usuarios','Configuracion\UsuariosController');
    Route::apiResource('/configuracion/cuentas','Configuracion\CuentasController');
    Route::apiResource('/configuracion/presupuestos','Configuracion\PresupuestosController');
    Route::apiResource('/configuracion/participaciones','Configuracion\ParticipacionesController');
    Route::apiResource('/configuracion/intereses','Configuracion\InteresesController');
    Route::apiResource('/configuracion/usuarios-empresas','Configuracion\UsuariosEmpresasController');
    Route::apiResource('/configuracion/usuarios-administradores','Configuracion\UsuariosAdministradoresController');
    Route::apiResource('/configuracion/usuarios-junta','Configuracion\UsuariosJuntaController');
    Route::apiResource('/configuracion/pasarelas-complejos','Configuracion\PasarelasComplejosController');
    // menu
    Route::post('/menu','Menu\MenuController@getMenu');
    // general
    Route::apiResource('/general','GeneralController');
    // Reportes
    Route::apiResource('/reportes/pago-condominio','Reportes\PagosCondominiosController');
});

// Route::group(['prefix' => 'multas','middleware'=>['cors','authentication']],function(){
//     Route::apiResource('{id}','Multas\MultasController');
//     Route::apiResource('/asignar-multas','Multas\AsignarMultasController');
//     Route::apiResource('/checkout','Multas\TxMultasController');
// });