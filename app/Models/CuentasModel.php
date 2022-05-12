<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CuentasModel extends Model{
    
    public function getInfo($user){
        $sql = "SELECT
                    pe.* 
                from usuarios us
                    inner join personas pe
                    on pe.persona_id = us.persona_id
                where us.usuario_id = ?";
        $result = DB::select($sql,array($user));
        return $result;
    }
    public function getCuenta($user){
        $sql = "SELECT
                    us.usuario_username,
                    us.usuario_estado,
                    us.usuario_fcreacion
                from usuarios us
                where us.usuario_id = ?";
        $result = DB::select($sql,array($user));
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

    public function updateData($form,$id,$user){
        // usuario actualizacion
        $form->persona_actualizadopor = $user;
        // fecha actualizacion
        $form->persona_factualizacion = 'now()';
        $sql = "UPDATE personas set ";
        $sqlSets = [];
        $sqlValues = [];
        foreach($form as $key => $value){
            $sqlSets[] = " $key = ? ";
            $sqlValues[] = $value;
        }
        $sqlSets = implode(',',$sqlSets);
        $sql .= $sqlSets . " where persona_id = ?";
        // id actualizacion
        $sqlValues[] = $id; 
        $result = DB::update($sql,$sqlValues);
        return $result;
    }


    public function updatePassword($pass,$user){
        $sql ="UPDATE
                    usuarios
                set 
                    usuario_recuperar = 0,
                    usuario_recuperartoken = null,
                    usuario_recuperarfecha = null,
                    usuario_password = ?,
                    usuario_actualizadopor = ?,
                    usuario_factualizacion = now()
                where
                    usuario_id = ?
                returning usuario_id";
        $result = DB::select($sql,array($pass,$user,$user));
        return $result;
    }
}