export type ClienteMedidorSeleccionable = {
    id: number;
    numero_cliente: string;
    tipo_suministro: string;
};

export type ConsumoBasicoDocumento = {
    vinculo_id: number;
    documento_id: number;
    nombre_archivo: string | null;
};

export type ConsumoBasico = {
    id: number;
    caso_pago_proveedor_id: number;
    numero_documento: string;
    numero_medidor: string;
    fecha_inicio_lectura: string;
    fecha_fin_lectura: string;
    fecha_emision: string;
    fecha_vencimiento: string;
    consumo: string | null;
    tarifa: string | null;
    lectura_estimada: boolean;
    monto_neto: string;
    iva: string;
    monto_exento: string;
    saldo_anterior: string;
    monto_total: string;
    cliente_medidor?: {
        id: number;
        numero_cliente: string;
        tipo_suministro: string;
    };
    documento?: ConsumoBasicoDocumento | null;
};
