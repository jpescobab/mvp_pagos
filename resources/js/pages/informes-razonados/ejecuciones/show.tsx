import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Monto } from '@/components/ui/monto';
import { formatFecha, formatFechaHora } from '@/lib/format';
import ejecuciones from '@/routes/informes-razonados/ejecuciones';
import excepciones from '@/routes/informes-razonados/excepciones';
import narrativas from '@/routes/informes-razonados/narrativas';
import secciones from '@/routes/informes-razonados/secciones';
import type {
    EjecucionInformeRazonado,
    ExcepcionInformeRazonado,
    NarrativaInformeRazonado,
    SeccionInformeRazonado,
} from '@/types/informes-razonados';
import type { TransicionWorkflow } from '@/types/pago-proveedores';

type PageProps = {
    ejecucion: EjecucionInformeRazonado;
};

const SEVERIDADES = ['info', 'advertencia', 'critico'] as const;

const CLASES_SEVERIDAD: Record<string, string> = {
    info: 'bg-muted text-muted-foreground',
    advertencia: 'bg-amber-500/15 text-amber-700 dark:text-amber-400',
    critico: 'bg-destructive/15 text-destructive',
};

export default function EjecucionInformeRazonadoShow() {
    const { ejecucion, auth } = usePage<PageProps>().props;

    const puedeElaborar = auth.permissions.includes('informes.elaborar');
    const puedeRevisar = auth.permissions.includes('informes.aprobar');
    const editable = ejecucion.editable ?? false;

    const [transicionConComentario, setTransicionConComentario] =
        useState<TransicionWorkflow | null>(null);
    const [comentario, setComentario] = useState('');
    const [procesando, setProcesando] = useState(false);
    const [errorTransicion, setErrorTransicion] = useState<string | null>(null);

    const [nuevaNarrativa, setNuevaNarrativa] = useState('');
    const [nuevaNarrativaSeccion, setNuevaNarrativaSeccion] = useState('');
    const [narrativaEnEdicion, setNarrativaEnEdicion] = useState<number | null>(
        null,
    );
    const [contenidoEdicion, setContenidoEdicion] = useState('');
    const [procesandoNarrativa, setProcesandoNarrativa] = useState(false);

    const [nuevaSeccionCodigo, setNuevaSeccionCodigo] = useState('');
    const [nuevaSeccionTitulo, setNuevaSeccionTitulo] = useState('');
    const [nuevaSeccionOrden, setNuevaSeccionOrden] = useState('0');
    const [seccionEnEdicion, setSeccionEnEdicion] = useState<number | null>(
        null,
    );
    const [tituloSeccionEdicion, setTituloSeccionEdicion] = useState('');
    const [ordenSeccionEdicion, setOrdenSeccionEdicion] = useState('0');
    const [procesandoSeccion, setProcesandoSeccion] = useState(false);

    const seccionesOrdenadas = [...(ejecucion.secciones ?? [])].sort(
        (a, b) => a.orden - b.orden,
    );

    function agregarNarrativa() {
        if (nuevaNarrativa.trim() === '') {
            return;
        }

        setProcesandoNarrativa(true);
        router.post(
            ejecuciones.narrativas.store(ejecucion.id).url,
            {
                contenido: nuevaNarrativa,
                seccion_informe_razonado_id: nuevaNarrativaSeccion || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setNuevaNarrativa('');
                    setNuevaNarrativaSeccion('');
                },
                onFinish: () => setProcesandoNarrativa(false),
            },
        );
    }

    function agregarSeccion() {
        if (
            nuevaSeccionCodigo.trim() === '' ||
            nuevaSeccionTitulo.trim() === ''
        ) {
            return;
        }

        setProcesandoSeccion(true);
        router.post(
            ejecuciones.secciones.store(ejecucion.id).url,
            {
                codigo: nuevaSeccionCodigo,
                titulo: nuevaSeccionTitulo,
                orden: Number(nuevaSeccionOrden) || 0,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setNuevaSeccionCodigo('');
                    setNuevaSeccionTitulo('');
                    setNuevaSeccionOrden('0');
                },
                onFinish: () => setProcesandoSeccion(false),
            },
        );
    }

    function iniciarEdicionSeccion(seccion: SeccionInformeRazonado) {
        setSeccionEnEdicion(seccion.id);
        setTituloSeccionEdicion(seccion.titulo);
        setOrdenSeccionEdicion(String(seccion.orden));
    }

    function guardarEdicionSeccion(id: number) {
        if (tituloSeccionEdicion.trim() === '') {
            return;
        }

        setProcesandoSeccion(true);
        router.patch(
            secciones.update(id).url,
            {
                titulo: tituloSeccionEdicion,
                orden: Number(ordenSeccionEdicion) || 0,
            },
            {
                preserveScroll: true,
                onSuccess: () => setSeccionEnEdicion(null),
                onFinish: () => setProcesandoSeccion(false),
            },
        );
    }

    function eliminarSeccion(id: number) {
        if (
            !window.confirm(
                'Eliminar esta sección también eliminará las narrativas, ' +
                    'métricas y gráficos asignados a ella. ¿Continuar?',
            )
        ) {
            return;
        }

        setProcesandoSeccion(true);
        router.delete(secciones.destroy(id).url, {
            preserveScroll: true,
            onFinish: () => setProcesandoSeccion(false),
        });
    }

    const [nuevaExcepcionCodigo, setNuevaExcepcionCodigo] = useState('');
    const [nuevaExcepcionDescripcion, setNuevaExcepcionDescripcion] =
        useState('');
    const [nuevaExcepcionSeveridad, setNuevaExcepcionSeveridad] =
        useState('info');
    const [excepcionEnEdicion, setExcepcionEnEdicion] = useState<number | null>(
        null,
    );
    const [descripcionExcepcionEdicion, setDescripcionExcepcionEdicion] =
        useState('');
    const [severidadExcepcionEdicion, setSeveridadExcepcionEdicion] =
        useState('info');
    const [procesandoExcepcion, setProcesandoExcepcion] = useState(false);

    function agregarExcepcion() {
        if (
            nuevaExcepcionCodigo.trim() === '' ||
            nuevaExcepcionDescripcion.trim() === ''
        ) {
            return;
        }

        setProcesandoExcepcion(true);
        router.post(
            ejecuciones.excepciones.store(ejecucion.id).url,
            {
                codigo: nuevaExcepcionCodigo,
                descripcion: nuevaExcepcionDescripcion,
                severidad: nuevaExcepcionSeveridad,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setNuevaExcepcionCodigo('');
                    setNuevaExcepcionDescripcion('');
                    setNuevaExcepcionSeveridad('info');
                },
                onFinish: () => setProcesandoExcepcion(false),
            },
        );
    }

    function iniciarEdicionExcepcion(excepcion: ExcepcionInformeRazonado) {
        setExcepcionEnEdicion(excepcion.id);
        setDescripcionExcepcionEdicion(excepcion.descripcion);
        setSeveridadExcepcionEdicion(excepcion.severidad);
    }

    function guardarEdicionExcepcion(id: number) {
        if (descripcionExcepcionEdicion.trim() === '') {
            return;
        }

        setProcesandoExcepcion(true);
        router.patch(
            excepciones.update(id).url,
            {
                descripcion: descripcionExcepcionEdicion,
                severidad: severidadExcepcionEdicion,
            },
            {
                preserveScroll: true,
                onSuccess: () => setExcepcionEnEdicion(null),
                onFinish: () => setProcesandoExcepcion(false),
            },
        );
    }

    function eliminarExcepcion(id: number) {
        if (!window.confirm('¿Eliminar esta excepción?')) {
            return;
        }

        setProcesandoExcepcion(true);
        router.delete(excepciones.destroy(id).url, {
            preserveScroll: true,
            onFinish: () => setProcesandoExcepcion(false),
        });
    }

    function iniciarEdicion(narrativa: NarrativaInformeRazonado) {
        setNarrativaEnEdicion(narrativa.id);
        setContenidoEdicion(narrativa.contenido);
    }

    function guardarEdicion(id: number) {
        if (contenidoEdicion.trim() === '') {
            return;
        }

        setProcesandoNarrativa(true);
        router.patch(
            narrativas.update(id).url,
            { contenido: contenidoEdicion },
            {
                preserveScroll: true,
                onSuccess: () => setNarrativaEnEdicion(null),
                onFinish: () => setProcesandoNarrativa(false),
            },
        );
    }

    function eliminarNarrativa(id: number) {
        if (!window.confirm('¿Eliminar esta narrativa?')) {
            return;
        }

        setProcesandoNarrativa(true);
        router.delete(narrativas.destroy(id).url, {
            preserveScroll: true,
            onFinish: () => setProcesandoNarrativa(false),
        });
    }

    function marcarRevisada(id: number) {
        setProcesandoNarrativa(true);
        router.post(
            narrativas.revisar(id).url,
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcesandoNarrativa(false),
            },
        );
    }

    function ejecutar(transicion: TransicionWorkflow, comentarioTexto = '') {
        setProcesando(true);
        setErrorTransicion(null);

        router.post(
            ejecuciones.transiciones.store(ejecucion.id).url,
            { codigo: transicion.codigo, comentario: comentarioTexto },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setTransicionConComentario(null);
                    setComentario('');
                },
                onError: (errors) =>
                    setErrorTransicion(
                        (errors as Record<string, string>).transicion ?? null,
                    ),
                onFinish: () => setProcesando(false),
            },
        );
    }

    const historial = [
        ...(ejecucion.proceso?.historial_transiciones ?? []),
    ].reverse();

    const todasLasNarrativas = ejecucion.narrativas ?? [];
    const gruposNarrativas = [
        ...seccionesOrdenadas.map((seccion) => ({
            key: `seccion-${seccion.id}`,
            titulo: seccion.titulo,
            items: todasLasNarrativas.filter(
                (n) => n.seccion_informe_razonado_id === seccion.id,
            ),
        })),
        {
            key: 'sin-seccion',
            titulo: 'Sin sección',
            items: todasLasNarrativas.filter(
                (n) => n.seccion_informe_razonado_id == null,
            ),
        },
    ].filter((grupo) => grupo.items.length > 0);

    function renderNarrativa(narrativa: NarrativaInformeRazonado) {
        return (
            <li key={narrativa.id} className="space-y-2 py-3">
                {narrativaEnEdicion === narrativa.id ? (
                    <div className="space-y-2">
                        <textarea
                            className="min-h-24 w-full rounded-md border bg-background p-2 text-sm"
                            value={contenidoEdicion}
                            onChange={(e) => setContenidoEdicion(e.target.value)}
                        />
                        <div className="flex gap-2">
                            <Button
                                size="sm"
                                disabled={
                                    procesandoNarrativa ||
                                    contenidoEdicion.trim() === ''
                                }
                                onClick={() => guardarEdicion(narrativa.id)}
                            >
                                Guardar
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                disabled={procesandoNarrativa}
                                onClick={() => setNarrativaEnEdicion(null)}
                            >
                                Cancelar
                            </Button>
                        </div>
                    </div>
                ) : (
                    <>
                        <p className="whitespace-pre-wrap">
                            {narrativa.contenido}
                        </p>
                        <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                            {narrativa.generado_por_ia && (
                                <span className="rounded bg-muted px-1.5 py-0.5">
                                    IA
                                </span>
                            )}
                            {narrativa.revisado_en ? (
                                <span>
                                    Revisada
                                    {narrativa.revisado_por
                                        ? ` por ${narrativa.revisado_por}`
                                        : ''}{' '}
                                    · {formatFechaHora(narrativa.revisado_en)}
                                </span>
                            ) : (
                                <span>Sin revisar</span>
                            )}
                        </div>
                        {(editable && puedeElaborar) || puedeRevisar ? (
                            <div className="flex flex-wrap gap-2">
                                {editable && puedeElaborar && (
                                    <>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            disabled={procesandoNarrativa}
                                            onClick={() =>
                                                iniciarEdicion(narrativa)
                                            }
                                        >
                                            Editar
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            disabled={procesandoNarrativa}
                                            onClick={() =>
                                                eliminarNarrativa(narrativa.id)
                                            }
                                        >
                                            Eliminar
                                        </Button>
                                    </>
                                )}
                                {puedeRevisar && !narrativa.revisado_en && (
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        disabled={procesandoNarrativa}
                                        onClick={() =>
                                            marcarRevisada(narrativa.id)
                                        }
                                    >
                                        Marcar revisada
                                    </Button>
                                )}
                            </div>
                        ) : null}
                    </>
                )}
            </li>
        );
    }

    return (
        <>
            <Head title={`Ejecución — ${ejecucion.definicion.nombre}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight">
                        {ejecucion.definicion.nombre}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Corte del período {ejecucion.corte.periodo_codigo} ·
                        generado por {ejecucion.generado_por ?? 'Sistema'} el{' '}
                        {formatFecha(ejecucion.generado_en)}
                    </p>
                </div>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Estado: {ejecucion.proceso?.estado_actual.nombre}
                    </h2>

                    {errorTransicion && (
                        <p className="text-sm text-destructive">
                            {errorTransicion}
                        </p>
                    )}

                    {(ejecucion.proceso?.transiciones_disponibles ?? [])
                        .length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No hay transiciones disponibles desde el estado
                            actual.
                        </p>
                    ) : (
                        <div className="flex flex-wrap gap-2">
                            {ejecucion.proceso?.transiciones_disponibles.map(
                                (transicion) => (
                                    <Button
                                        key={transicion.codigo}
                                        variant="outline"
                                        disabled={procesando}
                                        onClick={() =>
                                            transicion.requiere_comentario
                                                ? setTransicionConComentario(
                                                      transicion,
                                                  )
                                                : ejecutar(transicion)
                                        }
                                    >
                                        {transicion.nombre}
                                    </Button>
                                ),
                            )}
                        </div>
                    )}
                </section>

                {editable && puedeElaborar && (
                    <section className="space-y-3 rounded-xl border p-4">
                        <h2 className="text-base font-medium">Secciones</h2>

                        {seccionesOrdenadas.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin secciones todavía.
                            </p>
                        ) : (
                            <ul className="divide-y text-sm">
                                {seccionesOrdenadas.map((seccion) => (
                                    <li
                                        key={seccion.id}
                                        className="space-y-2 py-2"
                                    >
                                        {seccionEnEdicion === seccion.id ? (
                                            <div className="flex flex-wrap items-center gap-2">
                                                <input
                                                    aria-label="Título"
                                                    className="flex-1 rounded-md border bg-background p-2 text-sm"
                                                    value={tituloSeccionEdicion}
                                                    onChange={(e) =>
                                                        setTituloSeccionEdicion(
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <input
                                                    aria-label="Orden"
                                                    type="number"
                                                    className="w-20 rounded-md border bg-background p-2 text-sm"
                                                    value={ordenSeccionEdicion}
                                                    onChange={(e) =>
                                                        setOrdenSeccionEdicion(
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <Button
                                                    size="sm"
                                                    disabled={
                                                        procesandoSeccion ||
                                                        tituloSeccionEdicion.trim() ===
                                                            ''
                                                    }
                                                    onClick={() =>
                                                        guardarEdicionSeccion(
                                                            seccion.id,
                                                        )
                                                    }
                                                >
                                                    Guardar
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    disabled={procesandoSeccion}
                                                    onClick={() =>
                                                        setSeccionEnEdicion(null)
                                                    }
                                                >
                                                    Cancelar
                                                </Button>
                                            </div>
                                        ) : (
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <span>
                                                    <span className="text-muted-foreground">
                                                        #{seccion.orden}
                                                    </span>{' '}
                                                    {seccion.titulo}{' '}
                                                    <span className="font-mono text-xs text-muted-foreground">
                                                        {seccion.codigo}
                                                    </span>
                                                </span>
                                                <div className="flex gap-2">
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        disabled={
                                                            procesandoSeccion
                                                        }
                                                        onClick={() =>
                                                            iniciarEdicionSeccion(
                                                                seccion,
                                                            )
                                                        }
                                                    >
                                                        Editar
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        disabled={
                                                            procesandoSeccion
                                                        }
                                                        onClick={() =>
                                                            eliminarSeccion(
                                                                seccion.id,
                                                            )
                                                        }
                                                    >
                                                        Eliminar
                                                    </Button>
                                                </div>
                                            </div>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}

                        <div className="flex flex-wrap items-end gap-2 border-t pt-3">
                            <div className="grid gap-1">
                                <Label htmlFor="nueva-seccion-codigo">
                                    Código
                                </Label>
                                <input
                                    id="nueva-seccion-codigo"
                                    className="w-32 rounded-md border bg-background p-2 text-sm"
                                    value={nuevaSeccionCodigo}
                                    onChange={(e) =>
                                        setNuevaSeccionCodigo(e.target.value)
                                    }
                                />
                            </div>
                            <div className="grid flex-1 gap-1">
                                <Label htmlFor="nueva-seccion-titulo">
                                    Título
                                </Label>
                                <input
                                    id="nueva-seccion-titulo"
                                    className="w-full rounded-md border bg-background p-2 text-sm"
                                    value={nuevaSeccionTitulo}
                                    onChange={(e) =>
                                        setNuevaSeccionTitulo(e.target.value)
                                    }
                                />
                            </div>
                            <div className="grid gap-1">
                                <Label htmlFor="nueva-seccion-orden">
                                    Orden
                                </Label>
                                <input
                                    id="nueva-seccion-orden"
                                    type="number"
                                    className="w-20 rounded-md border bg-background p-2 text-sm"
                                    value={nuevaSeccionOrden}
                                    onChange={(e) =>
                                        setNuevaSeccionOrden(e.target.value)
                                    }
                                />
                            </div>
                            <Button
                                size="sm"
                                disabled={
                                    procesandoSeccion ||
                                    nuevaSeccionCodigo.trim() === '' ||
                                    nuevaSeccionTitulo.trim() === ''
                                }
                                onClick={agregarSeccion}
                            >
                                Agregar sección
                            </Button>
                        </div>
                    </section>
                )}

                <div className="grid gap-4 md:grid-cols-2">
                    <section className="space-y-2 rounded-xl border p-4">
                        <h2 className="text-base font-medium">Métricas</h2>
                        {(ejecucion.metricas ?? []).length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin métricas todavía.
                            </p>
                        ) : (
                            <ul className="divide-y text-sm">
                                {ejecucion.metricas?.map((metrica) => (
                                    <li
                                        key={metrica.id}
                                        className="flex items-center justify-between py-2"
                                    >
                                        <span>{metrica.etiqueta}</span>
                                        <span className="text-muted-foreground">
                                            <Monto
                                                valor={metrica.valor}
                                                variante="numero"
                                            />{' '}
                                            {metrica.unidad}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="space-y-2 rounded-xl border p-4">
                        <h2 className="text-base font-medium">Gráficos</h2>
                        {(ejecucion.graficos ?? []).length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin gráficos todavía.
                            </p>
                        ) : (
                            <ul className="divide-y text-sm">
                                {ejecucion.graficos?.map((grafico) => (
                                    <li key={grafico.id} className="py-2">
                                        {grafico.titulo} ({grafico.tipo})
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="space-y-3 rounded-xl border p-4">
                        <h2 className="text-base font-medium">Narrativas</h2>
                        {todasLasNarrativas.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin narrativas todavía.
                            </p>
                        ) : (
                            <div className="space-y-4">
                                {gruposNarrativas.map((grupo) => (
                                    <div key={grupo.key} className="space-y-1">
                                        {seccionesOrdenadas.length > 0 && (
                                            <h3 className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                                {grupo.titulo}
                                            </h3>
                                        )}
                                        <ul className="divide-y text-sm">
                                            {grupo.items.map(renderNarrativa)}
                                        </ul>
                                    </div>
                                ))}
                            </div>
                        )}

                        {editable && puedeElaborar && (
                            <div className="space-y-2 border-t pt-3">
                                <Label htmlFor="nueva-narrativa">
                                    Agregar narrativa
                                </Label>
                                <textarea
                                    id="nueva-narrativa"
                                    className="min-h-24 w-full rounded-md border bg-background p-2 text-sm"
                                    value={nuevaNarrativa}
                                    onChange={(e) =>
                                        setNuevaNarrativa(e.target.value)
                                    }
                                />
                                {seccionesOrdenadas.length > 0 && (
                                    <select
                                        aria-label="Sección"
                                        className="w-full rounded-md border bg-background p-2 text-sm"
                                        value={nuevaNarrativaSeccion}
                                        onChange={(e) =>
                                            setNuevaNarrativaSeccion(
                                                e.target.value,
                                            )
                                        }
                                    >
                                        <option value="">Sin sección</option>
                                        {seccionesOrdenadas.map((seccion) => (
                                            <option
                                                key={seccion.id}
                                                value={String(seccion.id)}
                                            >
                                                {seccion.titulo}
                                            </option>
                                        ))}
                                    </select>
                                )}
                                <Button
                                    size="sm"
                                    disabled={
                                        procesandoNarrativa ||
                                        nuevaNarrativa.trim() === ''
                                    }
                                    onClick={agregarNarrativa}
                                >
                                    Agregar
                                </Button>
                            </div>
                        )}
                    </section>

                    <section className="space-y-3 rounded-xl border p-4">
                        <h2 className="text-base font-medium">Excepciones</h2>
                        {(ejecucion.excepciones ?? []).length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin excepciones todavía.
                            </p>
                        ) : (
                            <ul className="divide-y text-sm">
                                {ejecucion.excepciones?.map((excepcion) => (
                                    <li
                                        key={excepcion.id}
                                        className="space-y-2 py-2"
                                    >
                                        {excepcionEnEdicion === excepcion.id ? (
                                            <div className="space-y-2">
                                                <textarea
                                                    aria-label="Descripción"
                                                    className="min-h-16 w-full rounded-md border bg-background p-2 text-sm"
                                                    value={
                                                        descripcionExcepcionEdicion
                                                    }
                                                    onChange={(e) =>
                                                        setDescripcionExcepcionEdicion(
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <select
                                                    aria-label="Severidad"
                                                    className="w-full rounded-md border bg-background p-2 text-sm"
                                                    value={
                                                        severidadExcepcionEdicion
                                                    }
                                                    onChange={(e) =>
                                                        setSeveridadExcepcionEdicion(
                                                            e.target.value,
                                                        )
                                                    }
                                                >
                                                    {SEVERIDADES.map(
                                                        (severidad) => (
                                                            <option
                                                                key={severidad}
                                                                value={severidad}
                                                            >
                                                                {severidad}
                                                            </option>
                                                        ),
                                                    )}
                                                </select>
                                                <div className="flex gap-2">
                                                    <Button
                                                        size="sm"
                                                        disabled={
                                                            procesandoExcepcion ||
                                                            descripcionExcepcionEdicion.trim() ===
                                                                ''
                                                        }
                                                        onClick={() =>
                                                            guardarEdicionExcepcion(
                                                                excepcion.id,
                                                            )
                                                        }
                                                    >
                                                        Guardar
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        disabled={
                                                            procesandoExcepcion
                                                        }
                                                        onClick={() =>
                                                            setExcepcionEnEdicion(
                                                                null,
                                                            )
                                                        }
                                                    >
                                                        Cancelar
                                                    </Button>
                                                </div>
                                            </div>
                                        ) : (
                                            <>
                                                <div className="flex items-start gap-2">
                                                    <span
                                                        className={`rounded px-1.5 py-0.5 text-xs ${
                                                            CLASES_SEVERIDAD[
                                                                excepcion
                                                                    .severidad
                                                            ] ??
                                                            CLASES_SEVERIDAD.info
                                                        }`}
                                                    >
                                                        {excepcion.severidad}
                                                    </span>
                                                    <span className="flex-1">
                                                        {excepcion.descripcion}
                                                    </span>
                                                </div>
                                                {editable && puedeElaborar && (
                                                    <div className="flex flex-wrap gap-2">
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            disabled={
                                                                procesandoExcepcion
                                                            }
                                                            onClick={() =>
                                                                iniciarEdicionExcepcion(
                                                                    excepcion,
                                                                )
                                                            }
                                                        >
                                                            Editar
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            disabled={
                                                                procesandoExcepcion
                                                            }
                                                            onClick={() =>
                                                                eliminarExcepcion(
                                                                    excepcion.id,
                                                                )
                                                            }
                                                        >
                                                            Eliminar
                                                        </Button>
                                                    </div>
                                                )}
                                            </>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}

                        {editable && puedeElaborar && (
                            <div className="space-y-2 border-t pt-3">
                                <Label htmlFor="nueva-excepcion-codigo">
                                    Agregar excepción
                                </Label>
                                <input
                                    id="nueva-excepcion-codigo"
                                    placeholder="Código"
                                    className="w-full rounded-md border bg-background p-2 text-sm"
                                    value={nuevaExcepcionCodigo}
                                    onChange={(e) =>
                                        setNuevaExcepcionCodigo(e.target.value)
                                    }
                                />
                                <textarea
                                    aria-label="Descripción"
                                    placeholder="Descripción"
                                    className="min-h-16 w-full rounded-md border bg-background p-2 text-sm"
                                    value={nuevaExcepcionDescripcion}
                                    onChange={(e) =>
                                        setNuevaExcepcionDescripcion(
                                            e.target.value,
                                        )
                                    }
                                />
                                <select
                                    aria-label="Severidad"
                                    className="w-full rounded-md border bg-background p-2 text-sm"
                                    value={nuevaExcepcionSeveridad}
                                    onChange={(e) =>
                                        setNuevaExcepcionSeveridad(
                                            e.target.value,
                                        )
                                    }
                                >
                                    {SEVERIDADES.map((severidad) => (
                                        <option key={severidad} value={severidad}>
                                            {severidad}
                                        </option>
                                    ))}
                                </select>
                                <Button
                                    size="sm"
                                    disabled={
                                        procesandoExcepcion ||
                                        nuevaExcepcionCodigo.trim() === '' ||
                                        nuevaExcepcionDescripcion.trim() === ''
                                    }
                                    onClick={agregarExcepcion}
                                >
                                    Agregar
                                </Button>
                            </div>
                        )}
                    </section>

                    <section className="space-y-2 rounded-xl border p-4">
                        <h2 className="text-base font-medium">Snapshots</h2>
                        {(ejecucion.snapshots ?? []).length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin snapshots todavía.
                            </p>
                        ) : (
                            <ul className="divide-y text-sm">
                                {ejecucion.snapshots?.map((snapshot) => (
                                    <li
                                        key={snapshot.id}
                                        className="py-2 font-mono text-xs"
                                    >
                                        {snapshot.hash} ·{' '}
                                        {formatFechaHora(snapshot.capturado_en)}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    <section className="space-y-2 rounded-xl border p-4">
                        <h2 className="text-base font-medium">Aprobaciones</h2>
                        {(ejecucion.aprobaciones ?? []).length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Sin aprobaciones todavía.
                            </p>
                        ) : (
                            <ul className="divide-y text-sm">
                                {ejecucion.aprobaciones?.map((aprobacion) => (
                                    <li
                                        key={aprobacion.id}
                                        className="space-y-1 py-2"
                                    >
                                        <div className="flex items-center justify-between">
                                            <span className="font-medium">
                                                {aprobacion.decision}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {formatFechaHora(aprobacion.decidido_en)}
                                            </span>
                                        </div>
                                        <p className="text-muted-foreground">
                                            {aprobacion.aprobado_por ??
                                                'Sistema'}
                                            {aprobacion.comentario &&
                                                ` — ${aprobacion.comentario}`}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>

                <section className="space-y-3 rounded-xl border p-4">
                    <h2 className="text-base font-medium">
                        Historial de transiciones
                    </h2>

                    {historial.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Sin transiciones registradas todavía.
                        </p>
                    ) : (
                        <ul className="divide-y text-sm">
                            {historial.map((item, i) => (
                                <li key={i} className="space-y-1 py-3">
                                    <div className="flex items-center justify-between">
                                        <span className="font-medium">
                                            {item.transicion.nombre}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {formatFechaHora(item.created_at)}
                                        </span>
                                    </div>
                                    <p className="text-muted-foreground">
                                        {item.estado_origen.codigo} →{' '}
                                        {item.estado_destino.codigo} ·{' '}
                                        {item.user.name ?? 'Sistema'}
                                    </p>
                                    {item.comentario && (
                                        <p className="italic">
                                            “{item.comentario}”
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>

            <Dialog
                open={transicionConComentario !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setTransicionConComentario(null);
                        setComentario('');
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {transicionConComentario?.nombre}
                        </DialogTitle>
                        <DialogDescription>
                            Esta transición requiere un comentario.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="comentario">Comentario</Label>
                        <textarea
                            id="comentario"
                            className="min-h-24 rounded-md border bg-background p-2 text-sm"
                            value={comentario}
                            onChange={(e) => setComentario(e.target.value)}
                        />
                    </div>

                    <DialogFooter>
                        <Button
                            disabled={procesando || comentario === ''}
                            onClick={() =>
                                transicionConComentario &&
                                ejecutar(transicionConComentario, comentario)
                            }
                        >
                            Confirmar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
