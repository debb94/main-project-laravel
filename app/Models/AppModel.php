<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Exception;

class AppModel extends Model{
    
    public function checkPermission($user,$endpoint){
        $sql = "SELECT 
                    pm.perfilmenu_permisos permisos,
                    m.menu_nombre menu,
                    m.menu_accion endpoint
                from 
                    perfiles_usuarios pu
                    inner join perfiles p
                        on p.perfil_id = pu.perfil_id
                        and p.perfil_estado = 1
                        and pu.usuario_id = ?
                    inner join perfiles_menu pm
                        on pm.perfil_id = pu.perfil_id
                        and pm.perfilmenu_estado = 1
                    inner join menus m
                        on m.menu_id = pm.menu_id
                        and m.menu_padre is not null
                        and m.menu_accion = ?";
        $result = DB::select($sql,array($user,$endpoint));
        return $result;
    }

    // paises, ciudades, estados
    public function getCountry(){
        $sql = "SELECT 
                (select 
                coalesce(json_object_agg(tmp.codigo,tmp.nombre),'{}')::text
                from(
                    select 
                        pa.pais_codigo as codigo,
                        pa.pais_nombre as nombre
                    from 
                        paises pa
                    where pa.pais_estado = 1
                ) as tmp) as paises";
        $result = DB::select($sql);
        return $result;
    }
    public function getEstate($pais){
        $sql = "SELECT 
                    es.estado_codigo as codigo,
                    es.estado_nombre as nombre
                from estados es
                where es.pais_codigo = ?
                    and es.estado_estado = 1
                order by es.estado_nombre";
        $result = DB::select($sql,array($pais));
        return $result;
    }
    public function getCity($pais,$estate){
        $sql = "SELECT 
                    ci.ciudad_codigo as codigo,
                    ci.ciudad_nombre as nombre
                from ciudades ci
                where ci.pais_codigo = ? 
                    and ci.estado_codigo = ?
                    and ci.ciudad_estado = 1
                order by ci.ciudad_nombre";
        $result = DB::select($sql,array($pais,$estate));
        return $result;
    }


    public function getEdificios($complejo){
        $sql = "SELECT 
                    ed.edificio_id as codigo,
                    ed.edificio_nombre as nombre
                from 
                    edificios ed
                where 
                    ed.complejo_id = ? 
                    and ed.edificio_estado = 1
                order by ed.edificio_nombre";
        $result = DB::select($sql,array($complejo));
        return $result;
    }

    public function getUsuarios(){
        $sql = "SELECT
                    us.usuario_id as codigo,
                    us.usuario_username as nombre
                from usuarios us
                where us.usuario_estado = 1
                order by us.usuario_username";
        $result = DB::select($sql);
        return $result;
    }

    public function getComplejos($user){
        $sql = "SELECT -- SUPER USUARIO
                    *
                FROM
                    complejos co
                where
                    1 = (select pe.perfil_id from perfiles_usuarios pe where pe.usuario_id = ?)
                UNION
                SELECT -- EMPRESA DE ADMINISTRACION
                    *
                FROM
                    complejos co
                where
                    co.empresa_id = 3 -- retorno de usuario_empresas where usuario = 10
                    and 2 = (select pe.perfil_id from perfiles_usuarios pe where pe.usuario_id = ?)";
        $result = DB::select($sql,array($user,$user));
        return $result;
    }

    public function getPropiedades($user,$complejo,$edificio=null){
        $strEdificio = "";
        if($edificio != null){
            $val = intval($edificio);
            $strEdificio = " and pr.edificio_id = $val ";
        }
        $sql = "(SELECT -- superusuario
                    pr.propiedad_id as codigo,
                    pr.propiedad_nombre as nombre
                from
                    propiedades pr
                where 
                    pr.complejo_id = ?
                    $strEdificio
                    and 1 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                order by 
                    pr.propiedad_nombre)
                UNION
                (SELECT -- empresa administracion
                    pr.propiedad_id as codigo,
                    pr.propiedad_nombre as nombre
                from
                    propiedades pr
                    inner join complejos co
                        on co.complejo_id = pr.complejo_id
                    inner join usuarios_empresas ue
                        on ue.empresa_id = co.empresa_id
                        and ue.usuario_id = ?
                where 
                    pr.complejo_id = ?
                    $strEdificio
                    and 2 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                order by 
                    pr.propiedad_nombre)
                UNION
                (SELECT -- administracion
                    pr.propiedad_id as codigo,
                    pr.propiedad_nombre as nombre
                from
                    propiedades pr
                    inner join complejos co
                        on co.complejo_id = pr.complejo_id
                    inner join usuarios_administrador ua
                        on ua.complejo_id = co.complejo_id
                        and ua.usuario_id = ?
                where 
                    pr.complejo_id = ?
                    $strEdificio
                    and 3 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                order by 
                    pr.propiedad_nombre)";
        $result = DB::select($sql,array($complejo,$user,$user,$complejo,$user,$user,$complejo,$user));
        return $result;
    }

    public function getEstacionamientosPropiedad($propiedad_id){
        $sql = "SELECT estacionamiento_id as codigo, estacionamiento_nombre as nombre from estacionamientos where propiedad_id = ?";
        $result = DB::select($sql,array($propiedad_id));
        return $result;
    }

    public function getUsuariosComplejo($user,$complejo){
        $sql = "SELECT
                    u.usuario_id as codigo,
                    COALESCE(pe.persona_nombre1 || ' ' ||pe.persona_apellido1,pe.persona_nombre1) as nombre
                FROM
                    usuarios u
                    inner join personas pe
                        on pe.persona_id = u.persona_id
                    inner join propiedades pr
                        on pr.usuario_id = u.usuario_id
                        and u.usuario_estado = 1
                        and pr.propiedad_estado = 1
                    inner join complejos co
                        on co.complejo_id = pr.complejo_id
                        and co.complejo_estado = 1
                        and co.complejo_id = ?
                WHERE
                    1 = (select pe.perfil_id from perfiles_usuarios pe where pe.usuario_id = ?)
                
                UNION
                -- empresa de administracion
                SELECT
                    u.usuario_id as codigo,
                    COALESCE(pe.persona_nombre1 || ' ' ||pe.persona_apellido1,pe.persona_nombre1) as nombre
                FROM
                    usuarios u
                    inner join personas pe
                        on pe.persona_id = u.persona_id
                    inner join propiedades pr
                        on pr.usuario_id = u.usuario_id
                        and u.usuario_estado = 1
                        and pr.propiedad_estado = 1
                    inner join complejos co
                        on co.complejo_id = pr.complejo_id
                        and co.complejo_estado = 1
                        and co.complejo_id = ?
                WHERE
                    co.empresa_id = 3 -- retorno de usuario_empresas where usuario = 10
                    and 2 = (select pe.perfil_id from perfiles_usuarios pe where pe.usuario_id = ?)
                UNION 
                SELECT -- usuario administrador
                    us.usuario_id as codigo,
                    COALESCE(pe.persona_nombre1 || ' ' ||pe.persona_apellido1,pe.persona_nombre1) as nombre
                from 
                    usuarios us
                    inner join personas pe
                        on pe.persona_id = us.persona_id
                    inner join propiedades pr
                        on pr.usuario_id = us.usuario_id
                        and us.usuario_estado = 1
                        and pr.propiedad_estado = 1
                    inner join complejos co
                        on co.complejo_id = pr.complejo_id
                        and co.complejo_estado = 1
                        and co.complejo_id = ?
                    and 3 = (select pe.perfil_id from perfiles_usuarios pe where pe.usuario_id = ?)";
        $result = DB::select($sql,array($complejo,$user,$complejo,$user,$complejo,$user));
        return $result;
    }


    public function getAdministradorComplejo($user,$complejo){
        $sql = "SELECT
                    u.usuario_id as codigo,
                    COALESCE(pe.persona_nombre1 || ' ' ||pe.persona_apellido1,pe.persona_nombre1) as nombre
                FROM
                    usuarios_administrador ua
                    inner join usuarios u
                        on u.usuario_id = ua.usuario_id
                    inner join personas pe
                        on pe.persona_id = u.persona_id
                    inner join complejos co
                        on co.complejo_id = ua.complejo_id
                        and co.complejo_id = ?
                        and co.complejo_estado = 1
                WHERE
                    1 = (select pe.perfil_id from perfiles_usuarios pe where pe.usuario_id = ?)
                UNION
                -- empresa de administracion
                SELECT
                    u.usuario_id as codigo,
                    COALESCE(pe.persona_nombre1 || ' ' ||pe.persona_apellido1,pe.persona_nombre1) as nombre
                FROM
                    usuarios_administrador ua
                    inner join usuarios u
                        on u.usuario_id = ua.usuario_id
                    inner join personas pe
                        on pe.persona_id = u.persona_id
                    inner join complejos co
                        on co.complejo_id = ua.complejo_id
                        and co.complejo_id = ?
                        and co.complejo_estado = 1
                WHERE
                    2 = (select pe.perfil_id from perfiles_usuarios pe where pe.usuario_id = ?)
                    and co.empresa_id = (select empresa_id from usuarios_empresas ue where ue.usuario_id = ? and ue.usuarioempresa_estado = 1)";
        $result = DB::select($sql,array($complejo,$user,$complejo,$user,$user));
        return $result;
    }

    function getProfile($user){
        $sql = "SELECT
                    pe.perfil_nombre
                FROM
                    perfiles_usuarios pu
                inner join perfiles pe
                    on pe.perfil_id = pu.perfil_id
                    and pu.perfilusuario_estado = 1
                where pu.usuario_id = ?";
        $result = DB::select($sql,array($user));
        return $result;
    }

    public function getUserByProperty($propiedad_id){
        $sql = "SELECT
                    us.usuario_id,
                    pr.propiedad_nombre
                from 
                    usuarios us
                inner join propiedades pr
                    on pr.usuario_id = us.usuario_id
                    and pr.propiedad_id = ?
                ";
        $result = DB::select($sql,array($propiedad_id));
        return $result;
    }

    /* ------------------------------ FILTROS COMUNICADOS ------------------------------ */
    public function getUsuariosAndComplejos($user){
        $sql = "select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?";
        $perfil = DB::select($sql,array($user));
        $perfil = $perfil[0]->perfil_id;
        switch($perfil){
            case 1: // superusuario
                $sql = "SELECT
                            (select
                                json_agg(tmp.*) as usuarios
                                from (
                                    select
                                        us.usuario_id as codigo,
                                        us.usuario_username as nombre
                                    from 
                                        usuarios us
                                    inner join propiedades pr
                                        on us.usuario_id = pr.usuario_id
                                        and pr.propiedad_estado = 1
                                        and us.usuario_estado = 1
                                    inner join complejos co
                                        on co.complejo_id = pr.complejo_id
                                        and co.complejo_estado = 1
                                    group by us.usuario_id, us.usuario_username
                                    order by us.usuario_username
                                ) as tmp
                            ) as usuarios,
                            (select
                                json_agg(tmp.*) as complejos
                                from (
                                    select
                                        co.complejo_id as codigo,
                                        co.complejo_nombre as nombre
                                    from 
                                        usuarios us
                                    inner join propiedades pr
                                        on us.usuario_id = pr.usuario_id
                                        and pr.propiedad_estado = 1
                                        and us.usuario_estado = 1
                                    inner join complejos co
                                        on co.complejo_id = pr.complejo_id
                                        and co.complejo_estado = 1
                                    group by co.complejo_id, co.complejo_nombre
                                     order by co.complejo_nombre
                                ) as tmp
                            )as complejos";
                $data = [];
            break;
            case 2: // empresas administracion
                $sql = "SELECT
                    (select
                        json_agg(tmp.*) as usuarios
                        from (
                            select
                                us.usuario_id as codigo,
                                us.usuario_username as nombre
                            from 
                                usuarios us
                            inner join propiedades pr
                                on us.usuario_id = pr.usuario_id
                                and pr.propiedad_estado = 1
                                and us.usuario_estado = 1
                            inner join complejos co
                                on co.complejo_id = pr.complejo_id
                                and co.complejo_estado = 1
                                and co.complejo_id in (
                                    select
                                        co1.complejo_id
                                    from
                                        usuarios_empresas ue
                                    inner join empresas em
                                        on ue.empresa_id = em.empresa_id
                                        and ue.usuario_id = ?
                                    inner join complejos co1
                                        on co1.empresa_id = em.empresa_id
                                )
                            group by us.usuario_id, us.usuario_username
							order by us.usuario_username
                        ) as tmp
                    )as usuarios,
                    (select
                        json_agg(tmp.*) as complejos
                        from (
                            select
                                co.complejo_id as codigo,
                                co.complejo_nombre as nombre
                            from 
                                usuarios us
                            inner join propiedades pr
                                on us.usuario_id = pr.usuario_id
                                and pr.propiedad_estado = 1
                                and us.usuario_estado = 1
                            inner join complejos co
                                on co.complejo_id = pr.complejo_id
                                and co.complejo_estado = 1
                                and co.complejo_id in (
                                    select
                                        co1.complejo_id
                                    from
                                        usuarios_empresas ue
                                    inner join empresas em
                                        on ue.empresa_id = em.empresa_id
                                        and ue.usuario_id = ?
                                    inner join complejos co1
                                        on co1.empresa_id = em.empresa_id
                                )
                            group by co.complejo_id, co.complejo_nombre
							order by co.complejo_nombre
                        ) as tmp
                    )as complejos";
                $data = array($user,$user);
            break;
            case 3: // administrador
                $sql = "SELECT
                            (select
                                json_agg(tmp.*) as usuarios
                                from (
                                    select
                                        us.usuario_id as codigo,
                                        us.usuario_username as nombre
                                    from 
                                        usuarios us
                                    inner join propiedades pr
                                        on us.usuario_id = pr.usuario_id
                                        and pr.propiedad_estado = 1
                                        and us.usuario_estado = 1
                                    inner join complejos co
                                        on co.complejo_id = pr.complejo_id
                                        and co.complejo_estado = 1
                                        and co.complejo_id in (
                                            select
                                                co.complejo_id
                                            from 
                                                usuarios_administrador ua
                                            inner join complejos co
                                                on co.complejo_id = ua.complejo_id
                                                and ua.usuario_id = ?
                                        )
                                    group by us.usuario_id, us.usuario_username
							        order by us.usuario_username
                                ) as tmp
                            ) as usuarios,
                            (select
                                json_agg(tmp.*) as complejos
                                from (
                                    select
                                        co.complejo_id as codigo,
                                        co.complejo_nombre as nombre
                                    from 
                                        usuarios us
                                    inner join propiedades pr
                                        on us.usuario_id = pr.usuario_id
                                        and pr.propiedad_estado = 1
                                        and us.usuario_estado = 1
                                    inner join complejos co
                                        on co.complejo_id = pr.complejo_id
                                        and co.complejo_estado = 1
                                        and co.complejo_id in (
                                            select
                                                co1.complejo_id
                                            from
                                                usuarios_administrador ua
                                            inner join complejos co1
                                                on co1.complejo_id = ua.complejo_id
                                                and ua.usuario_id = ?
                                        )
                                    group by co.complejo_id, co.complejo_nombre
							        order by co.complejo_nombre
                                ) as tmp
                            )as complejos";
                $data = array($user,$user);
            break;
        }
        $result = DB::select($sql,$data);
        return $result;
    }

    public function getAdministradores($user){
        $sql = "select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?";
        $perfil = DB::select($sql,array($user));
        $perfil = $perfil[0]->perfil_id;
        switch($perfil){
            case 1: // superusuario
                $sql = "SELECT
                            (select
                                json_agg(tmp.*) as usuarios
                                from (
                                    select
                                        us.usuario_id as codigo,
                                        us.usuario_username as nombre
                                    from 
                                        usuarios us
                                    inner join usuarios_administrador ua
                                        on us.usuario_id = ua.usuario_id
                                        and us.usuario_estado = 1
                                        and ua.usuarioadmin_estado = 1
                                    group by us.usuario_id, us.usuario_username
                                    order by us.usuario_username
                                ) as tmp
                            ) as usuarios,
                            (select
                                json_agg(tmp.*) as complejos
                                from (
                                    select
                                        co.complejo_id as codigo,
                                        co.complejo_nombre as nombre
                                    from 
                                        usuarios us
                                    inner join usuarios_administrador ua
                                        on us.usuario_id = ua.usuario_id
                                        and us.usuario_estado = 1
                                        and ua.usuarioadmin_estado = 1
                                    inner join complejos co
                                        on co.complejo_id = ua.complejo_id
                                        and co.complejo_estado = 1
                                    group by co.complejo_id, co.complejo_nombre
                                    order by co.complejo_nombre
                                ) as tmp
                            )as complejos";
                $data = [];
            break;
            case 2: // empresas administracion
                $sql = "SELECT
                    (select
                        json_agg(tmp.*) as usuarios
                        from (
                            select
                                us.usuario_id as codigo,
                                us.usuario_username as nombre
                            from 
                                usuarios us
                            inner join usuarios_administrador ua
                                    on us.usuario_id = ua.usuario_id
                                    and us.usuario_estado = 1
                                    and ua.usuarioadmin_estado = 1
                            inner join complejos co
                                on co.complejo_id = ua.complejo_id
                                and co.complejo_estado = 1
                                and co.complejo_id in (
                                    select
                                        co1.complejo_id
                                    from
                                        usuarios_empresas ue
                                    inner join empresas em
                                        on ue.empresa_id = em.empresa_id
                                        and ue.usuario_id = ?
                                    inner join complejos co1
                                        on co1.empresa_id = em.empresa_id
                                )
                            group by us.usuario_id, us.usuario_username
							order by us.usuario_username
                        ) as tmp
                    )as usuarios,
                    (select
                        json_agg(tmp.*) as complejos
                        from (
                            select
                                co.complejo_id as codigo,
                                co.complejo_nombre as nombre
                            from 
                                usuarios us
                            inner join usuarios_administrador ua
                                on us.usuario_id = ua.usuario_id
                                and us.usuario_estado = 1
                                and ua.usuarioadmin_estado = 1
                            inner join complejos co
                                on co.complejo_id = ua.complejo_id
                                and co.complejo_estado = 1
                                and co.complejo_id in (
                                    select
                                        co1.complejo_id
                                    from
                                        usuarios_empresas ue
                                    inner join empresas em
                                        on ue.empresa_id = em.empresa_id
                                        and ue.usuario_id = ?
                                    inner join complejos co1
                                        on co1.empresa_id = em.empresa_id
                                )
                            group by co.complejo_id, co.complejo_nombre
							order by co.complejo_nombre
                        ) as tmp
                    )as complejos";
                $data = array($user,$user);
            break;
        }
        $result = DB::select($sql,$data);
        return $result;
    }

    /* ------------------------------ FILTROS TRANSACCIONES ------------------------------*/
    public function getComplejosFilter($user){
        $sql = "SELECT -- superusuario
                    co.complejo_id as codigo,
                    co.complejo_nombre as nombre
                from 
                    complejos co
                where 
                    1 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                UNION
                SELECT  -- empresas
                    co.complejo_id as codigo,
                    co.complejo_nombre as nombre
                from 
                    complejos co
                inner join usuarios_empresas ue
                    on ue.usuario_id = ?
                    and ue.usuarioempresa_estado = 1
                inner join empresas em
                    on em.empresa_id = ue.empresa_id
                where 
                    2 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                    and co.empresa_id = em.empresa_id
                UNION
                SELECT  -- administradores
                    co.complejo_id as codigo,
                    co.complejo_nombre as nombre
                from 
                    complejos co
                inner join usuarios_administrador ua
                    on ua.usuario_id = ?
                    and ua.usuarioadmin_estado = 1
                    and ua.complejo_id = co.complejo_id
                where 
                    3 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                    and co.complejo_id = ua.complejo_id
                UNION
                SELECT  -- junta
                    co.complejo_id as codigo,
                    co.complejo_nombre as nombre
                from 
                    complejos co
                inner join juntas ju
                    on ju.complejo_id = co.complejo_id
                    and ju.junta_estado = 1
                    and co.complejo_estado = 1
                inner join usuarios_junta uj
                    on uj.junta_id = ju.junta_id
                    and uj.usuario_id = ?
                    and uj.usuariojunta_estado = 1
                where 
                    4 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                UNION
                SELECT  -- titular
                    co.complejo_id as codigo,
                    co.complejo_nombre as nombre
                from 
                    complejos co
                inner join propiedades pr
                    on pr.complejo_id = co.complejo_id
                    and pr.usuario_id = ?
                    and pr.propiedad_estado = 1
                    and co.complejo_estado = 1
                where 
                    5 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                ORDER BY nombre
                ";
        $result = DB::select($sql,array($user,$user,$user,$user,$user,$user,$user,$user,$user));
        return $result;
    }




    /* ------------------------------ FUNCIONES GENERALES ------------------------------*/
    public function getUser($user){
        $sql = "SELECT 
                    pe.*
                from 
                    usuarios us
                    inner join personas pe
                    on pe.persona_id = us.persona_id
                where usuario_id = ?";
        $result = DB::select($sql,array($user));
        return $result;
    }
    
    public function getCountries(){
        $sql = "SELECT 
                (select 
                    coalesce(json_agg(row_to_json(tmp.*)),'[]')
                from(
                    select 
                        pa.pais_codigo as codigo,
                        pa.pais_nombre as nombre
                    from 
                        paises pa
                    where pa.pais_estado = 1
                ) as tmp) as countries";
        $result = DB::select($sql);
        return $result;
    }

    public function getStates($country){
        $sql = "SELECT 
                    (select 
                        coalesce(json_agg(row_to_json(tmp.*)),'[]')
                    from(
                        select 
                            es.estado_codigo as codigo,
                            es.estado_nombre as nombre
                        from estados es
                        where es.pais_codigo = ?
                            and es.estado_estado = 1
                        order by es.estado_nombre
                    ) as tmp) as states
                ";
        $result = DB::select($sql,array($country));
        return $result;
    }

    public function getCities($country,$state){
        $sql = "SELECT 
                    (select 
                        coalesce(json_agg(row_to_json(tmp.*)),'[]')
                    from(
                        SELECT 
                            ci.ciudad_codigo as codigo,
                            ci.ciudad_nombre as nombre
                        from ciudades ci
                        where ci.pais_codigo = ? 
                            and ci.estado_codigo = ?
                            and ci.ciudad_estado = 1
                        order by ci.ciudad_nombre
                    ) as tmp) as cities";
        $result = DB::select($sql,array($country,$state));
        return $result;
    }

    public function getCityById($id){
        $sql = "SELECT ciudad_nombre from ciudades where ciudad_codigo = ?";
        $result = DB::select($sql,array($id));
        return $result;
    }

    public function getRealEstate($user,$complejo,$edificio = null){
        $strEdificio = "";
        if($edificio != null){
            $val = intval($edificio);
            $strEdificio = " and pr.edificio_id = $val ";
        }
        $sql = "SELECT
                    tmp.*
                from (
                    (SELECT -- superusuario
                        pr.propiedad_id as codigo,
                        pr.propiedad_nombre as nombre
                    from
                        propiedades pr
                    where 
                        pr.complejo_id = ?
                        $strEdificio
                        and 1 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                    order by 
                        pr.propiedad_nombre)
                    UNION
                    (SELECT -- empresa administracion
                        pr.propiedad_id as codigo,
                        pr.propiedad_nombre as nombre
                    from
                        propiedades pr
                        inner join complejos co
                            on co.complejo_id = pr.complejo_id
                        inner join usuarios_empresas ue
                            on ue.empresa_id = co.empresa_id
                            and ue.usuario_id = ?
                    where 
                        pr.complejo_id = ?
                        $strEdificio
                        and 2 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                    order by 
                        pr.propiedad_nombre)
                    UNION
                    (SELECT -- administracion
                        pr.propiedad_id as codigo,
                        pr.propiedad_nombre as nombre
                    from
                        propiedades pr
                        inner join complejos co
                            on co.complejo_id = pr.complejo_id
                        inner join usuarios_administrador ua
                            on ua.complejo_id = co.complejo_id
                            and ua.usuario_id = ?
                    where 
                        pr.complejo_id = ?
                        $strEdificio
                        and 3 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                    order by 
                        pr.propiedad_nombre)
                ) tmp
                order by tmp.nombre";
        $result = DB::select($sql,array($complejo,$user,$user,$complejo,$user,$user,$complejo,$user));
        return $result;
    }



}
