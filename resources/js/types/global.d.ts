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
            indicadoresTopbar: Indicador[];
            [key: string]: unknown;
        };
        flashDataType: {
            passwordTemporal?: string;
            usuarioNombre?: string;
            error?: string;
        };
    }
}
