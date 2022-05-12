<?php

namespace App\Http\Controllers\util;

use App\Http\Controllers\MyController;
use App\Http\Controllers\util\BoldMail;
use Illuminate\Http\Request;

class SupportMessageController extends MyController{
    
    public function sendMessage(Request $request){
        try{
            $mail               = new BoldMail();
            $objData            = json_decode($request->getContent(),true);
            $data               = $objData['formulario'];
            // cliente
            $data['subject']   = "Soporte";
            $data['recipient'] = $data['mail'];
            $mail->sendTemplate('SUPPORT-MESSAGE',$data);

            // administrador
            // $data['recipient'] = "daniel.bolivar.freelance@gmail.com";
            $data['recipient'] = "admin@pagoeasy.com";
            $data['subject']   = "Soporte";
            $mail->sendTemplate('SUPPORT-MESSAGE-ADMIN', $data);
            
            return $this->returnOk("Una persona de soporte se pondrá en contacto contigo");
        }catch(Exception $e){
            return $this->returnError('Se produjo un error al contactar a soporte.');
        }
    }
}