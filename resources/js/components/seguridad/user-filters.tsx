import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { CatalogosUsuarios, FiltrosUsuarios } from '@/types/seguridad';

const SIN_FILTRO = '__todos__';

type UserFiltersProps = {
    filters: FiltrosUsuarios;
    catalogs: CatalogosUsuarios;
    search: string;
    onSearchChange: (value: string) => void;
    onFilterChange: (
        partial: Partial<
            Pick<
                FiltrosUsuarios,
                | 'estado'
                | 'rol_id'
                | 'jurisdiccion_id'
                | 'centro_financiero_id'
                | 'centro_costo_id'
            >
        >,
    ) => void;
    onClear: () => void;
    hayFiltrosActivos: boolean;
};

export function UserFilters({
    filters,
    catalogs,
    search,
    onSearchChange,
    onFilterChange,
    onClear,
    hayFiltrosActivos,
}: UserFiltersProps) {
    return (
        <div className="flex flex-col gap-3">
            <div className="flex flex-wrap items-center gap-3">
                <Input
                    placeholder="Buscar por nombre, email o RUT…"
                    value={search}
                    onChange={(e) => onSearchChange(e.target.value)}
                    className="w-72"
                />

                <Select
                    value={filters.estado ?? SIN_FILTRO}
                    onValueChange={(value) =>
                        onFilterChange({
                            estado: value === SIN_FILTRO ? null : value,
                        })
                    }
                >
                    <SelectTrigger className="w-40">
                        <SelectValue placeholder="Estado" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={SIN_FILTRO}>
                            Todos los estados
                        </SelectItem>
                        <SelectItem value="activo">Activo</SelectItem>
                        <SelectItem value="inactivo">Inactivo</SelectItem>
                    </SelectContent>
                </Select>

                <Select
                    value={
                        filters.rol_id !== null
                            ? String(filters.rol_id)
                            : SIN_FILTRO
                    }
                    onValueChange={(value) =>
                        onFilterChange({
                            rol_id: value === SIN_FILTRO ? null : Number(value),
                        })
                    }
                >
                    <SelectTrigger className="w-44">
                        <SelectValue placeholder="Rol" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={SIN_FILTRO}>
                            Todos los roles
                        </SelectItem>
                        {catalogs.roles.map((rol) => (
                            <SelectItem key={rol.id} value={String(rol.id)}>
                                {rol.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                <Select
                    value={
                        filters.jurisdiccion_id !== null
                            ? String(filters.jurisdiccion_id)
                            : SIN_FILTRO
                    }
                    onValueChange={(value) =>
                        onFilterChange({
                            jurisdiccion_id:
                                value === SIN_FILTRO ? null : Number(value),
                        })
                    }
                >
                    <SelectTrigger className="w-48">
                        <SelectValue placeholder="Jurisdicción" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={SIN_FILTRO}>
                            Todas las jurisdicciones
                        </SelectItem>
                        {catalogs.jurisdicciones.map((jurisdiccion) => (
                            <SelectItem
                                key={jurisdiccion.id}
                                value={String(jurisdiccion.id)}
                            >
                                {jurisdiccion.nombre}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                <Select
                    value={
                        filters.centro_financiero_id !== null
                            ? String(filters.centro_financiero_id)
                            : SIN_FILTRO
                    }
                    onValueChange={(value) =>
                        onFilterChange({
                            centro_financiero_id:
                                value === SIN_FILTRO ? null : Number(value),
                        })
                    }
                >
                    <SelectTrigger className="w-52">
                        <SelectValue placeholder="Centro financiero" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={SIN_FILTRO}>
                            Todos los centros financieros
                        </SelectItem>
                        {catalogs.centros_financieros.map((cfinanciero) => (
                            <SelectItem
                                key={cfinanciero.id}
                                value={String(cfinanciero.id)}
                            >
                                {cfinanciero.nombre}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                <Select
                    value={
                        filters.centro_costo_id !== null
                            ? String(filters.centro_costo_id)
                            : SIN_FILTRO
                    }
                    onValueChange={(value) =>
                        onFilterChange({
                            centro_costo_id:
                                value === SIN_FILTRO ? null : Number(value),
                        })
                    }
                >
                    <SelectTrigger className="w-48">
                        <SelectValue placeholder="Centro de costo" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={SIN_FILTRO}>
                            Todos los centros de costo
                        </SelectItem>
                        {catalogs.centros_costos.map((ccosto) => (
                            <SelectItem
                                key={ccosto.id}
                                value={String(ccosto.id)}
                            >
                                {ccosto.nombre}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                {hayFiltrosActivos && (
                    <Button variant="ghost" onClick={onClear}>
                        Limpiar filtros
                    </Button>
                )}
            </div>
        </div>
    );
}
