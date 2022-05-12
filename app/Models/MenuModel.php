<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MenuModel extends Model{
    
    public static function getMenu($userId){
        // OBTENER MENU SEGUN LOS PERMISOS DEL USUARIO
        $sql = "SELECT 
                    m.menu_nombre as menu_padre,
                    m.menu_tipo as menu_padre_tipo,
                    m.menu_icono as menu_icono,
                    m.menu_libreriaicono as menu_libreria_icono,
                    m2.menu_nombre as menu_hijo,
                    m2.menu_tipo as menu_hijo_tipo,
                    m2.menu_accion as menu_hijo_accion,
                    pm2.perfilmenu_permisos as permisos
                from 
                    menus m
                    inner join perfiles_menu pm
                        on pm.menu_id = m.menu_id
                        and pm.perfilmenu_estado = 1
                    inner join perfiles_usuarios pu 
                        on pu.perfil_id = pm.perfil_id
                        and pu.perfilusuario_estado = 1
                        and pu.usuario_id = ?
                    left join menus m2
                        on m2.menu_padre = m.menu_id
                        and m.menu_estado = 1
                        and m2.menu_estado = 1
                    inner join perfiles_menu pm2
                        on pm2.menu_id = m2.menu_id
                        and pm2.perfilmenu_estado = 1
                    inner join perfiles_usuarios pu2
                        on pu2.perfil_id = pm2.perfil_id
                        and pu2.perfilusuario_estado = 1
                        and pu2.usuario_id = ?
                where m.menu_padre is null 
                group by 
                    m.menu_padre,
                    m.menu_nombre,
                    m2.menu_nombre,
                    m.menu_tipo,
                    m.menu_icono,
                    m.menu_libreriaicono,
                    m2.menu_tipo,
                    m2.menu_accion,
                    pm2.perfilmenu_permisos,
                    m.menu_orden,
                    m2.menu_orden
                order by m.menu_orden, m2.menu_orden";
        $result = DB::select($sql,array($userId,$userId));
        return $result;
    }
}
