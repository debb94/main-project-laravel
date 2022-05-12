<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Exception;

class PerfilesUsuariosModel extends Model{
    public $table           = "perfiles_usuarios";
    public $identificador   = "perfil_id";
    public $creador         = "perfilusuario_creadopor";
    public $actualizador    = "perfilusuario_actualizadopor";
    public $factualizacion  = "perfilusuario_factualizacion";
    public $sqlEstado       = "perfilusuario_estado";

    // actualizacion
    public function updateData($form,$id,$user){
        // usuario actualizacion
        $form->{$this->actualizador} = $user;
        // fecha actualizacion
        $form->{$this->factualizacion} = 'now()';

        $sql = "UPDATE $this->table set ";
        $sqlSets = [];
        $sqlValues = [];
        foreach($form as $key => $value){
            $sqlSets[] = " $key = ? ";
            $sqlValues[] = $value;
        }
        $sqlSets = implode(',',$sqlSets);
        $sql .= $sqlSets . " where usuario_id = ?";

        // id actualizacion
        $sqlValues[] = $id; 
        $result = DB::select($sql,$sqlValues);
        return $result;
    }
    // insercion
    public function insertData($form,$user,$usuario_id = null){
        if($usuario_id!=null){
            $form->usuario_id = $usuario_id;
            $form->{$this->sqlEstado} = 1;
        }
        $form->{$this->creador} = $user;
        foreach($form as $key=>$value){
            if($value != ''){
                $sqlInsert[]    = $key;
                $sqlBind[]      = '?';
                $sqlValues[]    = $value;
            }
        }
        $sqlInsert = implode(',',$sqlInsert);
        $sqlBind = implode(',',$sqlBind);
        $sql = "INSERT INTO $this->table ($sqlInsert) values($sqlBind)";
        $result = DB::insert($sql,$sqlValues);
        return $result;
    }

    // eliminacion desactivacion
    public function inactive($id){
        $sql    = "UPDATE $this->table set $this->sqlEstado = 0 where $this->identificador = ?";
        $result = DB::select($sql,array($id));
        return $result;
    }


    public function deletePerfil($id){
        $sql    = "DELETE from $this->table where $this->identificador = ?";
        $result = DB::select($sql,array($id));
        return $result;
    }
}
