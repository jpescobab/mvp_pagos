import { cn } from '@/lib/utils';

export function CampoSoloLectura({
    id,
    valor,
    vacio,
}: {
    id?: string;
    valor: React.ReactNode;
    vacio?: boolean;
}) {
    return (
        <div
            id={id}
            className={cn(
                'flex h-9 items-center rounded-md border bg-muted/30 px-3 text-sm',
                vacio && 'text-muted-foreground italic',
            )}
        >
            {valor}
        </div>
    );
}
