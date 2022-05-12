<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\MyController;
use Illuminate\Http\Request;
use App\Models\Reportes\PagosCondominiosModel;
use App\Models\AppModel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use Exception;

class PagosCondominiosController extends MyController{
    
    private $model;
    private $endpoint   = 'reportes/pago-condominio';

    public function __construct(){
        $this->appModel = new AppModel();
        $this->model = new PagosCondominiosModel;
    }

    public function index(Request $request){
        $user       = $this->getUser();
        $permission = $this->checkPermission($user,$this->endpoint,__FUNCTION__);
        if($permission){    // tengo permisos
            $action = $request->input('action');
            switch($action){
                case 'getParamsUpdate':
                break;
                case 'getFilters':
                    try{
                        $result = $this->appModel->getComplejosFilter($user);
                        return $this->returnData($result);
                    }catch(Exception $e){
                        return $this->returnError("Se produjo un error al consultar filtros");
                    }
                break;
                default:
                    try{
                        $complejoId     = ($request->input('complejoId') != '') ? $request->input('complejoId'):null;
                        $dStart         = ($request->input('dStart') != '') ? $request->input('dStart'):null;
                        $dEnd           = ($request->input('dEnd') != '') ? $request->input('dEnd'):null;
                        $result         = $this->model->getInfo($user,null,$complejoId,$dStart,$dEnd);
                        return $this->returnData($result);
                    }catch(Exception $e){
                        return $this->returnError("Se ha producido un error");
                    }
                break;
            }
        }else{  // permisos denegados.
            return $this->notPermission();
        }
    }

    public function store(Request $request){
        $user = $this->getUser();
        
        $user       = $this->getUser();
        $permission = $this->checkPermission($user,$this->endpoint,__FUNCTION__);
        if($permission){    // tengo permisos
            switch($request->input('action')){
                case 'getReportExcel':
                    $objData    = json_decode($request->getContent());
                    $complejoId = (isset($objData->complejoId) ) ? $objData->complejoId : null;
                    $dateStart  = (isset($objData->dStart) ) ? $objData->dStart : null;
                    $dateEnd    = (isset($objData->dEnd) ) ? $objData->dEnd : null;
                    return $this->generateReport($complejoId, $dateStart,$dateEnd);
                break;
                default:
                    return false;
                break;
            }
        }else{  // permisos denegados.
            return $this->notPermission();
        }
    }

    public function generateReport($complejoId, $dateStart,$dateEnd){
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // lista de deposito mensual a condominio
        $data = $this->model->getDataReportCondominiumPayments($complejoId, $dateStart,$dateEnd);
        $headers = ['Condominio', 'Mantenimiento Neto','Fee','Total x Mantenimiento', 'Producto Neto','Fee','Total x Producto', 'Derramas Neta','Fee','Total x Derramas', 'Multas Neta','Fee','Total x Multas'];
        $columns        = sizeof($headers);

        $arrayData      = [];
        $arrayData[]    = $headers;
        foreach($data as $key => $val){
            $arrayLine = [];
            // $arrayLine[] = $val->tx_id;
            $arrayLine[] = $val->complejo_nombre;
            $arrayLine[] = ($val->amount_mantenimiento - $val->fee_mantenimiento);
            $arrayLine[] = $val->fee_mantenimiento;
            $arrayLine[] = $val->amount_mantenimiento;

            $arrayLine[] = ($val->amount_productos - $val->fee_productos);
            $arrayLine[] = $val->fee_productos;
            $arrayLine[] = $val->amount_productos;

            $arrayLine[] = ($val->amount_derramas - $val->fee_derramas);
            $arrayLine[] = $val->fee_derramas;
            $arrayLine[] = $val->amount_derramas;

            $arrayLine[] = ($val->amount_multas - $val->fee_multas);
            $arrayLine[] = $val->fee_multas;
            $arrayLine[] = $val->amount_multas;

            $arrayData[] = $arrayLine;
        }

        $sheet->fromArray(
            $arrayData,  // The data to set
            NULL,        // Array values with this value will not be set
            'A4'         // Top left coordinate of the worksheet range where we want to set these values (default is A1)
        );

        // seteo el ancho de las columnas.
        $letterColumns = $this->getColumns($columns);
        foreach($letterColumns as $val){
            $sheet->getColumnDimension($val)->setAutoSize(true);
        }
        // seteo titulos
        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', 'Pagos a Condominios         Periodo: '. $dateStart." a ". $dateEnd);

        // estilizo encabezado y titulos
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(18);

        $styleArray = [
            'font' => [
                "bold" => true,
                "size" => 14
            ]
        ];
        $sheet->getStyle('A4:'.$letterColumns[sizeof($letterColumns)-1].'4')->applyFromArray($styleArray);
        // estilizo data.
        $styleArray = [
            'font' => [
                "bold" => false,
                "size" => 14
            ]
        ];
        $sheet->getStyle('A5:'.$letterColumns[sizeof($letterColumns)-1].(5+sizeof($arrayData)-2))->applyFromArray($styleArray);

        // en cada una de las hojas colocamos todas las transacciones.

        $spreadsheet->getProperties()
                ->setCreator("Pago Easy - Daniel Bolívar")
                ->setLastModifiedBy("Pago Easy - Daniel Bolívar")
                ->setTitle("Pagos a condominios")
                ->setSubject("Pagos a condominios")
                ->setDescription("Pagos a condominios. generated by CRM Pago Easy");
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

}
