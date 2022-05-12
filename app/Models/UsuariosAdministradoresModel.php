<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UsuariosAdministradoresModel extends Model{
    public $table           = "usuarios_administrador";
    public $identificador   = "usuarioadmin_id";
    public $creador         = "usuarioadmin_creadopor";
    public $actualizador    = "usuarioadmin_actualizadopor";
    public $factualizacion  = "usuarioadmin_factualizacion";
    public $sqlEstado       = "usuarioadmin_estado";

    public function get($user = null,$id = null){
        if($id == null){
            $sql = "SELECT --superusuario 
                        us.usuario_id,
                        us.usuario_username,
                        pe.persona_ocupacion,
                        coalesce(pe.persona_nombre1 || ' ' || pe.persona_apellido1, pe.persona_nombre1) as persona_nombrecompleto,
                        per.perfil_nombre,
                        co.complejo_nombre
                    from 
                        usuarios_administrador ua
                    inner join complejos co
                        on co.complejo_id = ua.complejo_id
                        and co.complejo_estado = 1
                    inner join usuarios us
                        on us.usuario_id = ua.usuario_id
                    inner join personas pe
                        on pe.persona_id = us.persona_id
                    inner join perfiles_usuarios pu
                        on pu.usuario_id = us.usuario_id 
                        and pu.perfilusuario_estado = 1
                    inner join perfiles per
                        on per.perfil_id = pu.perfil_id
                        and per.perfil_estado = 1
                        -- and per.perfil_id > ( SELECT perfil_id FROM perfiles_usuarios pu WHERE pu.usuario_id = ?)
                    where
                        1 = ( SELECT perfil_id FROM perfiles_usuarios pu WHERE pu.usuario_id = ?)
                    UNION
                    SELECT -- empresa de administracion
                        us.usuario_id,
                        us.usuario_username,
                        pe.persona_ocupacion,
                        coalesce(pe.persona_nombre1 || ' ' || pe.persona_apellido1, pe.persona_nombre1) as persona_nombrecompleto,
                        per.perfil_nombre,
                        co.complejo_nombre
                    from
                        usuarios us
                    inner join usuarios_administrador ua
                        on ua.usuario_id = us.usuario_id
                    inner join complejos co
                        on ua.complejo_id = co.complejo_id
                        and co.complejo_estado = 1
                    inner join empresas emp
                        on emp.empresa_id = co.empresa_id
                        and emp.empresa_estado = 1
                    inner join (
                            select
                                ue.*
                            from 
                                usuarios_empresas ue
                            where 
                                ue.usuario_id = ?) as em
                        on em.empresa_id = emp.empresa_id
                    inner join personas pe
                        on pe.persona_id = us.persona_id
                    inner join perfiles_usuarios pu
                        on pu.usuario_id = us.usuario_id 
                        and pu.perfilusuario_estado = 1
                    inner join perfiles per
                        on per.perfil_id = pu.perfil_id
                        and per.perfil_estado = 1
                    where
                        2 = ( SELECT perfil_id FROM perfiles_usuarios pu WHERE pu.usuario_id = ?)
                    group by us.usuario_id, us.usuario_username, persona_nombrecompleto, per.perfil_nombre,pe.persona_ocupacion,co.complejo_nombre
                    UNION 
                    SELECT -- ADMINISTRADORES
                        us.usuario_id,
                        us.usuario_username,
                        pe.persona_ocupacion,
                        coalesce(pe.persona_nombre1 || ' ' || pe.persona_apellido1, pe.persona_nombre1) as persona_nombrecompleto,
                        per.perfil_nombre,
                        co.complejo_nombre
                    from
                        usuarios us
                    inner join usuarios_administrador ua
                        on ua.usuario_id = us.usuario_id
                    inner join complejos co
                        on ua.complejo_id = co.complejo_id
                        and co.complejo_estado = 1
                    inner join (
                            select
                                complejo_id
                            from 
                                usuarios_administrador ua
                            where 
                                ua.usuario_id = ?) as co1
                        on co1.complejo_id = co.complejo_id
                    inner join personas pe
                        on pe.persona_id = us.persona_id
                    inner join perfiles_usuarios pu
                        on pu.usuario_id = us.usuario_id 
                        and pu.perfilusuario_estado = 1
                    inner join perfiles per
                        on per.perfil_id = pu.perfil_id
                        and per.perfil_estado = 1
                    WHERE
                        3 = ( SELECT perfil_id FROM perfiles_usuarios pu WHERE pu.usuario_id = ?)
                    group by us.usuario_id, us.usuario_username, persona_nombrecompleto, per.perfil_nombre, pe.persona_ocupacion, co.complejo_nombre
                ";
            $result = DB::select($sql,array($user,$user,$user,$user,$user));
        }else{
            $sql = "SELECT
                        ua.*,
                        coalesce(pe.persona_nombre1 || ' ' || pe.persona_apellido1, pe.persona_nombre1) as nombre_completo,
                        us.usuario_username,
                        pe.persona_ocupacion,
                        co.complejo_nombre,
                        (select pe.persona_nombre1 || ' ' || pe.persona_apellido1 from personas pe inner join usuarios us on us.persona_id = pe.persona_id and us.usuario_id = ua.usuarioadmin_creadopor) as creadopor
                    from 
                        usuarios_administrador ua
                        inner join usuarios us
                            on us.usuario_id = ua.usuario_id
                            and us.usuario_estado = 1
                        inner join personas pe
                            on pe.persona_id = us.persona_id
                        inner join complejos co
                            on co.complejo_id = ua.complejo_id
                            and co.complejo_estado = 1
                    where
                        usuarioadmin_estado = 1
                        and ua.usuarioadmin_id = ?
                    order by ua.usuarioadmin_id";
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
                                co.complejo_id as codigo,
                                co.complejo_nombre as nombre
                            from
                                complejos co
                            where 
                                co.complejo_estado = 1 
                            order by co.complejo_nombre
                        ) as tmp) as complejos";
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
