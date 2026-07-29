import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { PermissionsChecklist } from '@/components/seguridad/permissions-checklist';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import roles from '@/routes/roles';
import type { GrupoPermisos } from '@/types/seguridad';

type PageProps = {
    permissionGroups: GrupoPermisos[];
};

export default function RolesCrear({ permissionGroups }: PageProps) {
    const [name, setName] = useState('');
    const [etiqueta, setEtiqueta] = useState('');
    const [descripcion, setDescripcion] = useState('');
    const [permisosSeleccionados, setPermisosSeleccionados] = useState<
        number[]
    >([]);
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

        router.post(
            roles.store().url,
            {
                name,
                etiqueta: etiqueta || null,
                descripcion: descripcion || null,
                permissions: permisosSeleccionados,
            },
            {
                onError: (errores) =>
                    setErrors(errores as Record<string, string>),
                onFinish: () => setProcesando(false),
            },
        );
    }

    return (
        <>
            <Head title="Nuevo rol" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Nuevo rol
                </h1>

                <div className="grid max-w-xl gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="name">
                            Nombre técnico
                            <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="name"
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            placeholder="elaborador_informes"
                            className="font-mono"
                        />
                        <p className="text-xs text-muted-foreground">
                            Clave interna en snake_case; no cambia una vez en
                            uso.
                        </p>
                        {errors.name && (
                            <p className="text-sm text-destructive">
                                {errors.name}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="etiqueta">Etiqueta</Label>
                        <Input
                            id="etiqueta"
                            value={etiqueta}
                            onChange={(e) => setEtiqueta(e.target.value)}
                            placeholder="Elaborador de informes"
                        />
                        <p className="text-xs text-muted-foreground">
                            Nombre legible que se muestra en la aplicación.
                        </p>
                        {errors.etiqueta && (
                            <p className="text-sm text-destructive">
                                {errors.etiqueta}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="descripcion">Descripción</Label>
                        <Input
                            id="descripcion"
                            value={descripcion}
                            onChange={(e) => setDescripcion(e.target.value)}
                            placeholder="Elabora el contenido de los informes razonados."
                        />
                        {errors.descripcion && (
                            <p className="text-sm text-destructive">
                                {errors.descripcion}
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
                        Crear rol
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

RolesCrear.layout = {
    breadcrumbs: [
        { title: 'Roles y permisos', href: roles.index() },
        { title: 'Nuevo', href: roles.create() },
    ],
};
