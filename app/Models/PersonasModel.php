<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Exception;

class PersonasModel extends Model{
    public $table           = "personas";
    public $identificador   = "persona_id";
    public $creador         = "persona_creadopor";
    public $actualizador    = "persona_actualizadopor";
    public $factualizacion  = "persona_factualizacion";
    public $sqlEstado       = "persona_estado";

    public function get($id = null){
        if($id == null){
            $sql = "SELECT
                        us.usuario_id,
                        us.usuario_username,
                        us.usuario_estado,
                        (select 
                            coalesce(pe1.persona_nombre1||' '||pe1.persona_apellido1,pe1.persona_nombre1) 
                            from usuarios us1 
                            inner join personas pe1 
                                on pe1.persona_id = us1.persona_id
                            where us1.usuario_id = us.usuario_creadopor
                        ) as usuario_creadopor,
                        us.usuario_fcreacion,
                        coalesce(pe.persona_nombre1||' '||pe.persona_apellido1,pe.persona_nombre1) as persona_nombrecompleto,
                        pe.*
                    from 
                        usuarios us
                    inner join personas pe
                        on pe.persona_id = us.persona_id
                    order by us.usuario_fcreacion,persona_nombrecompleto";
            $result = DB::select($sql);
        }else{
            $sql = "SELECT
                        us.usuario_id,
                        us.usuario_username,
                        us.usuario_estado,
                        (select 
                            coalesce(pe1.persona_nombre1||' '||pe1.persona_apellido1,pe1.persona_nombre1) 
                            from usuarios us1 
                            inner join personas pe1 
                                on pe1.persona_id = us1.persona_id
                            where us1.usuario_id = us.usuario_creadopor
                        ) as usuario_creadopor,
                        us.usuario_fcreacion,
                        coalesce(pe.persona_nombre1||' '||pe.persona_apellido1,pe.persona_nombre1) as persona_nombrecompleto,
                        pe.*
                    from 
                        usuarios us
                    inner join personas pe
                        on pe.persona_id = us.persona_id
                    where us.usuario_id = ?
                    order by us.usuario_fcreacion,persona_nombrecompleto";
            $result = DB::select($sql,array($id));
        }
        return $result;
    }

    public function getParamsUpdate(){
        $sql = "SELECT 
                    (select 
                    coalesce(json_object_agg(tmp.codigo,tmp.nombre),'{}')::text
                    from(
                        select 
                            pa.parametro_valor as codigo,
                            pa.parametro_nombre as nombre
                        from 
                            parametros_configuracion pa
                        where 
                            pa.parametro_tipo = 'ESTADO_CIVIL'
                            and pa.parametro_estado = 1
                            order by pa.parametro_orden
                        ) as tmp) as estado_civil,
                    (select 
                        coalesce(json_object_agg(tmp.codigo,tmp.nombre),'{}')::text
                        from (select 
                                    pa.parametro_valor as codigo,
                                    pa.parametro_nombre as nombre
                                from 
                                    parametros_configuracion pa
                                where 
                                    pa.parametro_tipo = 'SEXO'
                                    and pa.parametro_estado = 1
                                    order by pa.parametro_orden
                        ) as tmp) as sexo";
        $result = DB::select($sql);
        return $result;
    }

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
        $sql .= $sqlSets . " where $this->identificador = ?";

        // id actualizacion
        $sqlValues[] = $id; 
        $result = DB::update($sql,$sqlValues);
        return $result;
    }
    // actualizacion estatus
    public function updateStatus($status,$id,$codigos = null,$user){
        if($id == 'null'){
            $codigos = implode(',',$codigos);
            $sql ="UPDATE $this->table set 
                $this->sqlEstado = ?,
                $this->actualizador = ?,
                $this->factualizacion = now()
                where $this->identificador in ($codigos)";
            $result = DB::update($sql,array($status,$user));
        }else{
            $sql ="UPDATE $this->table set 
                $this->sqlEstado = ?,
                $this->actualizador = ?,
                $this->factualizacion = now()
                where $this->identificador = ?";
                $result = DB::update($sql,array($status,$user,$id));
        }
        return $result;
    }

    // insercion
    public function insertData($form,$user){
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
        $sql = "INSERT INTO $this->table ($sqlInsert) values($sqlBind) returning persona_id";
        $result = DB::select($sql,$sqlValues);
        return $result;
    }

    // eliminacion desactivacion
    public function inactive($id){
        $sql    = "UPDATE $this->table set $this->sqlEstado = 0 where $this->identificador = ?";
        $result = DB::update($sql,array($id));
        return $result;
    }

    public function deletePersona($id){
        $sql = "DELETE from $this->table where $this->identificador = ?";
        $result = DB::delete($sql,array($id));
        return $result;
    }
}
