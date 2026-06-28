export type AuditLogEntry = {
    id: number;
    user: string | null;
    action: string;
    auditable_type: string | null;
    auditable_id: number | null;
    before: Record<string, unknown> | null;
    after: Record<string, unknown> | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
};
