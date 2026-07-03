import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { PermissionsChecklist } from '@/components/seguridad/permissions-checklist';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import roles from '@/routes/roles';
import type { GrupoPermisos, RolEditable } from '@/types/seguridad';

type PageProps = {
    role: RolEditable;
    permissionGroups: GrupoPermisos[];
};

export default function RolesEditar({ role, permissionGroups }: PageProps) {
    const [name, setName] = useState(role.name);
    const [permisosSeleccionados, setPermisosSeleccionados] = useState<
        number[]
    >(role.permission_ids);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function alternarPermiso(permisoId: number, marcado: boolean) {
        setPermisosSeleccionados((actuales) =>
            marcado
                ? [...actuales, permisoId]
                : actuales.filter((id) => id !== permisoId),
        );
    }

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.patch(
            roles.update(role.id).url,
            { name, permissions: permisosSeleccionados },
            {
                onError: (errores) =>
                    setErrors(errores as Record<string, string>),
                onFinish: () => setProcesando(false),
            },
        );
    }

    return (
        <>
            <Head title={`Editar rol — ${role.name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Editar rol
                </h1>

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
                        <Label>Permisos</Label>
                        <PermissionsChecklist
                            groups={permissionGroups}
                            selected={permisosSeleccionados}
                            onToggle={alternarPermiso}
                        />
                        {errors.permissions && (
                            <p className="text-sm text-destructive">
                                {errors.permissions}
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
                        onClick={() => router.get(roles.index().url)}
                    >
                        Cancelar
                    </Button>
                </div>
            </div>
        </>
    );
}

RolesEditar.layout = {
    breadcrumbs: [
        { title: 'Roles y permisos', href: roles.index() },
        { title: 'Editar', href: '#' },
    ],
};
