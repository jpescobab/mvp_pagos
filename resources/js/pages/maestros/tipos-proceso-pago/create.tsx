import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import tiposProcesoPago from '@/routes/maestros/tipos-proceso-pago';

export default function TiposProcesoPagoCrear() {
    const [codigo, setCodigo] = useState('');
    const [nombre, setNombre] = useState('');
    const [activo, setActivo] = useState(true);
    const [requiereTraspasoCgu, setRequiereTraspasoCgu] = useState(true);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.post(
            tiposProcesoPago.store().url,
            {
                codigo,
                nombre,
                activo,
                requiere_traspaso_cgu: requiereTraspasoCgu,
            },
            {
                onError: (errores) =>
                    setErrors(errores as Record<string, string>),
                onFinish: () => setProcesando(false),
            },
        );
    }

    return (
        <>
            <Head title="Nuevo tipo de proceso de pago" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Nuevo tipo de proceso de pago
                </h1>

                <div className="grid max-w-xl gap-4 rounded-xl border p-4">
                    <div className="grid gap-2">
                        <Label htmlFor="codigo">
                            Código
                            <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="codigo"
                            value={codigo}
                            onChange={(e) => setCodigo(e.target.value)}
                            placeholder="Ej: CONSUMOS_BASICOS"
                        />
                        {errors.codigo && (
                            <p className="text-sm text-destructive">
                                {errors.codigo}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="nombre">
                            Nombre
                            <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="nombre"
                            value={nombre}
                            onChange={(e) => setNombre(e.target.value)}
                            placeholder="Ej: Consumos básicos"
                        />
                        {errors.nombre && (
                            <p className="text-sm text-destructive">
                                {errors.nombre}
                            </p>
                        )}
                    </div>

                    <div className="flex items-center gap-2">
                        <Switch
                            id="activo"
                            checked={activo}
                            onCheckedChange={setActivo}
                        />
                        <Label htmlFor="activo">Activo</Label>
                    </div>

                    <div className="grid gap-2">
                        <div className="flex items-center gap-2">
                            <Switch
                                id="requiere-traspaso-cgu"
                                checked={requiereTraspasoCgu}
                                onCheckedChange={setRequiereTraspasoCgu}
                            />
                            <Label htmlFor="requiere-traspaso-cgu">
                                Requiere Traspaso (CGU)
                            </Label>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Desactiva esta opción para tipos de proceso que
                            nunca generan un Traspaso (CGU), como Remesa. El
                            formulario de registro quedará oculto en el
                            detalle de esos casos.
                        </p>
                    </div>
                </div>

                <div className="flex gap-2">
                    <Button disabled={procesando} onClick={enviar}>
                        Crear tipo de proceso de pago
                    </Button>
                    <Button
                        variant="outline"
                        disabled={procesando}
                        onClick={() => router.get(tiposProcesoPago.index().url)}
                    >
                        Cancelar
                    </Button>
                </div>
            </div>
        </>
    );
}

TiposProcesoPagoCrear.layout = {
    breadcrumbs: [
        { title: 'Tipos de Proceso de Pago', href: tiposProcesoPago.index() },
        { title: 'Nuevo', href: tiposProcesoPago.create() },
    ],
};
