<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ComplejosModel extends Model{
    
    public function get($user,$id = null){
        if($id == null){
            $sql = "SELECT
                        co.complejo_id,
                        co.complejo_nombre,
                        co.complejo_tipo,
                        co.empresa_id,
                        em.empresa_nombrecorto,
                        co.pais_codigo,
                        co.estado_codigo,
                        co.complejo_estado
                    FROM
                        complejos co
                        inner join empresas em
                            on em.empresa_id = co.empresa_id
                            and em.empresa_estado = 1
                    where
                        1 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                        and co.complejo_id > 1
                    UNION
                    -- EMPRESAS
                    SELECT
                        co.complejo_id,
                        co.complejo_nombre,
                        co.complejo_tipo,
                        co.empresa_id,
                        em.empresa_nombrecorto,
                        co.pais_codigo,
                        co.estado_codigo,
                        co.complejo_estado
                    FROM
                        complejos co
                        inner join empresas em
                            on em.empresa_id = co.empresa_id
                            and em.empresa_estado = 1
                        inner join usuarios_empresas ue
                            on ue.empresa_id = em.empresa_id
                            and ue.usuario_id = ?
                    where
                        2 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                        and co.complejo_id > 1
                    UNION
                    -- ADMINISTRADORES
                    SELECT
                        co.complejo_id,
                        co.complejo_nombre,
                        co.complejo_tipo,
                        co.empresa_id,
                        em.empresa_nombrecorto,
                        co.pais_codigo,
                        co.estado_codigo,
                        co.complejo_estado
                    FROM
                        complejos co
                        inner join empresas em
                            on em.empresa_id = co.empresa_id
                            and em.empresa_estado = 1
                        inner join usuarios_administrador ua
                            on ua.complejo_id = co.complejo_id
                            and ua.usuarioadmin_estado = 1
                            and ua.usuario_id = ?
                    where
                        3 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                        and co.complejo_id > 1
                    UNION
                    -- JUNTA
                    SELECT
                        co.complejo_id,
                        co.complejo_nombre,
                        co.complejo_tipo,
                        co.empresa_id,
                        em.empresa_nombrecorto,
                        co.pais_codigo,
                        co.estado_codigo,
                        co.complejo_estado
                    FROM
                        complejos co
                        inner join empresas em
                            on em.empresa_id = co.empresa_id
                            and em.empresa_estado = 1
                        inner join juntas ju
                            on ju.complejo_id = co.complejo_id
                            and ju.junta_estado = 1
                        inner join usuarios_junta uj
                            on uj.junta_id = ju.junta_id
                            and uj.usuariojunta_estado = 1
                            and uj.usuario_id = ?
                    where
                        4 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                        and co.complejo_id > 1
                    ";
            $result = DB::select($sql,array($user,$user,$user,$user,$user,$user,$user));
        }else{
            $sql = "SELECT 
                        complejo_nombre as nombre,
                        co.complejo_tipo,
                        em.empresa_id,
                        em.empresa_nombrecorto,
                        pa.pais_nombre as pais,
                        pa.pais_codigo,
                        es.estado_nombre as estado,
                        es.estado_codigo,
                        ci.ciudad_nombre as ciudad,
                        ci.ciudad_codigo,
                        co.complejo_estado as status
                    from 
                        complejos co 
                        inner join empresas em
                            on em.empresa_id = co.empresa_id
                            and em.empresa_estado = 1
                        inner join paises pa
                            on pa.pais_codigo = co.pais_codigo
                            and pa.pais_estado = 1
                        inner join estados es
                            on es.estado_codigo = co.estado_codigo
                            and es.estado_estado = 1
                        inner join ciudades ci
                            on ci.ciudad_codigo = co.ciudad_codigo
                            and ci.ciudad_estado = 1
                    where co.complejo_id = ?";
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
                        pa.pais_codigo as codigo,
                        pa.pais_nombre as nombre
                    from 
                        paises pa
                    where pa.pais_estado = 1 order by pa.pais_nombre
                ) as tmp) as paises,
                (select 
                coalesce(json_object_agg(tmp.codigo,tmp.nombre),'{}')::text
                from(
                    select 
                        em.empresa_id as codigo,
                        em.empresa_nombrecorto as nombre
                    from 
                        empresas em
                    where em.empresa_estado = 1 
                    and em.empresa_tiporelacion = 'COMPANIA_ADMINISTRACION'
                    order by em.empresa_nombrecorto
                ) as tmp) as empresas,
                (select 
                coalesce(json_object_agg(tmp.codigo,tmp.nombre),'{}')::text
                from(
                    select 
                        pc.parametro_valor as codigo,
                        pc.parametro_nombre as nombre
                    from 
                        parametros_configuracion pc
                    where pc.parametro_estado = 1 
                        and pc.parametro_tipo = 'TIPO_COMPLEJO'
                    order by pc.parametro_orden
                ) as tmp) as tipo_complejo
                ";
        $result = DB::select($sql);
        return $result;
    }





    // actualizacion
    public function updateData($form,$id,$user){
        $sql ="UPDATE complejos set 
                complejo_nombre = '$form->nombre', 
                complejo_tipo = '$form->complejo_tipo',
                empresa_id = $form->empresa, 
                pais_codigo = '$form->pais', 
                estado_codigo = '$form->estado',
                ciudad_codigo = $form->ciudad,
                complejo_estado = $form->status,
                complejo_actualizadopor = $user,
                complejo_factualizacion = now()
                where complejo_id = $id";  
        $result = DB::update($sql);
        return $result;
    }
    // actualizacion estatus
    public function updateStatus($status,$id,$codigos = null,$user){
        if($id == 'null'){
            $codigos = implode(',',$codigos);
            $sql ="UPDATE complejos set 
                complejo_estado = ?,
                complejo_actualizadopor = ?,
                complejo_factualizacion = now()
                where complejo_id in ($codigos)";
            $result = DB::update($sql,array($status,$user));
        }else{
            $sql ="UPDATE complejos set 
                complejo_estado = ?,
                complejo_actualizadopor = ?,
                complejo_factualizacion = now()
                where complejo_id = ?";
                $result = DB::update($sql,array($status,$user,$id));
        }
        return $result;
    }

    // insercion
    public function insertData($form,$user){
        $sql = "INSERT INTO complejos
                    (complejo_nombre,complejo_tipo, empresa_id, pais_codigo, estado_codigo,ciudad_codigo,complejo_estado,complejo_creadopor)
                VALUES
                    ('$form->nombre','$form->complejo_tipo',$form->empresa,'$form->pais','$form->estado',$form->ciudad,$form->status,$user);";
        $result = DB::insert($sql);
        return $result;
    }

    // eliminacion desactivacion
    public function inactive($id){
        $sql    = "UPDATE complejos set complejo_estado = 0 where complejo_id = ?";
        $result = DB::update($sql,array($id));
        return $result;
    }


}
