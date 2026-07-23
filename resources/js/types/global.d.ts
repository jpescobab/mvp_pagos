import type { Appearance } from '@/hooks/use-appearance';
import type { Indicador } from '@/lib/indicadores';
import type { Auth } from '@/types/auth';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            appearance: Appearance;
            indicadoresTopbar: Indicador[];
            notificaciones_no_leidas: number;
            [key: string]: unknown;
        };
        flashDataType: {
            passwordTemporal?: string;
            usuarioNombre?: string;
            error?: string;
            verificacionSgf?: {
                encontrada: boolean;
                payload_crudo: Record<string, unknown> | null;
            };
        };
    }
}
