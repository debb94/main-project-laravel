<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ForgotPasswordModel extends Model{
    
    public function flagPasswordUser($user,$key){
        $sql = "UPDATE 
                    usuarios 
                set 
                    usuario_recuperar = 1, 
                    usuario_recuperartoken = ?, 
                    usuario_recuperarfecha = now() + interval '1day'
                where
                    usuario_username = ?
                    and usuario_estado = 1
                returning usuario_username,usuario_id";
        $result = DB::select($sql,array($key,$user));
        return $result;
    }

    public function updatePassword($pass,$keyRecovery){
        $sql ="UPDATE
                    usuarios
                set 
                    usuario_recuperar = 0,
                    usuario_recuperartoken = null,
                    usuario_recuperarfecha = null,
                    usuario_password = ?
                where
                    usuario_recuperartoken = ?
                    and usuario_recuperarfecha >= now()
                returning usuario_id";
        $result = DB::select($sql,array($pass,$keyRecovery));
        return $result;
    }




    public function getPerson($user){
        $sql = "SELECT
                    coalesce(pe.persona_nombre1||' '||pe.persona_apellido1||' '||pe.persona_apellido2,pe.persona_nombre1||' '||pe.persona_apellido1) nombre_completo
                FROM
                    personas pe
                inner join usuarios us
                    on us.usuario_id = ?
                    and us.persona_id = pe.persona_id";
        $result = DB::select($sql,array($user));
        return $result;
    }


}
