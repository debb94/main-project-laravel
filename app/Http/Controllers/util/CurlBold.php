<?php
/**
 * @author      Daniel Bolivar.
 * @version     v1.0.0
 * @internal    Libreria para el manejos de peticiones curl a servidores. 
 */


 class CurlBold{

    private $type   = "ContentType: application/json";
    private $accept = "";

    public function getRequest($url){
        $curl = curl_init();
        $optionsCurl = array(
            CURLOPT_URL             => $url,
            CURLOPT_TIMEOUT         => 60,
            CURLOPT_HTTPHEADER      => array(
                                        $this->type,
                                       'accept application/json'
                                        ),
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_SSL_VERIFYHOST  => 0,
            CURLOPT_SSL_VERIFYPEER  => 0
        );
        curl_setopt_array($curl,$optionsCurl);
        $content    = curl_exec($curl);
        $error      = curl_errno($curl);
        $response   = curl_getinfo($curl);
        curl_close($curl);

        return $this->handlerResponse($content,$error,$response);
    }

    public function handlerResponse($content,$error,$response){
        if($response['http_code'] == 200){ // respuesta correcta.
            $objData = new \stdClass();
            $objData->content   = json_decode($content);
            $objData->error     = $error;
            $objData->response  = $response;
            return $objData;
        }else{ // manejo de errores
            $objData = new \stdClass();
            $objData->success = false;
            $objData->message = $content;
            return $objData;
        }
    }


    public function setHeader($headers){
        // recorro array y agrego al existente.
    }

    /**
     * @param   type entero que indica el tipo de contenido, 1-application/json | 2-application/html
     */
    public function setType($type){
        switch($type){
            case 1:
                $this->$type = 'ContentType: application/json';
            break;
            case 2:
                $this->$type = 'ContentType: application/html';
            break;
            default:
                $this->$type = 'ContentType: application/json';
            break;
        }
    }

 }
?>