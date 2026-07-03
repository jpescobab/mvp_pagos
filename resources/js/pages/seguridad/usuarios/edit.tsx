import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import usuarios from '@/routes/usuarios';
import type { CatalogosUsuarios } from '@/types/seguridad';

const SIN_SELECCION = '__sin_seleccion__';

type UsuarioEditable = {
    id: number;
    name: string;
    email: string;
    rut: string | null;
    cargo: string | null;
    unidad: string | null;
    cfinanciero_id: number | null;
    ccosto_id: number | null;
    role_ids: number[];
};

type PageProps = {
    usuario: UsuarioEditable;
    catalogs: CatalogosUsuarios;
    permissions: { can_assign_roles: boolean };
};

export default function UsuariosEditar({
    usuario,
    catalogs,
    permissions,
}: PageProps) {
    const { flash } = usePage();
    const [name, setName] = useState(usuario.name);
    const [email, setEmail] = useState(usuario.email);
    const [rut, setRut] = useState(usuario.rut ?? '');
    const [cargo, setCargo] = useState(usuario.cargo ?? '');
    const [unidad, setUnidad] = useState(usuario.unidad ?? '');
    const [cfinancieroId, setCfinancieroId] = useState(
        usuario.cfinanciero_id !== null
            ? String(usuario.cfinanciero_id)
            : SIN_SELECCION,
    );
    const [ccostoId, setCcostoId] = useState(
        usuario.ccosto_id !== null ? String(usuario.ccosto_id) : SIN_SELECCION,
    );
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);
    const [rolesSeleccionados, setRolesSeleccionados] = useState<number[]>(
        usuario.role_ids,
    );
    const [erroresRoles, setErroresRoles] = useState<Record<string, string>>(
        {},
    );
    const [procesandoRoles, setProcesandoRoles] = useState(false);

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.patch(
            usuarios.update(usuario.id).url,
            {
                name,
                email,
                rut,
                cargo: cargo || null,
                unidad: unidad || null,
                cfinanciero_id:
                    cfinancieroId === SIN_SELECCION
                        ? null
                        : Number(cfinancieroId),
                ccosto_id:
                    ccostoId === SIN_SELECCION ? null : Number(ccostoId),
            },
            {
                onError: (errores) =>
                    setErrors(errores as Record<string, string>),
                onFinish: () => setProcesando(false),
            },
        );
    }

    function alternarRol(rolId: number, marcado: boolean) {
        setRolesSeleccionados((actuales) =>
            marcado
                ? [...actuales, rolId]
                : actuales.filter((id) => id !== rolId),
        );
    }

    function enviarRoles() {
        setProcesandoRoles(true);
        setErroresRoles({});

        router.patch(
            usuarios.roles.update(usuario.id).url,
            { roles: rolesSeleccionados },
            {
                preserveScroll: true,
                onError: (errores) =>
                    setErroresRoles(errores as Record<string, string>),
                onFinish: () => setProcesandoRoles(false),
            },
        );
    }

    return (
        <>
            <Head title={`Editar usuario — ${usuario.name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Editar usuario
                </h1>

                {flash.error && (
                    <div className="max-w-xl rounded-md border border-destructive/50 bg-destructive/10 px-4 py-2 text-sm text-destructive-foreground">
                        {flash.error}
                    </div>
                )}

                <div className="grid max-w-xl gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="name">
                            Nombre<span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="name"
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                        />
                        {errors.name && (
                            <p className="text-sm text-destructive">
                                {errors.name}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">
                            Email<span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                        />
                        {errors.email && (
                            <p className="text-sm text-destructive">
                                {errors.email}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="rut">
                            RUT<span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="rut"
                            value={rut}
                            onChange={(e) => setRut(e.target.value)}
                        />
                        {errors.rut && (
                            <p className="text-sm text-destructive">
                                {errors.rut}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="cargo">Cargo</Label>
                        <Input
                            id="cargo"
                            value={cargo}
                            onChange={(e) => setCargo(e.target.value)}
                        />
                        {errors.cargo && (
                            <p className="text-sm text-destructive">
                                {errors.cargo}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="unidad">Unidad</Label>
                        <Input
                            id="unidad"
                            value={unidad}
                            onChange={(e) => setUnidad(e.target.value)}
                        />
                        {errors.unidad && (
                            <p className="text-sm text-destructive">
                                {errors.unidad}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="cfinanciero_id">
                            Centro financiero
                        </Label>
                        <Select
                            value={cfinancieroId}
                            onValueChange={setCfinancieroId}
                        >
                            <SelectTrigger
                                id="cfinanciero_id"
                                className="w-full"
                            >
                                <SelectValue placeholder="Sin centro financiero" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={SIN_SELECCION}>
                                    Sin centro financiero
                                </SelectItem>
                                {catalogs.centros_financieros.map((cf) => (
                                    <SelectItem
                                        key={cf.id}
                                        value={String(cf.id)}
                                    >
                                        {cf.nombre}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.cfinanciero_id && (
                            <p className="text-sm text-destructive">
                                {errors.cfinanciero_id}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="ccosto_id">Centro de costo</Label>
                        <Select value={ccostoId} onValueChange={setCcostoId}>
                            <SelectTrigger id="ccosto_id" className="w-full">
                                <SelectValue placeholder="Sin centro de costo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={SIN_SELECCION}>
                                    Sin centro de costo
                                </SelectItem>
                                {catalogs.centros_costos.map((cc) => (
                                    <SelectItem
                                        key={cc.id}
                                        value={String(cc.id)}
                                    >
                                        {cc.nombre}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.ccosto_id && (
                            <p className="text-sm text-destructive">
                                {errors.ccosto_id}
                            </p>
                        )}
                    </div>
                </div>

                <div className="flex gap-2">
                    <Button disabled={procesando} onClick={enviar}>
                        Guardar cambios
                    </Button>
                    <Button
                        variant="outline"
                        disabled={procesando}
                        onClick={() => router.get(usuarios.index().url)}
                    >
                        Cancelar
                    </Button>
                </div>

                {permissions.can_assign_roles && (
                    <div className="grid max-w-xl gap-2 border-t pt-6">
                        <Label>Roles</Label>
                        <div className="flex flex-col gap-2 rounded-md border p-3">
                            {catalogs.roles.map((rol) => (
                                <div
                                    key={rol.id}
                                    className="flex items-center gap-2"
                                >
                                    <Checkbox
                                        id={`rol-${rol.id}`}
                                        checked={rolesSeleccionados.includes(
                                            rol.id,
                                        )}
                                        onCheckedChange={(checked) =>
                                            alternarRol(
                                                rol.id,
                                                checked === true,
                                            )
                                        }
                                    />
                                    <Label
                                        htmlFor={`rol-${rol.id}`}
                                        className="font-normal"
                                    >
                                        {rol.name}
                                    </Label>
                                </div>
                            ))}
                        </div>
                        {erroresRoles.roles && (
                            <p className="text-sm text-destructive">
                                {erroresRoles.roles}
                            </p>
                        )}
                        <div>
                            <Button
                                disabled={procesandoRoles}
                                onClick={enviarRoles}
                            >
                                Guardar roles
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

UsuariosEditar.layout = {
    breadcrumbs: [
        { title: 'Usuarios', href: usuarios.index() },
        { title: 'Editar', href: '#' },
    ],
};
