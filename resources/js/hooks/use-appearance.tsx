import { useSyncExternalStore } from 'react';

export type ResolvedAppearance = 'light' | 'dark';
export type Appearance = ResolvedAppearance | 'system';

export type UseAppearanceReturn = {
    readonly appearance: Appearance;
    readonly resolvedAppearance: ResolvedAppearance;
    readonly updateAppearance: (mode: Appearance) => void;
};

const listeners = new Set<() => void>();
let currentAppearance: Appearance = 'system';

const prefersDark = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const getStoredAppearance = (): Appearance => {
    if (typeof window === 'undefined') {
        return 'system';
    }

    return (localStorage.getItem('appearance') as Appearance) || 'system';
};

const isDarkMode = (appearance: Appearance): boolean => {
    return appearance === 'dark' || (appearance === 'system' && prefersDark());
};

const applyTheme = (appearance: Appearance): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const isDark = isDarkMode(appearance);

    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
};

const subscribe = (callback: () => void) => {
    listeners.add(callback);

    return () => listeners.delete(callback);
};

// Detecta si ya se completó la hidratación sin usar setState en un efecto:
// getServerSnapshot devuelve false (igual que el servidor) y getSnapshot
// devuelve true; useSyncExternalStore re-renderiza solo una vez apenas
// detecta la diferencia tras montar.
const sinSuscripcion = () => () => {};
const useHidratado = (): boolean =>
    useSyncExternalStore(
        sinSuscripcion,
        () => true,
        () => false,
    );

const notify = (): void => listeners.forEach((listener) => listener());

const mediaQuery = (): MediaQueryList | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const handleSystemThemeChange = (): void => applyTheme(currentAppearance);

export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    if (!localStorage.getItem('appearance')) {
        localStorage.setItem('appearance', 'system');
        setCookie('appearance', 'system');
    }

    currentAppearance = getStoredAppearance();
    applyTheme(currentAppearance);

    // Set up system theme change listener
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

/**
 * `appearanceCompartida` es el valor real (cookie `appearance`) para la
 * request/render actual, ya resuelto por el servidor. Los componentes que
 * viven dentro del árbol de Inertia (páginas, layouts) deben pasarlo desde
 * `usePage().props.appearance`. Componentes fuera de ese árbol (ej. el
 * `<Toaster>` global, montado como hermano de `<App>` en `app.tsx`) no tienen
 * acceso a `usePage()` y usan el valor por defecto `'system'`.
 */
export function useAppearance(
    appearanceCompartida: Appearance = 'system',
): UseAppearanceReturn {
    // El servidor no puede evaluar `prefers-color-scheme`, así que hasta que
    // el cliente termine de hidratar tratamos 'system' como 'light' (igual
    // que el servidor) para que el primer render coincida exactamente.
    const hidratado = useHidratado();

    const appearance: Appearance = useSyncExternalStore(
        subscribe,
        () => currentAppearance,
        () => appearanceCompartida,
    );

    const resolvedAppearance: ResolvedAppearance =
        appearance === 'dark' ||
        (appearance === 'system' && hidratado && prefersDark())
            ? 'dark'
            : 'light';

    const updateAppearance = (mode: Appearance): void => {
        currentAppearance = mode;

        // Store in localStorage for client-side persistence...
        localStorage.setItem('appearance', mode);

        // Store in cookie for SSR...
        setCookie('appearance', mode);

        applyTheme(mode);
        notify();
    };

    return { appearance, resolvedAppearance, updateAppearance } as const;
}
