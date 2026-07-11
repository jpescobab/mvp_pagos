import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatFecha } from '@/lib/format';
import conectores from '@/routes/integraciones/conectores';
import type {
    ConectorAutomatizacionNavegador,
    SistemaExternoSeleccionable,
} from '@/types/integraciones';

type PageProps = {
    conectores: ConectorAutomatizacionNavegador[];
    sistemasExternos: SistemaExternoSeleccionable[];
};

export default function ConectoresIndex() {
    const { conectores: lista, sistemasExternos } = usePage<PageProps>().props;

    const [sistemaExternoId, setSistemaExternoId] = useState('');
    const [codigo, setCodigo] = useState('');
    const [nombre, setNombre] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [procesando, setProcesando] = useState(false);

    function crearConector() {
        setProcesando(true);
        setError(null);

        router.post(
            conectores.store().url,
            { sistema_externo_id: sistemaExternoId, codigo, nombre },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSistemaExternoId('');
                    setCodigo('');
                    setNombre('');
                },
                onError: (errors) =>
                    setError(
                        Object.values(errors as Record<string, string>)[0] ??
                            null,
                    ),
                onFinish: () => setProcesando(false),
            },
        );
    }

    function autorizar(conector: ConectorAutomatizacionNavegador) {
        router.post(
            conectores.autorizar(conector.id).url,
            {},
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Conectores de Automatización Playwright" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Conectores de Automatización Playwright
                </h1>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Registrar conector
                    </h2>

                    {error && (
                        <p className="text-sm text-destructive">{error}</p>
                    )}

                    <div className="flex flex-wrap items-end gap-2">
                        <Select
                            value={sistemaExternoId}
                            onValueChange={setSistemaExternoId}
                        >
                            <SelectTrigger className="w-56">
                                <SelectValue placeholder="Sistema externo" />
                            </SelectTrigger>
                            <SelectContent>
                                {sistemasExternos.map((sistema) => (
                                    <SelectItem
                                        key={sistema.id}
                                        value={String(sistema.id)}
                                    >
                                        {sistema.nombre}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <div className="space-y-1">
                            <Label htmlFor="codigo-conector">Código</Label>
                            <Input
                                id="codigo-conector"
                                value={codigo}
                                onChange={(e) => setCodigo(e.target.value)}
                            />
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="nombre-conector">Nombre</Label>
                            <Input
                                id="nombre-conector"
                                value={nombre}
                                onChange={(e) => setNombre(e.target.value)}
                            />
                        </div>
                        <Button
                            disabled={
                                procesando ||
                                sistemaExternoId === '' ||
                                codigo === '' ||
                                nombre === ''
                            }
                            onClick={crearConector}
                        >
                            Registrar
                        </Button>
                    </div>
                </section>

                {lista.length === 0 && (
                    <p className="text-sm text-muted-foreground">
                        Sin conectores registrados todavía.
                    </p>
                )}

                {lista.map((conector) => (
                    <ConectorCard
                        key={conector.id}
                        conector={conector}
                        onAutorizar={() => autorizar(conector)}
                    />
                ))}
            </div>
        </>
    );
}

function ConectorCard({
    conector,
    onAutorizar,
}: {
    conector: ConectorAutomatizacionNavegador;
    onAutorizar: () => void;
}) {
    const [nombrePerfil, setNombrePerfil] = useState('');
    const [almacenSecreto, setAlmacenSecreto] = useState('');
    const [referenciaSecreto, setReferenciaSecreto] = useState('');
    const [procesando, setProcesando] = useState(false);

    function crearPerfil() {
        setProcesando(true);

        router.post(
            conectores.perfiles.store(conector.id).url,
            {
                nombre: nombrePerfil,
                almacen_secreto: almacenSecreto,
                referencia_secreto: referenciaSecreto,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setNombrePerfil('');
                    setAlmacenSecreto('');
                    setReferenciaSecreto('');
                },
                onFinish: () => setProcesando(false),
            },
        );
    }

    return (
        <section className="space-y-3 rounded-xl border p-4">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="font-mono text-base font-medium">
                        {conector.codigo}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {conector.nombre} · {conector.sistema_externo.nombre}
                    </p>
                </div>
                {conector.esta_autorizado ? (
                    <span className="text-sm text-green-600">
                        Autorizado por {conector.autorizado_por}
                        {conector.autorizado_en &&
                            ` el ${formatFecha(conector.autorizado_en)}`}
                    </span>
                ) : (
                    <Button variant="outline" onClick={onAutorizar}>
                        Autorizar
                    </Button>
                )}
            </div>

            <div>
                <h3 className="text-sm font-medium">
                    Perfiles de autenticación
                </h3>
                {conector.perfiles.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Sin perfiles de autenticación todavía.
                    </p>
                ) : (
                    <ul className="divide-y text-sm">
                        {conector.perfiles.map((perfil) => (
                            <li key={perfil.id} className="py-2">
                                {perfil.nombre} — {perfil.almacen_secreto}:
                                {perfil.referencia_secreto}
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <div className="flex flex-wrap items-end gap-2">
                <div className="space-y-1">
                    <Label htmlFor={`nombre-perfil-${conector.id}`}>
                        Nombre del perfil
                    </Label>
                    <Input
                        id={`nombre-perfil-${conector.id}`}
                        value={nombrePerfil}
                        onChange={(e) => setNombrePerfil(e.target.value)}
                    />
                </div>
                <div className="space-y-1">
                    <Label htmlFor={`almacen-secreto-${conector.id}`}>
                        Almacén de secretos
                    </Label>
                    <Input
                        id={`almacen-secreto-${conector.id}`}
                        value={almacenSecreto}
                        onChange={(e) => setAlmacenSecreto(e.target.value)}
                        placeholder="vault"
                    />
                </div>
                <div className="space-y-1">
                    <Label htmlFor={`referencia-secreto-${conector.id}`}>
                        Referencia del secreto
                    </Label>
                    <Input
                        id={`referencia-secreto-${conector.id}`}
                        value={referenciaSecreto}
                        onChange={(e) => setReferenciaSecreto(e.target.value)}
                        placeholder="secret/conectores/sgf"
                    />
                </div>
                <Button
                    variant="outline"
                    disabled={
                        procesando ||
                        nombrePerfil === '' ||
                        almacenSecreto === '' ||
                        referenciaSecreto === ''
                    }
                    onClick={crearPerfil}
                >
                    Registrar perfil
                </Button>
            </div>
        </section>
    );
}

ConectoresIndex.layout = {
    breadcrumbs: [
        {
            title: 'Conectores Playwright',
            href: conectores.index(),
        },
    ],
};
