import { Link } from '@inertiajs/react';
import {
    BarChart3,
    Building2,
    FileBarChart,
    History,
    LayoutGrid,
    Plug,
    Receipt,
    ShieldCheck,
    ShoppingCart,
    TrendingUp,
    Wallet,
    Workflow,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
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
import sistemasExternos from '@/routes/integraciones/sistemas-externos';
import proveedores from '@/routes/maestros/proveedores';
import casos from '@/routes/pago-proveedores/casos';
import egresosCgu from '@/routes/pago-proveedores/egresos-cgu';
import periodosReportabilidad from '@/routes/reportabilidad/periodos';
import importacionesSgf from '@/routes/sgf/importaciones';
import definicionesWorkflow from '@/routes/workflow/definiciones';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Indicadores Económicos',
        href: indicadoresEconomicos.index(),
        icon: TrendingUp,
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
        title: 'Proveedores',
        href: proveedores.index(),
        icon: Building2,
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

const integracionesNavItems: NavItem[] = [
    {
        title: 'Sistemas Externos',
        href: sistemasExternos.index(),
        icon: Plug,
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

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                <NavMain
                    items={pagoProveedoresNavItems}
                    label="Pago de Proveedores"
                />
                <NavMain
                    items={adquisicionesNavItems}
                    label="Adquisiciones"
                />
                <NavMain
                    items={integracionesNavItems}
                    label="Integraciones"
                />
                <NavMain
                    items={reportabilidadNavItems}
                    label="Reportabilidad"
                />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
