import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    Building2,
    Coins,
    FileBarChart,
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
import procesosAdquisicion from '@/routes/adquisiciones/procesos';
import auditoria from '@/routes/auditoria';
import indicadoresEconomicos from '@/routes/indicadores-economicos';
import definicionesInformeRazonado from '@/routes/informes-razonados/definiciones';
import ejecucionesInformeRazonado from '@/routes/informes-razonados/ejecuciones';
import conectores from '@/routes/integraciones/conectores';
import sistemasExternos from '@/routes/integraciones/sistemas-externos';
import ccostos from '@/routes/maestros/ccostos';
import cfinancieros from '@/routes/maestros/cfinancieros';
import clientesMedidores from '@/routes/maestros/clientes-medidores';
import proveedores from '@/routes/maestros/proveedores';
import casos from '@/routes/pago-proveedores/casos';
import egresosCgu from '@/routes/pago-proveedores/egresos-cgu';
import periodosReportabilidad from '@/routes/reportabilidad/periodos';
import roles from '@/routes/roles';
import importacionesSgf from '@/routes/sgf/importaciones';
import usuarios from '@/routes/usuarios';
import definicionesWorkflow from '@/routes/workflow/definiciones';
import type { NavItem } from '@/types';

const generalNavItems: NavItem[] = [
    {
        title: 'Panel general',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const administracionNavItems: NavItem[] = [
    {
        title: 'Usuarios',
        href: usuarios.index(),
        icon: Users,
    },
    {
        title: 'Auditoría',
        href: auditoria.index(),
        icon: ShieldCheck,
    },
    {
        title: 'Definiciones de Workflow',
        href: definicionesWorkflow.index(),
        icon: Workflow,
    },
    {
        title: 'Roles y Permisos',
        href: roles.index(),
        icon: KeyRound,
    },
];

const pagoProveedoresNavItems: NavItem[] = [
    {
        title: 'Casos',
        href: casos.index(),
        icon: Wallet,
    },
    {
        title: 'Egresos CGU',
        href: egresosCgu.index(),
        icon: Receipt,
    },
    {
        title: 'Importaciones SGF',
        href: importacionesSgf.index(),
        icon: History,
    },
];

const adquisicionesNavItems: NavItem[] = [
    {
        title: 'Procesos',
        href: procesosAdquisicion.index(),
        icon: ShoppingCart,
    },
];

const maestrosNavItems: NavItem[] = [
    {
        title: 'Proveedores',
        href: proveedores.index(),
        icon: Building2,
    },
    {
        title: 'Clientes Medidores',
        href: clientesMedidores.index(),
        icon: Gauge,
    },
];

const estructuraInstitucionalNavItems: NavItem[] = [
    {
        title: 'Centros Financieros',
        href: cfinancieros.index(),
        icon: Landmark,
    },
    {
        title: 'Centros de Costos',
        href: ccostos.index(),
        icon: Coins,
    },
];

const reportabilidadNavItems: NavItem[] = [
    {
        title: 'Períodos de Reportabilidad',
        href: periodosReportabilidad.index(),
        icon: BarChart3,
    },
    {
        title: 'Definiciones de Informes',
        href: definicionesInformeRazonado.index(),
        icon: FileBarChart,
    },
    {
        title: 'Ejecuciones de Informes',
        href: ejecucionesInformeRazonado.index(),
        icon: FileBarChart,
    },
];

const integracionesNavItems: NavItem[] = [
    {
        title: 'Sistemas Externos',
        href: sistemasExternos.index(),
        icon: Plug,
    },
    {
        title: 'Conectores Playwright',
        href: conectores.index(),
        icon: PlugZap,
    },
    {
        title: 'Indicadores Económicos',
        href: indicadoresEconomicos.index(),
        icon: TrendingUp,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const puedeAdministrarEstructura = auth.permissions.includes(
        'core_institucional.administrar',
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
                <NavGroup
                    label="Administración"
                    items={administracionNavItems}
                />
                <NavGroup
                    label="Pago de Proveedores"
                    items={pagoProveedoresNavItems}
                />
                <NavGroup label="Adquisiciones" items={adquisicionesNavItems} />
                <NavGroup
                    label="Maestros"
                    items={
                        puedeAdministrarEstructura
                            ? [
                                  ...maestrosNavItems,
                                  ...estructuraInstitucionalNavItems,
                              ]
                            : maestrosNavItems
                    }
                />
                <NavGroup
                    label="Reportabilidad"
                    items={reportabilidadNavItems}
                />
                <NavGroup label="Integraciones" items={integracionesNavItems} />
            </SidebarContent>
        </Sidebar>
    );
}
