import { Link } from '@inertiajs/react';
import {
    LayoutGrid,
    Receipt,
    ShieldCheck,
    ShoppingCart,
    TrendingUp,
    Wallet,
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
import casos from '@/routes/pago-proveedores/casos';
import egresosCgu from '@/routes/pago-proveedores/egresos-cgu';
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
];

const adquisicionesNavItems: NavItem[] = [
    {
        title: 'Procesos',
        href: procesosAdquisicion.index(),
        icon: ShoppingCart,
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
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
