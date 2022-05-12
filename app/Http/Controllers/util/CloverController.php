<?php

namespace App\Http\Controllers\util;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CloverController extends Controller{
    private $urlToken   = "";
    private $urlCharges = "";

    function __construct(){
        if(env('TRANSACTIONS') == 'sandbox'){
            $this->urlToken     = "https://token-sandbox.dev.clover.com/v1/tokens";
            $this->urlCharges   = "https://scl-sandbox.dev.clover.com/v1/charges";
            $this->urlCustomers = "https://scl-sandbox.dev.clover.com/v1/customers";
        }else{
            $this->urlToken     = "https://token.clover.com/v1/tokens";
            $this->urlCharges   = "https://scl.clover.com/v1/charges";
            $this->urlCustomers = "https://scl.clover.com/v1/customers";
        }
    }

    public function tokenizeCard($card,$apiKey){
        $data = array(
            'card' => array(
                'number'        => $card->card_number,
                'exp_month'     => substr($card->card_expiry,0,2),
                'exp_year'      => '20'.substr($card->card_expiry,2,2),
                'cvv'           => $card->card_securitycode
            )
        );

        $curl = curl_init();
        $optionsCurl = array(
            CURLOPT_URL             => $this->urlToken,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => '',
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => 0,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => 'POST',
            CURLOPT_POSTFIELDS      => json_encode($data),
            CURLOPT_HTTPHEADER      => array(
                'apikey: '.$apiKey,
                'Content-Type: application/json'
            )
        );

        curl_setopt_array($curl,$optionsCurl);
        $content    = curl_exec($curl);
        $error      = curl_errno($curl);
        $response   = curl_getinfo($curl);
        
        $objData = new \stdClass();
        $objData->content   = json_decode($content);
        $objData->error     = $error;
        $objData->response  = $response;
        curl_close($curl);
        return $objData;
    }

    public function processCardCharges($data,$privateToken,$tx_id){
        $curl = curl_init();
        $optionsCurl = array(
            CURLOPT_URL             => $this->urlCharges,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => '',
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => 0,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => 'POST',
            CURLOPT_POSTFIELDS      =>json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'authorization: Bearer '.$privateToken,
                'idempotency-key: 00'.$tx_id. Date('i'),
                'content-type: application/json',
                'accept: application/json'
            ),
        );

        curl_setopt_array($curl,$optionsCurl);
        $content    = curl_exec($curl);
        $error      = curl_errno($curl);
        $response   = curl_getinfo($curl);
        
        $objData = new \stdClass();
        $objData->content   = json_decode($content);
        $objData->error     = $error;
        $objData->response  = $response;
        curl_close($curl);
        return $objData;
    }

    // registra un cliente y establece el token de la tarjeta como tarjeta de multiples pagos.
    public function saveCardMultiPay($data,$privateToken){
        $curl = curl_init();
        $optionsCurl = array(
            CURLOPT_URL             => $this->urlCustomers,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => '',
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => 0,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => 'POST',
            CURLOPT_POSTFIELDS      =>json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'authorization: Bearer '.$privateToken,
                'content-type: application/json',
                'accept: application/json'
            ),
        );

        curl_setopt_array($curl,$optionsCurl);
        $content    = curl_exec($curl);
        $error      = curl_errno($curl);
        $response   = curl_getinfo($curl);
        
        $objData = new \stdClass();
        $objData->content   = json_decode($content);
        $objData->error     = $error;
        $objData->response  = $response;
        curl_close($curl);
        return $objData;
    }

    // Agrega un token de tarjeta como tarjeta de multiples pagos a un cliente existente.
    public function addCardMultiPayCustomers($data,$customer_id,$privateToken){
        $curl = curl_init();
        $optionsCurl = array(
            CURLOPT_URL             => $this->urlCustomers."/".$customer_id,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => '',
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => 0,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST   => 'PUT',
            CURLOPT_POSTFIELDS      =>json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'authorization: Bearer '.$privateToken,
                'content-type: application/json',
                'accept: application/json'
            ),
        );

        curl_setopt_array($curl,$optionsCurl);
        $content    = curl_exec($curl);
        $error      = curl_errno($curl);
        $response   = curl_getinfo($curl);
        
        $objData = new \stdClass();
        $objData->content   = json_decode($content);
        $objData->error     = $error;
        $objData->response  = $response;
        curl_close($curl);
        return $objData;
    }

}
