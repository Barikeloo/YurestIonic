import { AuditEventApi } from '../../../services/audit-log.service';

type CategoryKey = 'order' | 'caja' | 'sale' | 'table' | 'catalog' | 'auth' | 'config' | 'system';
type SeverityKey = 'info' | 'warning' | 'danger' | 'critical' | 'success';

export interface UserRef {
  name: string;
  role: string;
  color: string;
  initials: string;
}

export interface AuditEvent {
  id: string;
  ts: string;
  cat: CategoryKey;
  sev: SeverityKey;
  action: string;
  entity?: { kind: string; id: string };
  entityLabel?: string;
  amount?: string;
  user: UserRef;
  device: string;
  ip: string;
  session?: string | null;
  summary: string;
  reason?: string | null;
  diff?: Array<{ field: string; before: string; after: string }> | null;
  inline?: { campo: string; from: string; to: string } | null;
  payload: Record<string, unknown>;
  related: string[];
  actions: string[];
  integrityHash: string;
  anomalyKind?: string | null;
}

export interface UserDirectoryEntry {
  name: string;
  role: string;
}

const AVATAR_COLORS = ['#1A6FE8', '#1A9E5A', '#D97706', '#B64040', '#6C5CE7', '#0D7E8C', '#C2410C', '#7C3AED'];
const UNKNOWN_USER: UserRef = { name: 'Sistema', role: '—', color: '#888888', initials: 'SY' };

const ENTITY_LABEL_PREFIX: Record<string, string> = {
  order: 'Pedido',
  cash_session: 'Sesión caja',
  cash_movement: 'Movimiento',
  sale: 'Venta',
  credit_note: 'Abono',
  product: 'Producto',
  merge: 'Fusión',
  transfer: 'Transferencia',
  user_session: 'Sesión usuario',
  auth_attempt: 'Intento de login',
};

const ACTIONS_BY_CATEGORY: Record<CategoryKey, string[]> = {
  order:   ['view-order', 'export'],
  sale:    ['view-sale', 'export'],
  caja:    ['view-session', 'export'],
  catalog: ['view-product', 'export'],
  table:   ['view-table', 'export'],
  auth:    ['mark-reviewed', 'export'],
  config:  ['export'],
  system:  ['mark-reviewed', 'export'],
};

function avatarColor(name: string): string {
  let h = 0;
  for (let i = 0; i < name.length; i++) h = ((h * 31) + name.charCodeAt(i)) >>> 0;
  return AVATAR_COLORS[h % AVATAR_COLORS.length];
}

function makeInitials(name: string): string {
  return name.split(' ').slice(0, 2).map(s => s[0] || '').join('').toUpperCase();
}

function resolveUser(userId: string | null, directory: Record<string, UserDirectoryEntry>): UserRef {
  if (!userId) return UNKNOWN_USER;
  const entry = directory[userId];
  if (!entry) return { ...UNKNOWN_USER, name: 'Usuario desconocido' };
  return {
    name: entry.name,
    role: entry.role,
    color: avatarColor(entry.name),
    initials: makeInitials(entry.name),
  };
}

function deriveEntityLabel(entityType: string, entityId: string, metadata: Record<string, unknown>): string {
  const productName = metadata['product_name'];
  if (entityType === 'product' && typeof productName === 'string') return productName;

  const tablesLabel = metadata['tables_label'];
  if (entityType === 'merge' && typeof tablesLabel === 'string') return tablesLabel;

  const prefix = ENTITY_LABEL_PREFIX[entityType] ?? entityType;
  return `${prefix} ${entityId}`;
}

function deriveAmount(metadata: Record<string, unknown>): string | undefined {
  const candidates = ['amount_formatted', 'total_formatted', 'delta_final_formatted'];
  for (const key of candidates) {
    const value = metadata[key];
    if (typeof value === 'string') return value;
  }
  return undefined;
}

function formatDiffValue(value: unknown): string {
  if (value === null || value === undefined) return '—';
  if (typeof value === 'string') return value;
  if (typeof value === 'number' || typeof value === 'boolean') return String(value);
  return JSON.stringify(value);
}

function deriveDiff(
  before: Record<string, unknown> | null,
  after: Record<string, unknown> | null,
): Array<{ field: string; before: string; after: string }> | null {
  if (!before && !after) return null;
  const beforeObj = before ?? {};
  const afterObj = after ?? {};
  const keys = new Set([...Object.keys(beforeObj), ...Object.keys(afterObj)]);
  const rows: Array<{ field: string; before: string; after: string }> = [];
  for (const key of keys) {
    rows.push({
      field: key,
      before: formatDiffValue(beforeObj[key]),
      after: formatDiffValue(afterObj[key]),
    });
  }
  return rows.length > 0 ? rows : null;
}

function deriveSessionLabel(sessionId: string | null): string | null {
  if (!sessionId) return null;
  return `Sesión ${sessionId.slice(0, 8)}`;
}

export function adaptApiEvent(
  api: AuditEventApi,
  directory: Record<string, UserDirectoryEntry> = {},
): AuditEvent {
  return {
    id: api.uuid,
    ts: api.created_at,
    cat: api.category,
    sev: api.severity,
    action: api.summary,
    entity: { kind: api.entity_type, id: api.entity_id },
    entityLabel: deriveEntityLabel(api.entity_type, api.entity_id, api.metadata),
    amount: deriveAmount(api.metadata),
    user: resolveUser(api.user_id, directory),
    device: api.device_id ?? '—',
    ip: api.ip_address ?? '—',
    session: deriveSessionLabel(api.session_id),
    summary: api.summary,
    reason: api.reason,
    diff: deriveDiff(api.before, api.after),
    inline: null,
    payload: api.metadata,
    related: [],
    actions: ACTIONS_BY_CATEGORY[api.category] ?? ['export'],
    integrityHash: api.integrity_hash,
    anomalyKind: api.anomaly_kind,
  };
}
