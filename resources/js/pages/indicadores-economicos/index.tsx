import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatFecha, formatNumero } from '@/lib/format';
import { formatearValorIndicador } from '@/lib/indicadores';
import indicadoresEconomicos from '@/routes/indicadores-economicos';
import type { IndicadorEconomico } from '@/types/indicadores';
import type { Paginated } from '@/types/pago-proveedores';

type PageProps = {
    indicadores: Paginated<IndicadorEconomico>;
    codigo: string | null;
};

const CODIGOS = ['UF', 'USD', 'UTM', 'UTA', 'IPC'];

export default function IndicadoresEconomicosIndex() {
    const { indicadores: pagina, codigo, auth } = usePage<PageProps>().props;
    const [importando, setImportando] = useState(false);
    const puedeImportar = auth.permissions.includes('indicadores.importar');

    function filtrarPorCodigo(valor: string) {
        router.get(
            indicadoresEconomicos.index().url,
            valor === 'todos' ? {} : { codigo: valor },
            { preserveState: true, preserveScroll: true },
        );
    }

    function importarAhora() {
        setImportando(true);

        router.post(
            indicadoresEconomicos.importarMensual().url,
            {},
            { preserveScroll: true, onFinish: () => setImportando(false) },
        );
    }

    return (
        <>
            <Head title="Indicadores Económicos" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Indicadores Económicos
                    </h1>
                    <div className="flex items-center gap-2">
                        {puedeImportar && (
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={importando}
                                onClick={importarAhora}
                            >
                                {importando ? 'Importando…' : 'Importar ahora'}
                            </Button>
                        )}
                        <Select
                            value={codigo ?? 'todos'}
                            onValueChange={filtrarPorCodigo}
                        >
                            <SelectTrigger className="w-40">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos</SelectItem>
                                {CODIGOS.map((c) => (
                                    <SelectItem key={c} value={c}>
                                        {c}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left text-muted-foreground">
                            <tr>
                                <th className="px-4 py-2 font-medium">
                                    Código
                                </th>
                                <th className="px-4 py-2 font-medium">
                                    Fecha / Periodo
                                </th>
                                <th className="px-4 py-2 font-medium">Valor</th>
                                <th className="px-4 py-2 font-medium">
                                    Fuente
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pagina.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        Sin indicadores importados todavía.
                                    </td>
                                </tr>
                            )}
                            {pagina.data.map((indicador) => (
                                <tr key={indicador.id}>
                                    <td className="px-4 py-2 font-medium">
                                        {indicador.codigo}
                                    </td>
                                    <td className="px-4 py-2 font-mono text-xs">
                                        {indicador.fecha_valor
                                            ? formatFecha(indicador.fecha_valor)
                                            : indicador.periodo}
                                    </td>
                                    <td className="px-4 py-2 font-mono tabular-nums">
                                        {formatearValorIndicador(indicador)}
                                    </td>
                                    <td className="px-4 py-2 text-muted-foreground">
                                        {indicador.fuente}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    <span>
                        Mostrando {formatNumero(pagina.meta.from ?? 0)}–
                        {formatNumero(pagina.meta.to ?? 0)} de{' '}
                        {formatNumero(pagina.meta.total)}
                    </span>
                    <div className="flex gap-2">
                        <Link
                            href={pagina.links.prev ?? '#'}
                            className={
                                pagina.links.prev
                                    ? 'underline'
                                    : 'pointer-events-none opacity-50'
                            }
                        >
                            Anterior
                        </Link>
                        <Link
                            href={pagina.links.next ?? '#'}
                            className={
                                pagina.links.next
                                    ? 'underline'
                                    : 'pointer-events-none opacity-50'
                            }
                        >
                            Siguiente
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
}

IndicadoresEconomicosIndex.layout = {
    breadcrumbs: [
        {
            title: 'Indicadores Económicos',
            href: indicadoresEconomicos.index(),
        },
    ],
};
