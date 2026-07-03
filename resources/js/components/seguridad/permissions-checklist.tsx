import { ChevronDown } from 'lucide-react';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Label } from '@/components/ui/label';
import type { GrupoPermisos } from '@/types/seguridad';

type PermissionsChecklistProps = {
    groups: GrupoPermisos[];
    selected: number[];
    onToggle: (permissionId: number, checked: boolean) => void;
};

export function PermissionsChecklist({
    groups,
    selected,
    onToggle,
}: PermissionsChecklistProps) {
    return (
        <div className="flex flex-col gap-2 rounded-md border p-3">
            {groups.map((grupo) => (
                <Collapsible key={grupo.group} defaultOpen>
                    <CollapsibleTrigger className="flex w-full items-center justify-between py-1 text-sm font-medium">
                        {grupo.group}
                        <ChevronDown className="size-3.5 transition-transform group-data-[state=open]/collapsible:rotate-180" />
                    </CollapsibleTrigger>
                    <CollapsibleContent className="flex flex-col gap-2 py-1 pl-2">
                        {grupo.permissions.map((permiso) => (
                            <div
                                key={permiso.id}
                                className="flex items-center gap-2"
                            >
                                <Checkbox
                                    id={`permiso-${permiso.id}`}
                                    checked={selected.includes(permiso.id)}
                                    onCheckedChange={(checked) =>
                                        onToggle(permiso.id, checked === true)
                                    }
                                />
                                <Label
                                    htmlFor={`permiso-${permiso.id}`}
                                    className="font-mono text-xs font-normal"
                                >
                                    {permiso.name}
                                </Label>
                            </div>
                        ))}
                    </CollapsibleContent>
                </Collapsible>
            ))}
        </div>
    );
}
