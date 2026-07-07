import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    Building2,
    Coins,
    FileBarChart,
    FileSearch,
    Gauge,
    History,
    KeyRound,
    Landmark,
    LayoutGrid,
    Plug,
    PlugZap,
    Receipt,
    ShieldCheck,
    ShoppingCart,
    Tags,
    TrendingUp,
    Users,
    Wallet,
    Workflow,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavGroup } from '@/components/nav-group';
import { NavMain } from '@/components/nav-main';
import {
    Sidebar,
    SidebarContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as licitacionesMercadoPublico } from '@/routes/adquisiciones/licitaciones_mp';
import { index as ordenesCompraMercadoPublico } from '@/routes/adquisiciones/ordenes_compra_mp';
import { index as procesosAdquisicion } from '@/routes/adquisiciones/procesos';
import { index as auditoria } from '@/routes/auditoria';
import { index as indicadoresEconomicos } from '@/routes/indicadores-economicos';
import { index as definicionesInformeRazonado } from '@/routes/informes-razonados/definiciones';
import { index as ejecucionesInformeRazonado } from '@/routes/informes-razonados/ejecuciones';
import { index as conectores } from '@/routes/integraciones/conectores';
import { index as sistemasExternos } from '@/routes/integraciones/sistemas-externos';
import { index as ccostos } from '@/routes/maestros/ccostos';
import { index as cfinancieros } from '@/routes/maestros/cfinancieros';
import { index as clientesMedidores } from '@/routes/maestros/clientes-medidores';
import { index as items } from '@/routes/maestros/items';
import { index as proveedores } from '@/routes/maestros/proveedores';
import { index as casos } from '@/routes/pago-proveedores/casos';
import { index as egresosCgu } from '@/routes/pago-proveedores/egresos-cgu';
import { index as periodosReportabilidad } from '@/routes/reportabilidad/periodos';
import { index as roles } from '@/routes/roles';
import { index as importacionesSgf } from '@/routes/sgf/importaciones';
import { index as usuarios } from '@/routes/usuarios';
import { index as definicionesWorkflow } from '@/routes/workflow/definiciones';
import type { NavItem } from '@/types';

type NavItemConPermiso = NavItem & { permiso?: string };

function filtrarPorPermiso(
    items: NavItemConPermiso[],
    permisos: string[],
): NavItem[] {
    return items.filter(
        (item) => !item.permiso || permisos.includes(item.permiso),
    );
}

const generalNavItems: NavItem[] = [
    {
        title: 'Panel general',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const administracionNavItems: NavItemConPermiso[] = [
    {
        title: 'Usuarios',
        href: usuarios(),
        icon: Users,
        permiso: 'usuarios.ver',
    },
    {
        title: 'Auditoría',
        href: auditoria(),
        icon: ShieldCheck,
        permiso: 'auditoria.ver',
    },
    {
        title: 'Definiciones de Workflow',
        href: definicionesWorkflow(),
        icon: Workflow,
    },
    {
        title: 'Roles y Permisos',
        href: roles(),
        icon: KeyRound,
        permiso: 'roles.administrar',
    },
    {
        title: 'Proveedores',
        href: proveedores(),
        icon: Building2,
        permiso: 'core_institucional.administrar',
    },
    {
        title: 'Clientes Medidores',
        href: clientesMedidores(),
        icon: Gauge,
        permiso: 'core_institucional.administrar',
    },
    {
        title: 'Ítems Presupuestarios',
        href: items(),
        icon: Tags,
        permiso: 'core_institucional.administrar',
    },
];

const pagoProveedoresNavItems: NavItem[] = [
    {
        title: 'Casos',
        href: casos(),
        icon: Wallet,
    },
    {
        title: 'Egresos CGU',
        href: egresosCgu(),
        icon: Receipt,
    },
    {
        title: 'Importaciones SGF',
        href: importacionesSgf(),
        icon: History,
    },
];

const adquisicionesNavItems: NavItemConPermiso[] = [
    {
        title: 'Procesos',
        href: procesosAdquisicion(),
        icon: ShoppingCart,
    },
    {
        title: 'Órdenes de Compra (Mercado Público)',
        href: ordenesCompraMercadoPublico(),
        icon: FileSearch,
    },
    {
        title: 'Licitaciones (Mercado Público)',
        href: licitacionesMercadoPublico(),
        icon: FileSearch,
    },
];

const estructuraInstitucionalNavItems: NavItemConPermiso[] = [
    {
        title: 'Centros Financieros',
        href: cfinancieros(),
        icon: Landmark,
        permiso: 'core_institucional.administrar',
    },
    {
        title: 'Centros de Costos',
        href: ccostos(),
        icon: Coins,
        permiso: 'core_institucional.administrar',
    },
];

const reportabilidadNavItems: NavItemConPermiso[] = [
    {
        title: 'Períodos de Reportabilidad',
        href: periodosReportabilidad(),
        icon: BarChart3,
        permiso: 'reportabilidad.ver',
    },
    {
        title: 'Definiciones de Informes',
        href: definicionesInformeRazonado(),
        icon: FileBarChart,
        permiso: 'informes.ver',
    },
    {
        title: 'Ejecuciones de Informes',
        href: ejecucionesInformeRazonado(),
        icon: FileBarChart,
        permiso: 'informes.ver',
    },
];

const integracionesNavItems: NavItem[] = [
    {
        title: 'Sistemas Externos',
        href: sistemasExternos(),
        icon: Plug,
    },
    {
        title: 'Conectores Playwright',
        href: conectores(),
        icon: PlugZap,
    },
    {
        title: 'Indicadores Económicos',
        href: indicadoresEconomicos(),
        icon: TrendingUp,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;

    const administracionItems = filtrarPorPermiso(
        [...administracionNavItems, ...estructuraInstitucionalNavItems],
        auth.permissions,
    );
    const reportabilidadItems = filtrarPorPermiso(
        reportabilidadNavItems,
        auth.permissions,
    );
    const adquisicionesItems = filtrarPorPermiso(
        adquisicionesNavItems,
        auth.permissions,
    );

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo subtitle="Finanzas y Ppto" />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={generalNavItems} />
                {administracionItems.length > 0 && (
                    <NavGroup
                        label="Administración"
                        items={administracionItems}
                    />
                )}
                <NavGroup
                    label="Pago de Proveedores"
                    items={pagoProveedoresNavItems}
                />
                <NavGroup label="Adquisiciones" items={adquisicionesItems} />
                {reportabilidadItems.length > 0 && (
                    <NavGroup
                        label="Reportabilidad"
                        items={reportabilidadItems}
                    />
                )}
                <NavGroup label="Integraciones" items={integracionesNavItems} />
            </SidebarContent>
        </Sidebar>
    );
}
