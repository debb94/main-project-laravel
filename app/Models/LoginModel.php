<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LoginModel extends Model{
    public function getUser($user){
        $sql = "SELECT 
                    us.usuario_id,
                    us.usuario_username,
                    us.usuario_password,
                    pe.persona_nombre1 || ' ' || pe.persona_apellido1 as nombre,
                    pu.perfil_id
                FROM 
                    usuarios us
                inner join personas pe
                    on us.persona_id = pe.persona_id
                inner join perfiles_usuarios pu
                    on pu.usuario_id = us.usuario_id
                where 
                    us.usuario_username = ?
                    and us.usuario_estado = 1";
        $result = DB::select($sql,array($user));
        return $result;
    }
}
