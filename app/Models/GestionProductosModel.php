<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GestionProductosModel extends Model{
    public $table           = "productos";
    public $identificador   = "producto_id";
    public $creador         = "producto_creadopor";
    public $actualizador    = "producto_actualizadopor";
    public $factualizacion  = "producto_factualizacion";
    public $sqlEstado       = "producto_estado";

    public function get($user,$id = null){
        if($id == null){
            $sql = "SELECT 
                        pr.producto_id,
                        pr.producto_nombre,
                        trunc( ((pr.producto_valor * pr.producto_impuesto/100 ) + pr.producto_valor),2) producto_valor,
                        pr.producto_estado,
                        co.complejo_nombre
                    from 
                        $this->table pr
                        inner join complejos co
                            on co.complejo_id = pr.complejo_id
                    WHERE
                        1 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                    UNION
                    SELECT 
                        pr.producto_id,
                        pr.producto_nombre,
                        trunc( ((pr.producto_valor * pr.producto_impuesto/100 ) + pr.producto_valor),2) producto_valor,
                        pr.producto_estado,
                        co.complejo_nombre
                    from 
                        $this->table pr
                        inner join complejos co
                            on co.complejo_id = pr.complejo_id
                        inner join empresas em
                            on em.empresa_id = co.empresa_id
                        inner join usuarios_empresas ue
                            on ue.empresa_id = em.empresa_id
                            and ue.usuario_id = ?
                    WHERE
                        2 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                    UNION
                    SELECT 
                        pr.producto_id,
                        pr.producto_nombre,
                        trunc( ((pr.producto_valor * pr.producto_impuesto/100 ) + pr.producto_valor),2) producto_valor,
                        pr.producto_estado,
                        co.complejo_nombre
                    from 
                        $this->table pr
                        inner join complejos co
                            on co.complejo_id = pr.complejo_id
                            and co.complejo_estado = 1
                        inner join usuarios_administrador ua
                            on ua.complejo_id = co.complejo_id
                            and ua.usuario_id = ?
                    WHERE
                        3 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                    UNION -- junta
                    SELECT 
                        pr.producto_id,
                        pr.producto_nombre,
                        trunc( ((pr.producto_valor * pr.producto_impuesto/100 ) + pr.producto_valor),2) producto_valor,
                        pr.producto_estado,
                        co.complejo_nombre
                    from 
                        $this->table pr
                        inner join complejos co
                            on co.complejo_id = pr.complejo_id
                            and co.complejo_estado = 1
                        inner join juntas ju
                            on ju.complejo_id = co.complejo_id
                        inner join usuarios_junta uj
                            on uj.junta_id = ju.junta_id
                            and uj.usuario_id = ?
                    WHERE
                        4 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                    UNION
                    SELECT -- usuarios
                        pr.producto_id,
                        pr.producto_nombre,
                        trunc( ((pr.producto_valor * pr.producto_impuesto/100 ) + pr.producto_valor),2) producto_valor,
                        pr.producto_estado,
                        co.complejo_nombre
                    from 
                        $this->table pr
                        inner join complejos co
                            on co.complejo_id = pr.complejo_id
                            and co.complejo_estado = 1
                        inner join propiedades pro
                            on pro.complejo_id = co.complejo_id
                        left join usuarios us
                            on us.usuario_id = pro.usuario_id
                            and us.usuario_estado = 1
                            and us.usuario_id = ?
                    WHERE
                        5 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                    order by producto_id";
            $result = DB::select($sql,array($user,$user,$user,$user,$user,$user,$user,$user,$user));
        }else{
            $sql = "SELECT 
                        pr.*,
                        co.complejo_id,
                        co.complejo_nombre,
                        coalesce(pe.persona_nombre1||' '||pe.persona_apellido1|| ' '||pe.persona_apellido2,pe.persona_nombre1||' '||pe.persona_apellido1) as persona_creacion
                    from 
                        $this->table pr
                        inner join complejos co
                            on co.complejo_id = pr.complejo_id
                        left join usuarios us
                            on us.usuario_id = pr.producto_creadopor
                        inner join personas pe
                            on pe.persona_id = us.persona_id
                        where $this->identificador = ?";
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
                            co.complejo_id as codigo,
                            co.complejo_nombre as nombre
                        from 
                            complejos co
                        where co.complejo_estado = 1 
                        order by co.complejo_nombre
                        ) as tmp) as complejos
                    ";
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
            if($value !== ''){
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
