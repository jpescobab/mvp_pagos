import { Button } from '@/components/ui/button';

export type RespuestaSiNo = '' | 'si' | 'no';

export function BotonesSiNo({
    id,
    valor,
    onChange,
}: {
    id: string;
    valor: RespuestaSiNo;
    onChange: (valor: RespuestaSiNo) => void;
}) {
    return (
        <div id={id} className="flex gap-2">
            <Button
                type="button"
                variant={valor === 'si' ? 'default' : 'outline'}
                size="sm"
                onClick={() => onChange('si')}
            >
                Sí
            </Button>
            <Button
                type="button"
                variant={valor === 'no' ? 'default' : 'outline'}
                size="sm"
                onClick={() => onChange('no')}
            >
                No
            </Button>
        </div>
    );
}
