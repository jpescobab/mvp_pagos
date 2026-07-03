import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { UserFilters } from '@/components/seguridad/user-filters';
import { UsersTable } from '@/components/seguridad/users-table';
import {
    navegarPaginacion,
    Pagination,
} from '@/components/shared/pagination';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import usuarios from '@/routes/usuarios';
import type { Paginated } from '@/types/pago-proveedores';
import type {
    CatalogosUsuarios,
    FiltrosUsuarios,
    PermisosUsuarios,
    UsuarioListado,
} from '@/types/seguridad';

type PageProps = {
    users: Paginated<UsuarioListado>;
    filters: FiltrosUsuarios;
    catalogs: CatalogosUsuarios;
    permissions: PermisosUsuarios;
};

const OPCIONES_ORDEN: Array<{ value: string; label: string }> = [
    { value: 'recomendado', label: 'Recomendado' },
    { value: 'name:asc', label: 'Nombre (A-Z)' },
    { value: 'name:desc', label: 'Nombre (Z-A)' },
    { value: 'email:asc', label: 'Email (A-Z)' },
    { value: 'email:desc', label: 'Email (Z-A)' },
    { value: 'active:desc', label: 'Activos primero' },
    { value: 'active:asc', label: 'Inactivos primero' },
    { value: 'last_login_at:desc', label: 'Último acceso (reciente)' },
    { value: 'last_login_at:asc', label: 'Último acceso (antiguo)' },
    { value: 'created_at:desc', label: 'Fecha de creación (reciente)' },
    { value: 'created_at:asc', label: 'Fecha de creación (antigua)' },
];

export default function UsuariosIndex() {
    const page = usePage<PageProps>();
    const {
        users: pagina,
        filters,
        catalogs,
        permissions,
    } = page.props;
    const { flash } = page;

    const [search, setSearch] = useState(filters.search ?? '');
    const [cargando, setCargando] = useState(false);
    const esPrimeraCarga = useRef(true);
    const mostrarPassword = flash.passwordTemporal !== undefined;

    useEffect(() => {
        if (esPrimeraCarga.current) {
            esPrimeraCarga.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            if (search === (filters.search ?? '')) {
                return;
            }

            navegar({ search: search === '' ? null : search });
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    function navegar(partial: Record<string, string | number | null>) {
        setCargando(true);

        router.get(
            usuarios.index().url,
            {
                search: filters.search,
                estado: filters.estado,
                rol_id: filters.rol_id,
                jurisdiccion_id: filters.jurisdiccion_id,
                centro_financiero_id: filters.centro_financiero_id,
                centro_costo_id: filters.centro_costo_id,
                per_page: filters.per_page,
                sort: filters.sort,
                direction: filters.direction,
                ...partial,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setCargando(false),
            },
        );
    }

    function limpiarFiltros() {
        setSearch('');
        setCargando(true);
        router.get(
            usuarios.index().url,
            {},
            { preserveState: true, preserveScroll: true, onFinish: () => setCargando(false) },
        );
    }

    const ordenActual =
        filters.sort !== null
            ? `${filters.sort}:${filters.direction}`
            : 'recomendado';

    function cambiarOrden(value: string) {
        if (value === 'recomendado') {
            navegar({ sort: null, direction: null });

            return;
        }

        const [sort, direction] = value.split(':');
        navegar({ sort, direction });
    }

    const hayFiltrosActivos =
        filters.search !== null ||
        filters.estado !== null ||
        filters.rol_id !== null ||
        filters.jurisdiccion_id !== null ||
        filters.centro_financiero_id !== null ||
        filters.centro_costo_id !== null;

    return (
        <>
            <Head title="Usuarios" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            Usuarios
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Administración de usuarios institucionales
                        </p>
                    </div>
                    {permissions.can_create_user && (
                        <Button asChild>
                            <Link href="/usuarios/create">Nuevo usuario</Link>
                        </Button>
                    )}
                </div>

                {flash.error && (
                    <div className="rounded-md border border-destructive/50 bg-destructive/10 px-4 py-2 text-sm text-destructive-foreground">
                        {flash.error}
                    </div>
                )}

                <div className="flex flex-wrap items-end justify-between gap-3">
                    <UserFilters
                        filters={filters}
                        catalogs={catalogs}
                        search={search}
                        onSearchChange={setSearch}
                        onFilterChange={navegar}
                        onClear={limpiarFiltros}
                        hayFiltrosActivos={hayFiltrosActivos}
                    />

                    <div className="flex items-center gap-2">
                        <span className="text-sm text-muted-foreground">
                            Ordenar por
                        </span>
                        <Select value={ordenActual} onValueChange={cambiarOrden}>
                            <SelectTrigger className="w-56">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {OPCIONES_ORDEN.map((opcion) => (
                                    <SelectItem
                                        key={opcion.value}
                                        value={opcion.value}
                                    >
                                        {opcion.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {cargando && (
                    <div className="flex flex-col gap-2">
                        {Array.from({ length: 5 }).map((_, i) => (
                            <Skeleton key={i} className="h-12 w-full" />
                        ))}
                    </div>
                )}

                {!cargando && pagina.meta.total === 0 && !hayFiltrosActivos && (
                    <div className="rounded-xl border px-4 py-10 text-center text-muted-foreground">
                        No existen usuarios registrados.
                    </div>
                )}

                {!cargando && pagina.meta.total === 0 && hayFiltrosActivos && (
                    <div className="flex flex-col items-center gap-3 rounded-xl border px-4 py-10 text-center text-muted-foreground">
                        <p>
                            No se encontraron usuarios con los filtros
                            aplicados.
                        </p>
                        <Button variant="outline" onClick={limpiarFiltros}>
                            Limpiar filtros
                        </Button>
                    </div>
                )}

                {!cargando && pagina.meta.total > 0 && (
                    <>
                        <UsersTable
                            users={pagina.data}
                            permissions={permissions}
                        />

                        <Pagination
                            pagina={pagina}
                            perPage={filters.per_page}
                            onNavigate={navegarPaginacion}
                            onPerPageChange={(perPage) =>
                                navegar({ per_page: perPage })
                            }
                        />
                    </>
                )}
            </div>

            <Dialog
                open={mostrarPassword}
                onOpenChange={(open) => {
                    if (!open) {
                        router.flash(() => ({}));
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Contraseña temporal generada</DialogTitle>
                        <DialogDescription>
                            {flash.usuarioNombre &&
                                `Para ${flash.usuarioNombre}. `}
                            Cópiala ahora: no volverá a mostrarse. El usuario
                            deberá cambiarla en su próximo ingreso.
                        </DialogDescription>
                    </DialogHeader>
                    <p className="rounded-md border bg-muted px-4 py-3 text-center font-mono text-lg tracking-wide">
                        {flash.passwordTemporal}
                    </p>
                </DialogContent>
            </Dialog>
        </>
    );
}

UsuariosIndex.layout = {
    breadcrumbs: [
        {
            title: 'Usuarios',
            href: usuarios.index(),
        },
    ],
};
