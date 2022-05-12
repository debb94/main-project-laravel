<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UsuariosEmpresasModel extends Model{
    public $table           = "usuarios_empresas";
    public $identificador   = "usuarioempresa_id";
    public $creador         = "usuarioempresa_creadopor";
    public $actualizador    = "usuarioempresa_actualizadopor";
    public $factualizacion  = "usuarioempresa_factualizacion";
    public $sqlEstado       = "usuarioempresa_estado";

    public function get($id = null){
        if($id == null){
            $sql = "SELECT
                        ue.*,
                        coalesce(pe.persona_nombre1 || ' ' || pe.persona_apellido1, pe.persona_nombre1) as nombre_completo,
                        us.usuario_username,
                        pe.persona_ocupacion,
                        em.empresa_nombrecorto
                    from 
                        usuarios_empresas ue
                        inner join usuarios us
                            on us.usuario_id = ue.usuario_id
                            and us.usuario_estado = 1
                        inner join personas pe
                            on pe.persona_id = us.persona_id
                        inner join empresas em
                            on em.empresa_id = ue.empresa_id
                            and em.empresa_estado = 1
                    order by ue.usuarioempresa_id";
            $result = DB::select($sql);
        }else{
            $sql = "SELECT
                        ue.*,
                        coalesce(pe.persona_nombre1 || ' ' || pe.persona_apellido1, pe.persona_nombre1) as nombre_completo,
                        us.usuario_username,
                        pe.persona_ocupacion,
                        em.empresa_nombrecorto,
                        (select pe.persona_nombre1 || ' ' || pe.persona_apellido1 from personas pe inner join usuarios us on us.persona_id = pe.persona_id and us.usuario_id = ue.usuarioempresa_creadopor) as creadopor
                    from 
                        usuarios_empresas ue
                        inner join usuarios us
                            on us.usuario_id = ue.usuario_id
                            and us.usuario_estado = 1
                        inner join personas pe
                            on pe.persona_id = us.persona_id
                        inner join empresas em
                            on em.empresa_id = ue.empresa_id
                            and em.empresa_estado = 1
                    where ue.usuarioempresa_id = ?";
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
                            us.usuario_id as codigo,
                            us.usuario_username as nombre
                        from 
                            usuarios us
                        where us.usuario_estado = 1 
                        order by us.usuario_username
                        ) as tmp) as usuarios,
                    (select 
                        coalesce(json_object_agg(tmp.codigo,tmp.nombre),'{}')::text
                        from (select 
                                em.empresa_id as codigo,
                                em.empresa_nombrecorto as nombre
                            from
                                empresas em
                            where 
                                em.empresa_estado = 1 
                            order by em.empresa_nombrecorto
                        ) as tmp) as empresas";
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
        $sql = "INSERT INTO $this->table ($sqlInsert) values($sqlBind)";
        $result = DB::insert($sql,$sqlValues);
        return $result;
    }

    // eliminacion desactivacion
    public function inactive($id){
        $sql    = "UPDATE $this->table set $this->sqlEstado = 0 where $this->identificador = ?";
        $result = DB::update($sql,array($id));
        return $result;
    }
}
