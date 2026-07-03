import { Head, router } from '@inertiajs/react';
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

type PageProps = {
    catalogs: CatalogosUsuarios;
};

export default function UsuariosCrear({ catalogs }: PageProps) {
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [rut, setRut] = useState('');
    const [cargo, setCargo] = useState('');
    const [unidad, setUnidad] = useState('');
    const [rolesSeleccionados, setRolesSeleccionados] = useState<number[]>(
        [],
    );
    const [cfinancieroId, setCfinancieroId] = useState(SIN_SELECCION);
    const [ccostoId, setCcostoId] = useState(SIN_SELECCION);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [procesando, setProcesando] = useState(false);

    function alternarRol(rolId: number, marcado: boolean) {
        setRolesSeleccionados((actuales) =>
            marcado
                ? [...actuales, rolId]
                : actuales.filter((id) => id !== rolId),
        );
    }

    function enviar() {
        setProcesando(true);
        setErrors({});

        router.post(
            usuarios.store().url,
            {
                name,
                email,
                rut,
                cargo: cargo || null,
                unidad: unidad || null,
                roles: rolesSeleccionados,
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

    return (
        <>
            <Head title="Nuevo usuario" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Nuevo usuario
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

                    <div className="grid gap-2">
                        <Label>
                            Roles<span className="text-destructive">*</span>
                        </Label>
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
                        {errors.roles && (
                            <p className="text-sm text-destructive">
                                {errors.roles}
                            </p>
                        )}
                    </div>
                </div>

                <div className="flex gap-2">
                    <Button disabled={procesando} onClick={enviar}>
                        Crear usuario
                    </Button>
                    <Button
                        variant="outline"
                        disabled={procesando}
                        onClick={() => router.get(usuarios.index().url)}
                    >
                        Cancelar
                    </Button>
                </div>
            </div>
        </>
    );
}

UsuariosCrear.layout = {
    breadcrumbs: [
        { title: 'Usuarios', href: usuarios.index() },
        { title: 'Nuevo', href: usuarios.create() },
    ],
};
