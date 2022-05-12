<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EmpresasModel extends Model{

    public $table           = "empresas";
    public $identificador   = "empresa_id";
    public $creador         = "empresa_creadopor";
    public $actualizador    = "empresa_actualizadopor";
    public $factualizacion  = "empresa_factualizacion";
    public $sqlEstado       = "empresa_estado";

    public function get($id = null){
        if($id == null){
            $sql = "SELECT 
                        em.empresa_id,
                        em.empresa_nombrecorto,
                        em.empresa_tiporelacion,
                        em.estado_codigo,
                        em.empresa_correo,
                        em.empresa_estado
                    from 
                        empresas em
                    order by em.empresa_fcreacion";
            $result = DB::select($sql);
        }else{
            $sql = "SELECT 
                        em.*,
                        pa.pais_nombre,
                        es.estado_nombre,
                        ci.ciudad_nombre
                    from 
                        empresas em
                        inner join paises pa
                            on pa.pais_codigo = em.pais_codigo
                            and pa.pais_estado = 1
                        inner join estados es
                            on es.estado_codigo = em.estado_codigo
                            and es.estado_estado = 1
                        inner join ciudades ci
                            on ci.ciudad_codigo = em.ciudad_codigo
                            and ci.ciudad_estado = 1
                    where em.empresa_id = ?";
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
                            pa.parametro_valor as codigo,
                            pa.parametro_nombrecorto as nombre
                        from 
                            parametros_configuracion pa
                        where pa.parametro_tipo = 'TIPO_RELACION' 
                                            and pa.parametro_estado = 1 
                                            order by pa.parametro_orden
                    ) as tmp) as tipo_relacion,
                    (select 
                        coalesce(json_object_agg(tmp.codigo,tmp.nombre),'{}')::text
                    from(
                        select 
                            pa.parametro_valor as codigo,
                            pa.parametro_nombrecorto as nombre
                        from 
                            parametros_configuracion pa
                        where pa.parametro_tipo = 'TIPO_DNI' 
                                            and pa.parametro_estado = 1 
                                            order by pa.parametro_orden
                    ) as tmp) as tipo_dni";
        $result = DB::select($sql);
        return $result;
    }





    // actualizacion
    public function updateData($form,$id,$user){

        // SELECT column_name,data_type                  --Seleccionamos el nombre de columna
        // FROM information_schema.columns     --Desde information_schema.columns
        // WHERE table_schema = 'public'       --En el esquema que tenemos las tablas en este caso public
        // AND table_name   = 'empresas' 

        // $form->empresa_factualizacion = 'now()';
        // $arrayInsert = [];
        // $arrayValues = [];
        // foreach($form as $key=>$value){
        //     $arrayInsert[] = $key;
        //     $arrayValues[] = $value; 
        // }
        // $sqlInsert = implode(',',$arrayInsert);

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

        // $sql ="UPDATE complejos set 
        //         complejo_nombre = '$form->nombre', 
        //         empresa_id = $form->empresa, 
        //         pais_codigo = '$form->pais', 
        //         estado_codigo = '$form->estado',
        //         ciudad_codigo = $form->ciudad,
        //         complejo_estado = $form->status,
        //         complejo_actualizadopor = $user,
        //         complejo_factualizacion = now()
        //         where complejo_id = $id";  
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

        // SELECT column_name,data_type                  --Seleccionamos el nombre de columna
        // FROM information_schema.columns     --Desde information_schema.columns
        // WHERE table_schema = 'public'       --En el esquema que tenemos las tablas en este caso public
        // AND table_name   = 'empresas' 
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
        
        // $form->empresa_factualizacion = 'now()';
        // $arrayInsert = [];
        // $arrayValues = [];
        // foreach($form as $key=>$value){
        //     $arrayInsert[] = $key;
        //     $arrayValues[] = $value; 
        // }
        // $sqlInsert = implode(',',$arrayInsert);




        // $sql = "INSERT INTO complejos
        //             (complejo_nombre, empresa_id, pais_codigo, estado_codigo,ciudad_codigo,complejo_estado,complejo_creadopor)
        //         VALUES
        //             ('$form->nombre',$form->empresa,'$form->pais','$form->estado',$form->ciudad,$form->status,$user);";
        $result = DB::insert($sql,$sqlValues);
        return $result;
    }

    // eliminacion desactivacion
    public function inactive($id){
        $sql    = "UPDATE complejos set complejo_estado = 0 where complejo_id = ?";
        $result = DB::update($sql,array($id));
        return $result;
    }
}
