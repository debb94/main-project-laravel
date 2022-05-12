<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class HomeModel extends Model{
    // public $table           = "usuarios";
    // public $identificador   = "usuario_id";
    // public $creador         = "usuario_creadopor";
    // public $actualizador    = "usuario_actualizadopor";
    // public $factualizacion  = "usuario_factualizacion";
    // public $sqlEstado       = "usuario_estado";

    public $tablePagosRealizados ="";

    function __construct(){
        $this->tablePagosRealizados = (env('TRANSACTIONS') == 'production') ? 'privado.pagos_realizados_clover' : 'privado.pagos_realizados_clover_test';
        $this->tableMantenimiento   = (env('TRANSACTIONS') == 'production') ? 'privado.pagos_realizados_clover' : 'privado.pagos_realizados_clover_test';
        $this->tableProductos       = (env('TRANSACTIONS') == 'production') ? 'privado.pagos_productos_clover' : 'privado.pagos_productos_clover_test';
        $this->tableMultas          = (env('TRANSACTIONS') == 'production') ? 'privado.pagos_multas_clover' : 'privado.pagos_multas_clover_test';
        $this->tableDerramas        = (env('TRANSACTIONS') == 'production') ? 'privado.pagos_derramas_clover' : 'privado.pagos_derramas_clover_test';
    }

    // usuario
    public function getMainData($user){
        /* $sql = "SELECT 
                tx.tx_saldo,
                tx.tx_valortotalpagar,
                tx.tx_ultimopago
            from 
                propiedades pr
                inner join complejos co
                    on co.complejo_id = pr.complejo_id
                    and co.complejo_estado = 1
                inner join tx_mantenimiento tx
                    on tx.propiedad_id = pr.propiedad_id
                    and tx.usuario_id = ?
                    and tx.complejo_id = co.complejo_id
                    and tx.tx_estado = 'P'
            where pr.usuario_id = ?
            and pr.propiedad_estado = 1"; */
        // $sql = "SELECT 
        //         (select sum(tx.tx_valortotalpagar) from tx_mantenimiento tx where tx.usuario_id = ? and tx.tx_estado = 'P') as tx_valortotalpagar,
        //         (select sum(tx.tx_saldo) from tx_mantenimiento tx where tx.usuario_id = ? and tx.tx_estado = 'P') as tx_saldo,
        //         (select max(tx.tx_ultimopago) from tx_mantenimiento tx where tx.usuario_id = ? and tx.tx_estado = 'P')as tx_ultimopago,
        //         (SELECT array_to_json(array_agg(txm.*)) as transacciones FROM (
        //             select 
        //                 txm.tx_referencia,
        //                 txm.tx_fexpedicion,
        //                 txm.tx_valortotalpagar,
        //                 txm.tx_estado,
        //                 pr.propiedad_nombre,
        //                 co.complejo_nombre 
        //             FROM
        //                 tx_mantenimiento txm
        //                 inner join complejos co
        //                     on co.complejo_id = txm.complejo_id
        //                 inner join propiedades pr
        //                     on co.complejo_id = pr.complejo_id
        //                     and pr.propiedad_id = txm.propiedad_id
        //             where
        //                 txm.usuario_id = ?
        //                 and txm.tx_estado in ('A','R','F','I','V')
        //             order by tx_fexpedicion
        //             limit 10) as txm
        //         ) as transacciones";
        $sql = "SELECT 
                coalesce((select json_agg(tmp.*)
                from (
                    select coalesce(sum(tx.tx_valortotalpagar),0.00) as valor, tx_id from tx_mantenimiento tx where tx.usuario_id = ? and tx.tx_estado = 'P'  group by tx_id
                ) tmp),'[]') mantenimiento,
                -- (select coalesce(sum(tx.tx_valortotalpagar),0.00) from tx_mantenimiento tx where tx.usuario_id = ? and tx.tx_estado = 'P') as mantenimiento,
                -- (select coalesce(sum(tx.tx_valortotalpagar),0.00) from tx_productos tx where tx.usuario_id = ? and tx.tx_estado = 'P') as producto,
                coalesce((select json_agg(tmp.*)
                from (
                    select coalesce(sum(tx.tx_valortotalpagar),0.00) as valor, tx_id from tx_productos tx where tx.usuario_id = ? and tx.tx_estado = 'P'  group by tx_id
                ) tmp),'[]') productos,
                -- (select coalesce(sum(tx.tx_valortotalpagar),0.00) from tx_multas tx where tx.usuario_id = ? and tx.tx_estado = 'P') as multas,
                coalesce((select json_agg(tmp.*)
                    from (
                        select coalesce(sum(tx.tx_valortotalpagar),0.00) as valor, tx_id from tx_multas tx where tx.usuario_id = ? and tx.tx_estado = 'P'  group by tx_id
                    ) tmp),'[]') multas,
                -- (select coalesce(sum(tx.tx_valortotalpagar),0.00) from tx_derramas tx where tx.usuario_id = ? and tx.tx_estado = 'P') as derramas,
                coalesce((select json_agg(tmp.*)
                    from (
                        select coalesce(sum(tx.tx_valortotalpagar),0.00) as valor, tx_id from tx_derramas tx where tx.usuario_id = ? and tx.tx_estado = 'P'  group by tx_id
                    ) tmp),'[]') derramas,
                (select '[]') as servicios,
                -- (select sum(tx.tx_valortotalpagar) from tx_derrama tx where tx.usuario_id = ? and tx.tx_estado = 'P') as derrama,
                -- (select sum(tx.tx_valortotalpagar) from tx_servicios tx where tx.usuario_id = ? and tx.tx_estado = 'P') as servicios,
                (select sum(tx.tx_saldo) from tx_mantenimiento tx where tx.usuario_id = ? and tx.tx_estado = 'P') as tx_saldo,
                (select max(tx.tx_ultimopago) from tx_mantenimiento tx where tx.usuario_id = ? and tx.tx_estado = 'P')as tx_ultimopago,
                (SELECT array_to_json(array_agg(tx.*)) as transacciones FROM (
                    -- mantenimiento
                    select 
                        tx.tx_referencia,
                        tx.tx_fexpedicion,
                        -- tx.tx_valortotalpagar,
                        tx.tx_valorpagado,
                        tx.tx_estado,
                        pr.propiedad_nombre,
                        co.complejo_nombre 
                    FROM
                        tx_mantenimiento tx
						inner join propiedades pr
							on pr.propiedad_id = tx.propiedad_id
                        inner join complejos co
                            on co.complejo_id = pr.complejo_id
                    where
                        tx.usuario_id = ?
                        and tx.tx_estado in ('A','R','F','I','V')
					union
                    -- productos
                    select 
                        tx.tx_referencia,
                        tx.tx_fexpedicion,
                        tx.tx_valortotalpagar as tx_valorpagado,
                        tx.tx_estado,
                        pr.propiedad_nombre,
                        co.complejo_nombre 
                    FROM
                        tx_productos tx
                        inner join propiedades pr
							on pr.propiedad_id = tx.propiedad_id
                        inner join complejos co
                            on co.complejo_id = pr.complejo_id
                    where
                        tx.usuario_id = ?
                        and tx.tx_estado in ('A','R','F','I','V')
                    union
                    -- multas
					(select 
                        tx.tx_referencia,
                        tx.tx_fexpedicion,
                        tx.tx_valortotalpagar as tx_valorpagado,
                        tx.tx_estado,
                        pr.propiedad_nombre,
                        co.complejo_nombre 
                    FROM
                        tx_multas tx
						inner join propiedades pr
							on pr.propiedad_id = tx.propiedad_id
                        inner join complejos co
                            on co.complejo_id = pr.complejo_id
                    where
                        tx.usuario_id = ?
                        and tx.tx_estado in ('A','R','F','I','V')
                    order by tx_fexpedicion)
                    union
                    -- derramas
					select 
                        tx.tx_referencia,
                        tx.tx_fexpedicion,
                        tx.tx_valortotalpagar as tx_valorpagado,
                        tx.tx_estado,
                        pr.propiedad_nombre,
                        co.complejo_nombre 
                    FROM
                        tx_derramas tx
						inner join propiedades pr
							on pr.propiedad_id = tx.propiedad_id
                        inner join complejos co
                            on co.complejo_id = pr.complejo_id
                    where
                        tx.usuario_id = ?
                        and tx.tx_estado in ('A','R','F','I','V')
                    order by tx_fexpedicion
                    limit 10) as tx
                ) as transacciones";
        $result = DB::select($sql,array($user,$user,$user,$user,$user,$user,$user,$user,$user,$user));
        return $result;   
    }
    /**
     * Obitene informacion sobre las transacciones lo pagado este anio, este mes. lo que se debe este mes y en el anio.
     */
    public function getMainDataAdminSuperusuario(){
        $sql = "SELECT
                    *
                from
                    (select
                        coalesce((sum(tmp.amount)/100)::numeric(12,2),0) as amount,
                        tmp.transactions,
                        coalesce((sum(tmp.fee)/100)::numeric(12,2),0) as fee
                    from (
                        -- mantenimiento
                        select 
                            sum(ma.amount) as amount,
                            count(1) as transactions,
                            sum(ma.fee) as fee
                        from
                            $this->tableMantenimiento ma
                        union
                        -- productos
                        select 
                            sum(pr.amount) as amount,
                            count(1) as transactions,
                            sum(pr.fee) as fee
                        from
                            $this->tableProductos pr
                        union
                        -- multas
                        select 
                            sum(mu.amount) as amount,
                            count(1) as transactions,
                            sum(mu.fee) as fee
                        from
                            $this->tableMultas mu
                        union
                        -- derramas
                        select 
                            sum(de.amount) as amount,
                            count(1) as transactions,
                            sum(de.fee) as fee
                        from
                            $this->tableDerramas de
                        ) as tmp
                        group by tmp.transactions
                    ) tx_r,
                    (select
                        count(1) pending_transactions
                    from (
                        select
                            tx_id
                        from
                            tx_mantenimiento txm
                        where txm.tx_estado = 'P'
                        union
                        select
                            tx_id
                        from
                            tx_multas tx
                        where tx.tx_estado = 'P'
                        union
                        select
                            tx_id
                        from
                            tx_productos tx
                        where tx.tx_estado = 'P'
                        union
                        select
                            tx_id
                        from
                            tx_derramas tx
                        where tx.tx_estado = 'P'
                    ) as tmp) as tx_p,
                    (select 
                        count(1) as active_customers
                    from 
                        usuarios us
                    inner join perfiles_usuarios pu
                        on pu.usuario_id = us.usuario_id
                        and pu.perfil_id = 5
                        and us.usuario_estado = 1) as customers,
                    (select 
                        count(1) as inactive_customers
                    from 
                        usuarios us
                    inner join perfiles_usuarios pu
                        on pu.usuario_id = us.usuario_id
                        and pu.perfil_id = 5
                        and us.usuario_estado = 0) inactive_customers";
        $result = DB::select($sql);
        return $result;
    }
    public function getMainDataAdminEmpresa($user){
        $sql = "SELECT
                    *
                from
                    (select
                        coalesce((sum(tmp.amount)/100)::numeric(12,2),0) as amount,
                        tmp.transactions,
                        coalesce((sum(tmp.fee)/100)::numeric(12,2),0) as fee
                    from (
                        select 
                            sum(ma.amount) as amount,
                            count(1) as transactions,
                            sum(ma.fee) as fee
                        from
                            $this->tableMantenimiento ma
                            inner join tx_mantenimiento tx
                                on tx.tx_id = ma.tx_id
                            inner join complejos co
                                on co.complejo_id = tx.complejo_id
                            inner join usuarios_empresas ue
                                on ue.empresa_id = co.empresa_id
                                and ue.usuario_id = ?
                        union
                        select 
                            sum(pr.amount) as amount,
                            count(1) as transactions,
                            sum(pr.fee) as fee
                        from
                            $this->tableProductos pr
                            inner join tx_productos tx
                                on tx.tx_id = pr.tx_id
                            inner join propiedades pro
                                on pro.propiedad_id = tx.propiedad_id
                            inner join complejos co
                                on co.complejo_id = pro.complejo_id
                            inner join usuarios_empresas ue
                                on ue.empresa_id = co.empresa_id
                                and ue.usuario_id = ?
                        union
                        select 
                            sum(mu.amount) as amount,
                            count(1) as transactions,
                            sum(mu.fee) as fee
                        from
                            $this->tableMultas mu
                            inner join tx_multas tx
                                on tx.tx_id = mu.tx_id
                            inner join propiedades pro
                                on pro.propiedad_id = tx.propiedad_id
                            inner join complejos co
                                on co.complejo_id = pro.complejo_id
                            inner join usuarios_empresas ue
                                on ue.empresa_id = co.empresa_id
                                and ue.usuario_id = ?
                        union
                        select 
                            sum(de.amount) as amount,
                            count(1) as transactions,
                            sum(de.fee) as fee
                        from
                            $this->tableDerramas de
                            inner join tx_derramas tx
                                on tx.tx_id = de.tx_id
                            inner join propiedades pro
                                on pro.propiedad_id = tx.propiedad_id
                            inner join complejos co
                                on co.complejo_id = pro.complejo_id
                            inner join usuarios_empresas ue
                                on ue.empresa_id = co.empresa_id
                                and ue.usuario_id = ?
                        ) as tmp
                        group by tmp.transactions
                    ) tx_r,
                    (select
                        count(tx_valortotalpagar) as pending_transactions,
                        coalesce(sum(tx_valortotalpagar),0.00) as pending_amount
                    from
                        tx_mantenimiento tx
                        inner join complejos co
                            on co.complejo_id = tx.complejo_id
                        inner join usuarios_empresas ue
                            on ue.empresa_id = co.empresa_id
                            and ue.usuario_id = ?
                    where tx.tx_estado = 'P') as tx_p,
                    (select 
                        count(1) as active_customers
                    from 
                        usuarios us
                        inner join propiedades pr
                            on pr.usuario_id = us.usuario_id
                        inner join complejos co
                            on co.complejo_id = pr.complejo_id
                        inner join usuarios_empresas ue
			                on ue.empresa_id = co.empresa_id
			                and ue.usuario_id = ?
                        inner join perfiles_usuarios pu
                            on pu.usuario_id = us.usuario_id
                            and pu.perfil_id = 5
                            and us.usuario_estado = 1) as customers,
                    (select 
                        count(1) as inactive_customers
                    from 
                        usuarios us
                        inner join propiedades pr
                            on pr.usuario_id = us.usuario_id
                        inner join complejos co
                            on co.complejo_id = pr.complejo_id
                        inner join usuarios_empresas ue
			                on ue.empresa_id = co.empresa_id
			                and ue.usuario_id = ?
                        inner join perfiles_usuarios pu
                            on pu.usuario_id = us.usuario_id
                            and pu.perfil_id = 5
                            and us.usuario_estado = 0) inactive_customers";
        $result = DB::select($sql,array($user,$user,$user,$user,$user,$user,$user));
        return $result;
    }

    public function getChartAdminSuperusuario(){
        $sql = "SELECT
                    (sum(tmp.amount)/100)::numeric(12,2) amount_chart,
                    case
                        when mes = '01' then 'Enero'
                        when mes = '02' then 'Febrero'
                        when mes = '03' then 'Marzo'
                        when mes = '04' then 'Abril'
                        when mes = '05' then 'Mayo'
                        when mes = '06' then 'Junio'
                        when mes = '07' then 'Julio'
                        when mes = '08' then 'Agosto'
                        when mes = '09' then 'Septiembre'
                        when mes = '10' then 'Octubre'
                        when mes = '11' then 'Noviembre'
                        else 'Diciembre' end as month
                from
                (
                    select 
                        sum(amount) amount,
                        to_char(pago_fcreacion,'mm') mes
                    from
                        $this->tableMantenimiento
                    where pago_fcreacion > concat(to_char(now() -interval '7 month','YYYY-mm'),'-01')::timestamp
                    group by pago_fcreacion
                    union 
                    select 
                        sum(amount),
                        to_char(pago_fcreacion,'mm') mes
                    from
                        $this->tableProductos
                    where pago_fcreacion > concat(to_char(now() -interval '7 month','YYYY-mm'),'-01')::timestamp
                    group by pago_fcreacion
                    union 
                    select 
                        sum(amount),
                        to_char(pago_fcreacion,'mm') mes
                    from
                        $this->tableMultas
                    where pago_fcreacion > concat(to_char(now() -interval '7 month','YYYY-mm'),'-01')::timestamp
                    group by pago_fcreacion
                    union
                    select 
                        sum(amount),
                        to_char(pago_fcreacion,'mm') mes
                    from
                        $this->tableDerramas
                    where pago_fcreacion > concat(to_char(now() -interval '7 month','YYYY-mm'),'-01')::timestamp
                    group by pago_fcreacion
                ) tmp
                group by mes
                order by mes";
        $result = DB::select($sql);
        return $result;
    }
    public function getChartAdminEmpresa($user){
        $sql = "SELECT
                    (sum(tmp.amount)/100)::numeric(12,2) amount_chart,
                    case
                        when mes = '01' then 'Enero'
                        when mes = '02' then 'Febrero'
                        when mes = '03' then 'Marzo'
                        when mes = '04' then 'Abril'
                        when mes = '05' then 'Mayo'
                        when mes = '06' then 'Junio'
                        when mes = '07' then 'Julio'
                        when mes = '08' then 'Agosto'
                        when mes = '09' then 'Septiembre'
                        when mes = '10' then 'Octubre'
                        when mes = '11' then 'Noviembre'
                        else 'Diciembre' end as month
                from
                (
                    select 
                        sum(pr.amount) amount,
                        to_char(pr.pago_fcreacion,'mm') mes
                    from
                        $this->tableMantenimiento pr
                    inner join tx_mantenimiento tx
                        on tx.tx_id = pr.tx_id
                    inner join complejos co
                        on co.complejo_id = tx.complejo_id
                    inner join empresas em
                        on em.empresa_id = co.empresa_id
                    inner join usuarios_empresas ue
                        on ue.empresa_id = em.empresa_id
                        and ue.usuario_id = ?
                        
                    where pago_fcreacion > concat(to_char(now() -interval '7 month','YYYY-mm'),'-01')::timestamp
                    group by pr.pago_fcreacion
                    union 
                    select 
                        sum(pr.amount),
                        to_char(pr.pago_fcreacion,'mm') mes
                    from
                        $this->tableProductos pr
                    inner join tx_productos tx
                        on tx.tx_id = pr.tx_id
                    inner join propiedades pro
                        on pro.propiedad_id = tx.propiedad_id
                    inner join complejos co
                        on co.complejo_id = pro.complejo_id
                    inner join empresas em
                        on em.empresa_id = co.empresa_id
                    inner join usuarios_empresas ue
                        on ue.empresa_id = em.empresa_id
                        and ue.usuario_id = ?
                    where pr.pago_fcreacion > concat(to_char(now() -interval '7 month','YYYY-mm'),'-01')::timestamp
                    group by pr.pago_fcreacion
                    union 
                    select 
                        sum(pr.amount),
                        to_char(pr.pago_fcreacion,'mm') mes
                    from
                        $this->tableMultas pr
                    inner join tx_multas tx
                        on tx.tx_id = pr.tx_id
                    inner join propiedades pro
                        on pro.propiedad_id = tx.propiedad_id
                    inner join complejos co
                        on co.complejo_id = pro.complejo_id
                    inner join empresas em
                        on em.empresa_id = co.empresa_id
                    inner join usuarios_empresas ue
                        on ue.empresa_id = em.empresa_id
                        and ue.usuario_id = ?
                    where pr.pago_fcreacion > concat(to_char(now() -interval '7 month','YYYY-mm'),'-01')::timestamp
                    group by pr.pago_fcreacion
                    union
                    select 
                        sum(pr.amount),
                        to_char(pr.pago_fcreacion,'mm') mes
                    from
                        $this->tableDerramas pr
                    inner join tx_derramas tx
                        on tx.tx_id = pr.tx_id
                    inner join propiedades pro
                        on pro.propiedad_id = tx.propiedad_id
                    inner join complejos co
                        on co.complejo_id = pro.complejo_id
                    inner join empresas em
                        on em.empresa_id = co.empresa_id
                    inner join usuarios_empresas ue
                        on ue.empresa_id = em.empresa_id
                        and ue.usuario_id = ?
                    where pr.pago_fcreacion > concat(to_char(now() -interval '7 month','YYYY-mm'),'-01')::timestamp
                    group by pr.pago_fcreacion
                ) tmp
                group by mes";
        $result = DB::select($sql,array($user,$user,$user,$user));
        return $result;
    }

    public function getMainDataAdmin2_eliminar($user){
        $sql = "SELECT  
                    (SELECT -- pendiente
                        sum(tx.tx_valortotalpagar)
                    FROM
                        tx_mantenimiento tx
                    where 
                        tx.tx_estado = 'P'
                        and to_char(tx.tx_fexpedicion, 'YYYY-MM') >= to_char(now(),'YYYY-MM')
                        and (1 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                          OR 2 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                        )
                    ) AS pendiente_mes,
                    (SELECT -- pagado este mes
                        sum(tx.tx_valorpagado)
                    from
                        tx_mantenimiento tx
                    where
                        tx.tx_estado = 'A'
                        and tx.tx_estado = 'E'
                        and to_char(tx.tx_ultimopago, 'YYYY-MM') = to_char(now(),'YYYY-MM')
                        and (1 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                          OR 2 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                        )
                    ) as pagado_mes,
                    (SELECT -- pendiente anio
                        sum(tx.tx_valortotalpagar)
                    FROM
                        tx_mantenimiento tx
                    where 
                        tx.tx_estado = 'P'
                        and to_char(tx.tx_fexpedicion, 'YYYY') >= to_char(now(),'YYYY')
                        and (1 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                          OR 2 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                        )
                    ) AS pendiente_anio,
                    (SELECT -- pagado este anio
                        sum(tx.tx_valorpagado)
                    from
                        tx_mantenimiento tx
                    where
                        tx.tx_estado = 'A'
                        and tx.tx_estado = 'E'
                        and to_char(tx.tx_fexpedicion, 'YYYY') = to_char(now(),'YYYY')
                        and (1 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                          OR 2 = (select perfil_id  from perfiles_usuarios pu where pu.usuario_id = ?)
                        )
                    ) as pagado_anio,
                    (select 
                        coalesce(sum(fee)/100,0) as fee
                    from
                        privado.pagos_realizados_clover
                    where
                        to_char(pago_fcreacion,'YYYY-MM') = to_char(now(),'YYYY-MM')
                    ) as fee_mensual,
                    (select 
                        coalesce(sum(fee)/100,0) as fee
                    from
                        privado.pagos_realizados_clover
                    where
                        to_char(pago_fcreacion,'YYYY') = to_char(now(),'YYYY')
                    ) as fee_anual
                ";
        $result = DB::select($sql,array($user,$user,$user,$user,$user,$user,$user,$user));
        return $result;
    }




}
