<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PagosCondominiosModel extends Model{

    private $tableMantenimiento;
    private $tableProductos;
    private $tableDerramas;
    private $tableMultas;
    
    public function __construct(){
        if(env('APP_ENV') == 'sandbox' && env('TRANSACTIONS') == 'sandbox'){
            $this->tableMantenimiento = 'privado.pagos_realizados_clover_test';
            $this->tableProductos     = 'privado.pagos_productos_clover_test';
            $this->tableDerramas      = 'privado.pagos_derramas_clover_test';
            $this->tableMultas        = 'privado.pagos_multas_clover_test';
        }else{
            $this->tableMantenimiento = 'privado.pagos_realizados_clover';
            $this->tableProductos     = 'privado.pagos_productos_clover';
            $this->tableDerramas      = 'privado.pagos_derramas_clover';
            $this->tableMultas        = 'privado.pagos_multas_clover';
        }
        
    }

    public function getInfo($user,$id = null,$complejoId=null,$dStart=null,$dEnd=null){
        if($complejoId != ""){
            $complejo = "and complejo_id = ?";
            $data = array($complejoId,$dStart,$dEnd,$complejoId,$dStart,$dEnd, $complejoId,$dStart,$dEnd, $complejoId,$dStart,$dEnd);
        }else{
            $complejo = "";
            $data = array($dStart,$dEnd,$dStart,$dEnd,$dStart,$dEnd,$dStart,$dEnd);
        }
        $sql = "SELECT 
                (
                    select json_agg(tmp.*) 
                    from (
                        select 
                            coalesce((sum(pa.amount)/100)::numeric(12,2),0.00) total,
                            coalesce((sum(pa.fee)/100)::numeric(12,2),0.00) fee
                        from $this->tableMantenimiento pa
                        inner join tx_mantenimiento tx
                            on tx.tx_id = pa.tx_id
                            $complejo
                        where 
                            pa.pago_fcreacion >= ?
                            and pa.pago_fcreacion <= ?
                    ) as tmp
                ) mantenimiento,
                (
                    select json_agg(tmp.*) 
                    from (
                        select 
                            coalesce((sum(amount)/100)::numeric(12,2),0.00) total,
                            coalesce((sum(fee)/100)::numeric(12,2),0.00) fee
                        from $this->tableProductos pa
                        inner join tx_productos tx
                            on pa.tx_id = tx.tx_id
                        inner join propiedades pro
                            on pro.propiedad_id = tx.propiedad_id
                            $complejo
                        where 
                            pa.pago_fcreacion >= ?
                            and pa.pago_fcreacion <= ?
                    ) as tmp
                ) productos,
                (
                    select json_agg(tmp.*) 
                    from (
                        select 
                            coalesce((sum(amount)/100)::numeric(12,2),0.00) total,
                            coalesce((sum(fee)/100)::numeric(12,2),0.00) fee
                        from $this->tableDerramas pa
                        inner join tx_derramas tx
                            on tx.tx_id = pa.tx_id
                        inner join propiedades pr
                            on pr.propiedad_id = tx.propiedad_id
                            $complejo
                        where 
                            pa.pago_fcreacion >= ?
                            and pa.pago_fcreacion <= ?
                    ) as tmp
                ) derramas,
                (
                    select json_agg(tmp.*) 
                    from (
                        select 
                            coalesce((sum(amount)/100)::numeric(12,2),0.00) total,
                            coalesce((sum(fee)/100)::numeric(12,2),0.00) fee
                        from $this->tableMultas pa
                        inner join tx_multas tx
                            on tx.tx_id = pa.tx_id
                        inner join propiedades pr
                            on pr.propiedad_id = tx.propiedad_id
                            $complejo
                        where 
                            pa.pago_fcreacion >= ?
                            and pa.pago_fcreacion <= ?
                    ) as tmp
                ) multas
        ";
        $result = DB::select($sql,$data);
        return $result;
    }
    
    /**
     * @author Daniel Bolivar
     * @internal get info condominium payments without comision
     * @param 
     */
    public function getDataReportCondominiumPayments($complejoId, $dStart, $dEnd){
        if($complejoId != null){
            $complejo = "and co.complejo_id = ?";
            $complejo_where = "where co.complejo_id = ?";
            $data = array($dStart,$dEnd,$complejoId, $complejoId,$dStart,$dEnd, $complejoId,$dStart,$dEnd, $complejoId,$dStart,$dEnd, $complejoId);
        }else{
            $complejo = "";
            $complejo_where = "";
            $data = array($dStart,$dEnd,$dStart,$dEnd,$dStart,$dEnd,$dStart,$dEnd);
        }
        $sql = "SELECT 
                    co.complejo_id,
                    co.complejo_nombre,
                    mantenimiento.amount as amount_mantenimiento,
                    mantenimiento.fee as fee_mantenimiento,
                    productos.amount as amount_productos,
                    productos.fee as fee_productos,
                    derramas.amount as amount_derramas,
                    derramas.fee as fee_derramas,
                    multas.amount as amount_multas,
                    multas.fee as fee_multas
                from 
                    complejos co
                    left join (
                        select
                            (sum(pm.amount)/100)::numeric(12,2) as amount,
                            (sum(pm.fee)/100)::numeric(12,2) as fee,
                            co.complejo_id
                        from
                            tx_mantenimiento tx
                        inner join $this->tableMantenimiento pm
                            on pm.tx_id = tx.tx_id
                            and tx.tx_fcreacion >= ?
                            and tx.tx_fcreacion <= ?
                        inner join complejos co
                            on co.complejo_id = tx.complejo_id
                            and co.complejo_estado = 1
                            $complejo
                        group by co.complejo_id
                    ) mantenimiento
                        on mantenimiento.complejo_id = co.complejo_id
                    left join (
                        select 
                            (sum(pp.amount)/100)::numeric(12,2) amount,
                            (sum(pp.fee)/100)::numeric(12,2) fee,
                            co.complejo_id
                        from
                            tx_productos tx
                        inner join productos_asignados pa
                            on tx.tx_id = pa.tx_id
                        inner join propiedades pr
                            on pr.propiedad_id = pa.propiedad_id
                        inner join complejos co
                            on pr.complejo_id = co.complejo_id
                            and co.complejo_estado = 1
                            $complejo
                        inner join $this->tableProductos pp
                            on pp.tx_id = tx.tx_id
                            and pp.pago_fcreacion >= ?
                            and pp.pago_fcreacion <= ?
                        group by co.complejo_id
                        ) productos
                        on productos.complejo_id = co.complejo_id
                    left join (
                        select 
                            (sum(pd.amount)/100)::numeric(12,2) amount,
                            (sum(pd.fee)/100)::numeric(12,2) fee,
                            co.complejo_id
                        from
                            tx_derramas tx
                        inner join derramas_asignadas da
                            on tx.tx_id = da.tx_id
                        inner join propiedades pr
                            on pr.propiedad_id = da.propiedad_id
                        inner join complejos co
                            on pr.complejo_id = co.complejo_id
                            and co.complejo_estado = 1
                            $complejo
                        inner join $this->tableDerramas pd
                            on pd.tx_id = tx.tx_id
                            and pd.pago_fcreacion >= ?
                            and pd.pago_fcreacion <= ?
                        group by co.complejo_id
                        ) derramas
                        on derramas.complejo_id = co.complejo_id
                    left join (
                        select 
                            (sum(pm.amount)/100)::numeric(12,2) amount,
                            (sum(pm.fee)/100)::numeric(12,2) fee,
                            co.complejo_id
                        from
                            tx_multas tx
                        inner join multas_asignadas ma
                            on tx.tx_id = ma.tx_id
                        inner join propiedades pr
                            on pr.propiedad_id = ma.propiedad_id
                        inner join complejos co
                            on pr.complejo_id = co.complejo_id
                            and co.complejo_estado = 1
                            $complejo
                        inner join $this->tableMultas pm
                            on pm.tx_id = tx.tx_id
                            and pm.pago_fcreacion >= ?
                            and pm.pago_fcreacion <= ?
                        group by co.complejo_id
                        ) multas
                        on multas.complejo_id = co.complejo_id
                $complejo_where
                group by co.complejo_id, co.complejo_nombre,mantenimiento.amount, mantenimiento.fee, productos.amount, productos.fee, derramas.amount, derramas.fee, multas.amount, multas.fee
                order by co.complejo_nombre;";
        $result = DB::select($sql,$data);
        return $result;
    }


}
