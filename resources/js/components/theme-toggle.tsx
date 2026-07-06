import { usePage } from '@inertiajs/react';
import { Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/hooks/use-appearance';

export function ThemeToggle() {
    const { appearance: appearanceCompartida } = usePage().props;
    const { resolvedAppearance, updateAppearance } =
        useAppearance(appearanceCompartida);
    const esOscuro = resolvedAppearance === 'dark';

    return (
        <Button
            variant="outline"
            size="icon"
            aria-label={
                esOscuro ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'
            }
            onClick={() => updateAppearance(esOscuro ? 'light' : 'dark')}
        >
            {esOscuro ? (
                <Sun className="size-4" />
            ) : (
                <Moon className="size-4" />
            )}
        </Button>
    );
}
