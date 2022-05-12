<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RegisterModel extends Model{
    /**
     * @description funcion usada para registro de usuarios.
     */
    public function getComplejos(){
        $sql = "SELECT 
                    complejo_id as codigo,
                    complejo_nombre as nombre
                from
                    complejos
                where complejo_estado = 1
                order by 2";
        $result = DB::select($sql);
        return $result;
    }
    public function getEdificios($complejo_id){
        $sql = "SELECT
                (select 
                        json_object_agg(tmp.codigo,tmp.nombre) 
                    from (SELECT 
                            ed.edificio_id as codigo,
                            ed.edificio_nombre as nombre
                            from
                                edificios ed
                            inner join complejos co
                                on co.complejo_id = ed.complejo_id
                                and co.complejo_estado = 1
                                and co.complejo_id = ?
                            where ed.edificio_estado = 1
                    ) as tmp
                ) as edificios,
                (select
                        json_object_agg(tmp.codigo, tmp.nombre)
                    from (SELECT 
                            propiedad_id as codigo,
                            propiedad_nombre as nombre
                        from
                            propiedades pr
                        inner join complejos co
                            on co.complejo_id = pr.complejo_id
                            and co.complejo_estado = 1
                            and co.complejo_id = ?
                        where 
                            pr.propiedad_estado = 1
                            and pr.edificio_id is null
                        order by 2
                    ) as tmp
                ) as propiedades";
        $result = DB::select($sql,array($complejo_id,$complejo_id));
        return $result;
    }
    public function getPropiedades($complejo_id,$edificio_id){
        $sql = "SELECT 
                    propiedad_id as codigo,
					propiedad_nombre as nombre
                from
                    propiedades pr
                inner join complejos co
                    on co.complejo_id = pr.complejo_id
                    and co.complejo_estado = 1
                    and co.complejo_id = ?
				inner join edificios ed
					on ed.complejo_id = co.complejo_id
                    and ed.edificio_id = pr.edificio_id
					and ed.edificio_id = ?
					and ed.edificio_estado = 1
                where pr.propiedad_estado = 1
                order by 2";
        $result = DB::select($sql,array($complejo_id,$edificio_id));
        return $result;
    }

    public function register(){
        $sql = "SELECT 
                    us.usuario_id,
                    us.usuario_username,
                    us.usuario_password,
                    pe.persona_nombre1 || ' ' || pe.persona_apellido1 as nombre
                FROM 
                    usuarios us
                inner join personas pe
                    on us.persona_id = pe.persona_id
                where 
                    us.usuario_username = ?
                    and us.usuario_estado = 1";
        $result = DB::select($sql,array($user));
        return $result;
    }

    public function insertRegister($form){
        $options = ['cost'=> 12];
        $pass = password_hash($form->usuario_password,PASSWORD_DEFAULT,$options);
        $form->usuario_password = $pass;

        foreach($form as $key=>$value){
            if($value !== ''){
                $sqlInsert[]    = $key;
                $sqlBind[]      = '?';
                $sqlValues[]    = $value;
            }
        }
        $sqlInsert  = implode(',',$sqlInsert);
        $sqlBind    = implode(',',$sqlBind);
        $sql        = "INSERT INTO aprobar_usuarios ($sqlInsert) values($sqlBind)";
        $result     = DB::select($sql,$sqlValues);
        return $result;
    }

    public function checkEmail($username){
        $sql = "SELECT usuario_username from usuarios where usuario_username = ?";
        $result = DB::select($sql,array($username));
        return $result;
    }

}
