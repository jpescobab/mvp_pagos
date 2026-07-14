import { Button } from '@/components/ui/button';
import type { TransicionWorkflow } from '@/types/pago-proveedores';

type TransicionesSidebarCardProps = {
    transicionesVisibles: TransicionWorkflow[];
    procesando: boolean;
    errorTransicion: string | null;
    ejecutar: (
        transicion: TransicionWorkflow,
        comentarioTexto?: string,
    ) => void;
    setTransicionConComentario: (transicion: TransicionWorkflow | null) => void;
};

export function TransicionesSidebarCard({
    transicionesVisibles,
    procesando,
    errorTransicion,
    ejecutar,
    setTransicionConComentario,
}: TransicionesSidebarCardProps) {
    return (
        <section className="space-y-3 rounded-xl border p-4">
            <h2 className="text-base font-medium">Transiciones disponibles</h2>

            {errorTransicion && (
                <p className="text-sm text-destructive">{errorTransicion}</p>
            )}

            {transicionesVisibles.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No hay transiciones disponibles desde el estado actual.
                </p>
            ) : (
                <div className="flex flex-wrap gap-2">
                    {transicionesVisibles.map((transicion) => (
                        <Button
                            key={transicion.codigo}
                            variant="outline"
                            disabled={procesando}
                            onClick={() =>
                                transicion.requiere_comentario
                                    ? setTransicionConComentario(transicion)
                                    : ejecutar(transicion)
                            }
                        >
                            {transicion.nombre}
                        </Button>
                    ))}
                </div>
            )}
        </section>
    );
}
