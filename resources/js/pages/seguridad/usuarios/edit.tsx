import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
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
};

type PageProps = {
    usuario: UsuarioEditable;
    catalogs: CatalogosUsuarios;
};

export default function UsuariosEditar({ usuario, catalogs }: PageProps) {
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

    return (
        <>
            <Head title={`Editar usuario — ${usuario.name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">
                    Editar usuario
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
