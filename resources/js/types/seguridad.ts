export type AuditLogEntry = {
    id: number;
    user: string | null;
    action: string;
    auditable_type: string | null;
    auditable_id: number | null;
    before: Record<string, unknown> | null;
    after: Record<string, unknown> | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
};

export type UsuarioListado = {
    id: number;
    name: string;
    email: string;
    rut: string | null;
    cargo: string | null;
    unidad: string | null;
    active: boolean;
    last_login_at: string | null;
    created_at: string | null;
    roles: string[];
    jurisdiccion: { id: number; nombre: string } | null;
    centro_financiero: { id: number; nombre: string } | null;
    centro_costo: { id: number; nombre: string } | null;
};

export type FiltrosUsuarios = {
    search: string | null;
    per_page: number;
    sort: string | null;
    direction: 'asc' | 'desc';
};

export type CatalogoOpcion = {
    id: number;
    nombre?: string;
    name?: string;
};

export type CatalogosUsuarios = {
    roles: CatalogoOpcion[];
    centros_financieros: CatalogoOpcion[];
    centros_costos: CatalogoOpcion[];
};

export type PermisosUsuarios = {
    can_create_user: boolean;
    can_view_user: boolean;
    can_edit_user: boolean;
    can_activate_user: boolean;
    can_deactivate_user: boolean;
    can_reset_password: boolean;
    can_assign_roles: boolean;
};

export type RolListado = {
    id: number;
    name: string;
    users_count: number;
    permissions_count: number;
    is_core: boolean;
};

export type FiltrosRoles = {
    search: string | null;
};

export type PermisoOpcion = {
    id: number;
    name: string;
};

export type GrupoPermisos = {
    group: string;
    permissions: PermisoOpcion[];
};

export type RolEditable = {
    id: number;
    name: string;
    permission_ids: number[];
};
