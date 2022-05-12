<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\PersonasModel;
use App\Models\PropiedadesModel;
use App\Models\PerfilesUsuariosModel;

class UsuariosModel extends Model{
    public $table           = "usuarios";
    public $identificador   = "usuario_id";
    public $creador         = "usuario_creadopor";
    public $actualizador    = "usuario_actualizadopor";
    public $factualizacion  = "usuario_factualizacion";
    public $sqlEstado       = "usuario_estado";

    public function get($user,$id = null){
        if($id == null){
            $sql = "SELECT -- superusuario
                    us.usuario_id,
                    us.usuario_username,
                    coalesce(pe.persona_nombre1 ||' '||pe.persona_apellido1||' '||pe.persona_apellido2, pe.persona_nombre1 ||' '|| pe.persona_apellido1) as persona_nombrecompleto,
                    pf.perfil_nombre
                from 
                    usuarios us
                inner join personas pe
                    on pe.persona_id = us.persona_id
                    and pe.persona_estado = 1
                inner join perfiles_usuarios pu
                    on pu.usuario_id = us.usuario_id
                    and pu.perfilusuario_estado = 1
                inner join perfiles pf
                    on pf.perfil_id = pu.perfil_id
                    and pf.perfil_estado = 1
                where
                    1 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                UNION
                -- EMPRESAS
                select
                    us.usuario_id,
                    us.usuario_username,
                    coalesce(pe.persona_nombre1 ||' '||pe.persona_apellido1||' '||pe.persona_apellido2, pe.persona_nombre1 ||' '|| pe.persona_apellido1) as persona_nombrecompleto,
                    pf.perfil_nombre
                from 
                    usuarios us
                inner join personas pe
                    on pe.persona_id = us.persona_id
                    and pe.persona_estado = 1
                inner join perfiles_usuarios pu
                    on pu.usuario_id = us.usuario_id
                    and pu.perfilusuario_estado = 1
                inner join perfiles pf
                    on pf.perfil_id = pu.perfil_id
                    and pf.perfil_estado = 1
                where
                    2 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                order by usuario_id";
            $result = DB::select($sql,array($user,$user));
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
                        pe.*,
                        per.perfil_id,
                        per.perfil_nombre
                    from 
                        usuarios us
                    inner join personas pe
                        on pe.persona_id = us.persona_id
                    inner join perfiles_usuarios pu
                        on pu.usuario_id = us.usuario_id
                        and pu.perfilusuario_estado = 1
                    inner join perfiles per
                        on per.perfil_id = pu.perfil_id
                        and per.perfil_estado = 1
                    where us.usuario_id = ?
                    order by us.usuario_fcreacion,persona_nombrecompleto";
            $result = DB::select($sql,array($id));
        }
        return $result;
    }

    public function getParamsUpdate($user){
        $sql = "SELECT 
                    (select 
                    coalesce(json_object_agg(tmp.codigo,tmp.nombre),'{}')::text
                    from(
                        select 
                                pe.perfil_nombre as nombre,
                                pe.perfil_id as codigo
                        from 
                                perfiles pe
                        where 
                            pe.perfil_estado = 1
                            and 1 = (select pu.perfil_id from perfiles_usuarios pu where pu.usuario_id = ?)
                        UNION
                        select 
                                pe.perfil_nombre as nombre,
                                pe.perfil_id as codigo
                        from 
                                perfiles pe
                        where 
                            pe.perfil_estado = 1
                            and pe.perfil_id > 2 
                            and 2 = (select pu.perfil_id from perfiles_usuarios pu where pu.usuario_id = ?)

                        UNION
                        select 
                                pe.perfil_nombre as nombre,
                                pe.perfil_id as codigo
                        from 
                                perfiles pe
                        where 
                            pe.perfil_estado = 1
                            and pe.perfil_id > 3
                            and 3 = (select pu.perfil_id from perfiles_usuarios pu where pu.usuario_id = ?)
                        order by codigo
                        ) as tmp) as perfiles,
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
        $result = DB::select($sql,array($user,$user,$user));
        return $result;
    }

    public function getUsuariosToApprove($user,$codigos=null){
        if($codigos != null){
            $codigos = implode(',',$codigos);
            $sql = "SELECT -- superusuario
                        au.*
                    from 
                        aprobar_usuarios au
                    where aprobarusuario_id in ($codigos)
                    ";
            $result = DB::select($sql);
        }else{
            $sql = "SELECT -- superusuario
                        au.aprobarusuario_id,
                        coalesce(au.persona_nombre1 ||' '||au.persona_apellido1||' '||au.persona_apellido2, au.persona_nombre1 ||' '|| au.persona_apellido1) as persona_nombrecompleto,
                        au.usuario_username,
                        pr.propiedad_nombre,
                        co.complejo_nombre,
                        au.aprobarusuario_fcreacion
                    from 
                        aprobar_usuarios au
                    inner join propiedades pr
                        on pr.propiedad_id = au.propiedad_id
                    inner join complejos co
                        on co.complejo_id = pr.complejo_id
                    where
                        1 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                        and au.aprobarusuario_estado = 1
                    UNION
                    -- EMPRESAS
                    select
                        au.aprobarusuario_id,
                        coalesce(au.persona_nombre1 ||' '||au.persona_apellido1||' '||au.persona_apellido2, au.persona_nombre1 ||' '|| au.persona_apellido1) as persona_nombrecompleto,
                        au.usuario_username,
                        pr.propiedad_nombre,
                        co.complejo_nombre,
                        au.aprobarusuario_fcreacion
                    from 
                        aprobar_usuarios au
                    inner join propiedades pr
                        on pr.propiedad_id = au.propiedad_id
                    inner join complejos co
                        on co.complejo_id = pr.complejo_id
                    inner join empresas em
                        on em.empresa_id = co.empresa_id
                        and em.empresa_estado = 1
                    inner join usuarios_empresas ue
                        on ue.empresa_id = em.empresa_id
                        and ue.usuario_id = ?
                    where
                        2 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                        and au.aprobarusuario_estado = 1
                    UNION
                    -- ADMINISTRADORES
                    select
                        au.aprobarusuario_id,
                        coalesce(au.persona_nombre1 ||' '||au.persona_apellido1||' '||au.persona_apellido2, au.persona_nombre1 ||' '|| au.persona_apellido1) as persona_nombrecompleto,
                        au.usuario_username,
                        pr.propiedad_nombre,
                        co.complejo_nombre,
                        au.aprobarusuario_fcreacion
                    from 
                        aprobar_usuarios au
                    inner join propiedades pr
                        on pr.propiedad_id = au.propiedad_id
                    inner join complejos co
                        on co.complejo_id = pr.complejo_id
                    inner join usuarios_administrador ua
                        on ua.complejo_id = co.complejo_id
                        and ua.usuarioadmin_estado = 1
                        and ua.usuario_id = ?
                    where
                        3 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                        and au.aprobarusuario_estado = 1
            ";
            $result = DB::select($sql,array($user,$user,$user,$user,$user));
        }
        return $result;
    }

    public function createUsersFromUserToApprove($users,$user){
        try{
            $personas         = new PersonasModel();
            $perfilesUsuarios = new PerfilesUsuariosModel();
            $propiedades      = new PropiedadesModel();

            DB::beginTransaction();
            $codigos = array();
            foreach($users as $u){
                $codigos[] = $u->aprobarusuario_id;
                // personas
                $formPersona = new \stdClass();
                $formPersona->persona_nombre1   = $u->persona_nombre1;
                $formPersona->persona_nombre2   = $u->persona_nombre2;
                $formPersona->persona_apellido1 = $u->persona_apellido1;
                $formPersona->persona_apellido2 = $u->persona_apellido2;
                $formPersona->persona_telefono  = $u->persona_telefono;
                $formPersona->persona_email     = $u->usuario_username;

                // usuario
                $formUsuario = new \stdClass();
                $formUsuario->usuario_username  = $u->usuario_username;
                $formUsuario->usuario_password  = $u->usuario_password;
                $formUsuario->usuario_estado    = 1;

                // usuario perfil
                $formUsuarioPerfil = new \stdClass();
                $formUsuarioPerfil->perfil_id = 5;
                
                $propiedad_id = $u->propiedad_id;

                $persona_id = $personas->insertData($formPersona,1);            // return persona_id
                $persona_id = $persona_id[0]->persona_id;

                $usuario_id = $this->insertDataRegister($formUsuario,1,$persona_id);    //return codigo
                $usuario_id = $usuario_id[0]->usuario_id;

                $perfil     = $perfilesUsuarios->insertData($formUsuarioPerfil,1,$usuario_id);

                // update propiedad (asigno usuario a propiedad)
                $formPropiedad = new \stdClass();
                $formPropiedad->usuario_id = $usuario_id;
                $result     = $propiedades->updateData($formPropiedad,$propiedad_id,1);

            }
            // eliminar usuarios creados
            if(sizeof($codigos) > 0){
                $codigos = implode(',',$codigos);
                $sql = "UPDATE aprobar_usuarios set aprobarusuario_estado = 0, aprobarusuario_aprobadopor = ? where aprobarusuario_id in ($codigos)";
                $result = DB::update($sql,array($user));
            }
            DB::commit();
            $objData = array(
                'success'=> true,
                'message'=> 'Usuarios aprobados exitosamente'
            );
            return $objData;
        }catch(Exception $e){
            DB::rollback();
            if(isset($perfil)){
                $result = $perfilesUsuarios->deletePerfil($usuario_id);
                $result = $this->deleteUsuario($usuario_id);
                $result = $personas->deletePersona($persona_id);
                $objData = array(
                    'success'=> false,
                    'message'=> 'Se produjo un error al intentar asociar usuario y propiedad'
                );
                return $objData;
            }
            if(isset($usuario_id)){
                $result = $this->deleteUsuario($usuario_id);
                $result = $personas->deletePersona($persona_id);
                $objData = array(
                    'success'=> false,
                    'message'=> 'Se produjo un error al intentar asignar perfil'
                );
                return $objData;
            }
            if(isset($persona_id)){
                $result = $personas->deletePersona($persona_id);
                $objData = array(
                    'success'=> false,
                    'message'=> 'Se produjo un error al intentar agregar usuario'
                );
                return $objData;
            }
            if(!isset($persona_id)){
                $objData = array(
                    'success'=> false,
                    'message'=> 'El correo electrónico ya se encuentra registrado'
                );
                return $objData;
            }else{
                $objData = array(
                    'success'=> false,
                    'message'=> 'Se produjo un error en el registro.'
                );
                return $objData;
            }
        }
    }

    // desactiva los
    public function inactivateUsersToApprove($users,$user){
        try{
            DB::beginTransaction();
            $codigos = array();
            foreach($users as $u){
                $codigos[] = $u->aprobarusuario_id;
            }
            if(sizeof($codigos) > 0){
                $codigos = implode(',',$codigos);
                $sql = "UPDATE aprobar_usuarios set aprobarusuario_estado = 0, aprobarusuario_descartadopor = ? where aprobarusuario_id in ($codigos)";
                $result = DB::update($sql,array($user));
            }
            DB::commit();
            $objData = array(
                'success'=> true,
                'message'=> 'Registros descartados exitosamente.'
            );
            return $objData;
        }catch(Exception $e){
            DB::rollback();
            $objData = array(
                'success'=> false,
                'message'=> 'Se produjo un error al intentar eliminar los registros.'
            );
            return $objData;
        }
        
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
        $sql .= $sqlSets . " where $this->identificador = ? returning persona_id";

        // id actualizacion
        $sqlValues[] = $id; 
        $result = DB::select($sql,$sqlValues);
        return $result;
    }

    // actualizacion status
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
    public function insertData($form,$user,$persona_id = null){
        if($persona_id!=null){
            $form->persona_id = $persona_id;
            $options = ['cost'=> 12];
            $pass = password_hash('pagoeasy123',PASSWORD_DEFAULT,$options);
            $form->usuario_password = $pass;
            $form->{$this->sqlEstado} = 1;

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
            $sql = "INSERT INTO $this->table ($sqlInsert) values($sqlBind) returning usuario_id";
            $result = DB::select($sql,$sqlValues);
            return $result;
        }
    }

    public function insertDataRegister($form,$user,$persona_id = null){
        if($persona_id!=null){
            $form->persona_id = $persona_id;
            $options = ['cost'=> 12];            
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
            $sql = "INSERT INTO $this->table ($sqlInsert) values($sqlBind) returning usuario_id";
            $result = DB::select($sql,$sqlValues);
            return $result;
        }
    }

    // eliminacion desactivacion
    public function inactive($id){
        $sql    = "UPDATE $this->table set $this->sqlEstado = 0 where $this->identificador = ?";
        $result = DB::select($sql,array($id));
        return $result;
    }


    public function deletePersona($persona_id){
        $sql = "DELETE from personas where persona_id = ?";
        $result = DB::delete($sql,array($persona_id));
        return $result;
    }
    public function deleteUsuario($usuario_id){
        $sql = "DELETE from usuarios where usuario_id = ?";
        $result = DB::delete($sql,array($usuario_id));
        return $result;
    }
    public function deletePerfilUsuario($usuario_id){
        $sql = "DELETE from perfiles_usuarios where usuario_id = ?";
        $result = DB::delete($sql,array($usuario_id));
        return $result;
    }
    
    
}
