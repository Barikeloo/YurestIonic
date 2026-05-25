import { Component, OnInit, OnDestroy, signal, computed, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';

type CategoryKey = 'order' | 'caja' | 'sale' | 'table' | 'catalog' | 'auth' | 'config' | 'system';
type SeverityKey = 'info' | 'warning' | 'danger' | 'critical' | 'success';

interface User {
  name: string;
  role: string;
  color: string;
  initials: string;
}

interface AuditEvent {
  id: string;
  ts: string;
  cat: CategoryKey;
  sev: SeverityKey;
  action: string;
  entity?: { kind: string; id: string };
  entityLabel?: string;
  amount?: string;
  user: User;
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
}

interface Category { label: string; color: string; bg: string; }
interface Chip { id: string; label: string; icon: string; }
interface Tab { id: string; label: string; cat: CategoryKey | null; }
interface SavedView { id: string; name: string; icon: string; filters: Record<string, string | null>; }

const AVATAR_COLORS = ['#1A6FE8','#1A9E5A','#D97706','#B64040','#6C5CE7','#0D7E8C','#C2410C','#7C3AED'];

function avatarColor(name: string): string {
  let h = 0;
  for (let i = 0; i < name.length; i++) h = ((h * 31) + name.charCodeAt(i)) >>> 0;
  return AVATAR_COLORS[h % AVATAR_COLORS.length];
}
function makeInitials(name: string): string {
  return name.split(' ').slice(0, 2).map(s => s[0] || '').join('').toUpperCase();
}
function makeUser(name: string, role: string): User {
  return { name, role, color: avatarColor(name), initials: makeInitials(name) };
}
function makeHash(id: string): string {
  let h = 5381;
  for (let i = 0; i < id.length; i++) h = ((h << 5) + h + id.charCodeAt(i)) >>> 0;
  const hex = '0123456789abcdef';
  let out = '';
  let n = h;
  for (let i = 0; i < 64; i++) { out += hex[(n + i * 31) % 16]; n = ((n * 1103515245) + 12345) >>> 0; }
  return out;
}

const USERS: Record<string, User> = {
  ana:   makeUser('Ana Martínez',    'Supervisora'),
  juan:  makeUser('Juan García',      'Camarero'),
  laura: makeUser('Laura Fernández',  'Camarera'),
  marco: makeUser('Marco Ruiz',       'Cocinero'),
  admin: makeUser('Carlos Admin',     'Administrador'),
  sara:  makeUser('Sara Vidal',       'Camarera'),
};

const CATEGORIES: Record<CategoryKey, Category> = {
  order:   { label: 'Pedidos',   color: '#1A6FE8', bg: '#EAF1FD' },
  caja:    { label: 'Caja',      color: '#D97706', bg: '#FFF4E6' },
  sale:    { label: 'Ventas',    color: '#1A9E5A', bg: '#E6F5ED' },
  table:   { label: 'Mesas',     color: '#1A6FE8', bg: '#EAF1FD' },
  catalog: { label: 'Catálogo',  color: '#6C5CE7', bg: '#EEEAFB' },
  auth:    { label: 'Acceso',    color: '#3D3D3D', bg: '#F0F0F0' },
  config:  { label: 'Config.',   color: '#3D3D3D', bg: '#F0F0F0' },
  system:  { label: 'Sistema',   color: '#B64040', bg: '#FBECEC' },
};

const SEV_LABEL: Record<SeverityKey, string> = {
  info: 'Info', warning: 'Warning', danger: 'Danger', critical: 'Critical', success: 'Success',
};

const EVENTS: AuditEvent[] = [
  {
    id: 'evt-001', ts: '2026-05-25T14:32:15', cat: 'auth', sev: 'critical',
    action: 'Login fallido — PIN incorrecto',
    entity: { kind: 'auth_attempt', id: 'att-9F3A' },
    user: USERS['juan'], device: 'TPV-02', ip: '192.168.1.45', session: null,
    summary: 'Tercer intento consecutivo con PIN incorrecto. El usuario quedó temporalmente bloqueado durante 5 minutos.',
    reason: '3 intentos fallidos en 90 segundos',
    diff: null,
    inline: { campo: 'estado_usuario', from: 'active', to: 'locked-temp' },
    payload: { user_uuid: 'u-juan-7821', pin_attempt_count: 3, lock_duration_sec: 300 },
    related: ['evt-010'], actions: ['mark-reviewed', 'export'],
  },
  {
    id: 'evt-002', ts: '2026-05-25T14:28:02', cat: 'order', sev: 'danger',
    action: 'Reapertura de pedido',
    entity: { kind: 'order', id: 'a4f3d8e1' }, entityLabel: 'Pedido a4f3d8e1',
    user: USERS['ana'], device: 'TPV-01', ip: '192.168.1.41', session: 'Sesión caja #45',
    summary: 'Pedido cerrado y marcado para cobrar fue reabierto por la supervisora tras reclamación del cliente para añadir 2 cafés.',
    reason: 'Reclamación del cliente — añadir 2 cafés',
    diff: [
      { field: 'status',      before: 'to-charge', after: 'open' },
      { field: 'closed_at',   before: '14:24:50',  after: 'null' },
      { field: 'total',       before: '47,80 €',   after: '47,80 €' },
      { field: 'reopened_by', before: '—',         after: 'u-ana-1042' },
    ],
    inline: null,
    payload: { order_id: 'a4f3d8e1', table_id: 'T-12', previous_status: 'to-charge', new_status: 'open' },
    related: ['evt-006', 'evt-007', 'evt-004'], actions: ['view-order', 'export'],
  },
  {
    id: 'evt-003', ts: '2026-05-25T14:25:11', cat: 'caja', sev: 'warning',
    action: 'Movimiento de caja — Retirada',
    entity: { kind: 'cash_movement', id: 'mov-0142' }, entityLabel: 'Movimiento #0142',
    amount: '-50,00 €',
    user: USERS['ana'], device: 'TPV-01', ip: '192.168.1.41', session: 'Sesión caja #45',
    summary: 'Retirada manual de efectivo de 50,00 € registrada con motivo "pago proveedor". Arqueo posterior detectó discrepancia de 2,40 €.',
    reason: 'Pago proveedor — discrepancia detectada: 2,40 €',
    diff: [
      { field: 'cash_balance', before: '847,60 €', after: '797,60 €' },
      { field: 'expected',     before: '847,60 €', after: '800,00 €' },
      { field: 'delta',        before: '0,00 €',   after: '−2,40 €' },
    ],
    inline: null,
    payload: { movement_id: 'mov-0142', type: 'out', amount_cents: -5000, discrepancy_cents: -240 },
    related: ['evt-005', 'evt-013'], actions: ['view-session', 'export'],
  },
  {
    id: 'evt-004', ts: '2026-05-25T14:20:38', cat: 'table', sev: 'info',
    action: 'Transferencia de mesa',
    entity: { kind: 'transfer', id: 'trf-0099' }, entityLabel: 'Transferencia #0099',
    user: USERS['juan'], device: 'TPV-03', ip: '192.168.1.43', session: 'Sesión caja #45',
    summary: 'Mesa 12 transferida íntegramente a Mesa 5 (4 comensales, 47,80 € en consumo) a petición del cliente para cambio de zona.',
    reason: 'Cambio de zona solicitado por el cliente',
    diff: [
      { field: 'from_table', before: 'Mesa 12', after: '—' },
      { field: 'to_table',   before: '—',        after: 'Mesa 5' },
      { field: 'diners',     before: '4',         after: '4' },
      { field: 'total',      before: '47,80 €',   after: '47,80 €' },
    ],
    inline: null,
    payload: { transfer_id: 'trf-0099', from_table_id: 'T-12', to_table_id: 'T-05' },
    related: ['evt-002', 'evt-006'], actions: ['view-order', 'view-table', 'export'],
  },
  {
    id: 'evt-005', ts: '2026-05-25T14:15:00', cat: 'caja', sev: 'info',
    action: 'Apertura de sesión de caja',
    entity: { kind: 'cash_session', id: 'cs-45' }, entityLabel: 'Sesión caja #45',
    user: USERS['ana'], device: 'TPV-01', ip: '192.168.1.41', session: 'Sesión caja #45',
    summary: 'Apertura de sesión de caja del turno de tarde con fondo inicial de 200,00 €.',
    reason: 'Inicio de turno tarde',
    diff: null,
    inline: { campo: 'estado_sesión', from: 'closed', to: 'open' },
    payload: { session_id: 'cash-session-45', opening_float_cents: 20000 },
    related: ['evt-003'], actions: ['view-session', 'export'],
  },
  {
    id: 'evt-006', ts: '2026-05-25T14:10:22', cat: 'order', sev: 'warning',
    action: 'Pedido marcado para cobrar',
    entity: { kind: 'order', id: 'a4f3d8e1' }, entityLabel: 'Pedido a4f3d8e1',
    user: USERS['juan'], device: 'TPV-03', ip: '192.168.1.43', session: 'Sesión caja #45',
    summary: 'El camarero marcó el pedido como listo para cobrar tras servir todas las comandas.',
    reason: null, diff: null,
    inline: { campo: 'status', from: 'open', to: 'to-charge' },
    payload: { order_id: 'a4f3d8e1', total_cents: 4780 },
    related: ['evt-002', 'evt-004'], actions: ['view-order', 'export'],
  },
  {
    id: 'evt-007', ts: '2026-05-25T14:05:48', cat: 'sale', sev: 'success',
    action: 'Venta creada',
    entity: { kind: 'sale', id: 'V-2026-1142' }, entityLabel: 'Venta V-2026-1142',
    amount: '47,80 €',
    user: USERS['laura'], device: 'TPV-02', ip: '192.168.1.45', session: 'Sesión caja #45',
    summary: 'Nueva venta registrada por 47,80 € — método de pago: tarjeta.',
    reason: null, diff: null, inline: null,
    payload: { sale_id: 'V-2026-1142', total_cents: 4780, payment_method: 'card' },
    related: ['evt-002'], actions: ['view-sale', 'export'],
  },
  {
    id: 'evt-008', ts: '2026-05-25T13:58:14', cat: 'catalog', sev: 'info',
    action: 'Producto activado',
    entity: { kind: 'product', id: 'p-7821' }, entityLabel: 'Croqueta Casera',
    user: USERS['admin'], device: 'TPV-Admin', ip: '192.168.1.10', session: null,
    summary: 'Producto "Croqueta Casera" reactivado tras reposición de stock.',
    reason: 'Stock repuesto: 24 unidades',
    diff: [
      { field: 'active',     before: 'false', after: 'true' },
      { field: 'stock',      before: '0',     after: '24' },
      { field: 'updated_at', before: '—',     after: '13:58:14' },
    ],
    inline: null,
    payload: { product_id: 'p-7821', name: 'Croqueta Casera', price_cents: 850 },
    related: ['evt-011'], actions: ['view-product', 'export'],
  },
  {
    id: 'evt-009', ts: '2026-05-25T13:50:02', cat: 'system', sev: 'critical',
    action: 'Cierre forzado de caja',
    entity: { kind: 'cash_session', id: 'cs-44' }, entityLabel: 'Sesión caja #44',
    user: USERS['admin'], device: 'TPV-Admin', ip: '192.168.1.10', session: 'Sesión caja #44',
    summary: 'Sesión de turno mañana cerrada de forma forzada por administrador. Discrepancia final 8,40 €.',
    reason: 'Cierre forzado — cuadre no realizado en tiempo',
    diff: [
      { field: 'status',      before: 'open', after: 'force-closed' },
      { field: 'delta_final', before: '—',    after: '−8,40 €' },
    ],
    inline: null,
    payload: { session_id: 'cash-session-44', final_discrepancy_cents: -840 },
    related: [], actions: ['view-session', 'mark-reviewed', 'export'],
  },
  {
    id: 'evt-010', ts: '2026-05-25T13:45:30', cat: 'auth', sev: 'success',
    action: 'Login PIN',
    entity: { kind: 'user_session', id: 'ses-7811' }, entityLabel: 'Sesión usuario',
    user: USERS['ana'], device: 'TPV-01', ip: '192.168.1.41', session: null,
    summary: 'Inicio de sesión correcto con PIN de 4 dígitos.',
    reason: null, diff: null, inline: null,
    payload: { user_uuid: 'u-ana-1042', method: 'pin', duration_ms: 184 },
    related: ['evt-005'], actions: ['export'],
  },
  {
    id: 'evt-011', ts: '2026-05-25T13:32:18', cat: 'sale', sev: 'danger',
    action: 'Abono emitido',
    entity: { kind: 'credit_note', id: 'CN-0142' }, entityLabel: 'Abono CN-0142',
    amount: '−23,40 €',
    user: USERS['ana'], device: 'TPV-01', ip: '192.168.1.41', session: 'Sesión caja #45',
    summary: 'Abono parcial emitido sobre venta V-2026-1138 por importe de 23,40 €. Motivo: producto en mal estado.',
    reason: 'Producto en mal estado — devolución completa de plato',
    diff: [
      { field: 'sale_status', before: 'completed',          after: 'partially_refunded' },
      { field: 'refunded',    before: '0,00 €',              after: '23,40 €' },
    ],
    inline: null,
    payload: { credit_note_id: 'CN-0142', amount_cents: -2340 },
    related: ['evt-008'], actions: ['view-sale', 'export'],
  },
  {
    id: 'evt-012', ts: '2026-05-25T13:20:05', cat: 'table', sev: 'info',
    action: 'Fusión de mesas',
    entity: { kind: 'merge', id: 'mrg-0033' }, entityLabel: 'Mesa 8 + Mesa 9',
    user: USERS['sara'], device: 'TPV-02', ip: '192.168.1.45', session: 'Sesión caja #45',
    summary: 'Mesas 8 y 9 fusionadas en una única comanda para grupo de 8 comensales.',
    reason: 'Grupo grande — 8 comensales',
    diff: null,
    inline: { campo: 'modo', from: 'individual', to: 'merged' },
    payload: { merge_id: 'mrg-0033', tables: ['T-08', 'T-09'], diners: 8 },
    related: [], actions: ['view-order', 'export'],
  },
  {
    id: 'evt-013', ts: '2026-05-25T13:10:44', cat: 'catalog', sev: 'warning',
    action: 'Cambio de precio',
    entity: { kind: 'product', id: 'p-3301' }, entityLabel: 'Hamburguesa Yurest',
    user: USERS['admin'], device: 'TPV-Admin', ip: '192.168.1.10', session: null,
    summary: 'Precio de venta actualizado en la carta principal.',
    reason: 'Revisión trimestral de carta',
    diff: [
      { field: 'price',    before: '9,50 €', after: '10,50 €' },
      { field: 'tax_rate', before: '10 %',   after: '10 %' },
    ],
    inline: null,
    payload: { product_id: 'p-3301', price_cents_before: 950, price_cents_after: 1050 },
    related: ['evt-008'], actions: ['view-product', 'export'],
  },
  {
    id: 'evt-014', ts: '2026-05-24T22:45:12', cat: 'caja', sev: 'success',
    action: 'Cierre de sesión de caja',
    entity: { kind: 'cash_session', id: 'cs-43' }, entityLabel: 'Sesión caja #43',
    user: USERS['laura'], device: 'TPV-02', ip: '192.168.1.45', session: 'Sesión caja #43',
    summary: 'Cierre correcto de sesión nocturna con informe Z generado.',
    reason: null,
    diff: [
      { field: 'status',   before: 'open', after: 'closed' },
      { field: 'z_report', before: '—',    after: 'Z-2026-0143' },
      { field: 'delta',    before: '—',    after: '0,00 €' },
    ],
    inline: null,
    payload: { session_id: 'cash-session-43', z_report_id: 'Z-2026-0143' },
    related: [], actions: ['view-session', 'export'],
  },
  {
    id: 'evt-015', ts: '2026-05-24T20:12:00', cat: 'order', sev: 'danger',
    action: 'Pedido cancelado',
    entity: { kind: 'order', id: 'b1d4e7c2' }, entityLabel: 'Pedido b1d4e7c2',
    user: USERS['sara'], device: 'TPV-02', ip: '192.168.1.45', session: 'Sesión caja #43',
    summary: 'Pedido cancelado tras ausencia del cliente — comanda no servida.',
    reason: 'Cliente abandonó el local antes de servir',
    diff: null,
    inline: { campo: 'status', from: 'open', to: 'cancelled' },
    payload: { order_id: 'b1d4e7c2', reason_code: 'ABANDONED' },
    related: [], actions: ['view-order', 'export'],
  },
];

const EVENT_INDEX: Record<string, AuditEvent> = {};
EVENTS.forEach(e => EVENT_INDEX[e.id] = e);

const TABS: Tab[] = [
  { id: 'all',     label: 'Todo',      cat: null       },
  { id: 'order',   label: 'Pedidos',   cat: 'order'    },
  { id: 'caja',    label: 'Caja',      cat: 'caja'     },
  { id: 'sale',    label: 'Ventas',    cat: 'sale'     },
  { id: 'table',   label: 'Mesas',     cat: 'table'    },
  { id: 'catalog', label: 'Catálogo',  cat: 'catalog'  },
  { id: 'auth',    label: 'Acceso',    cat: 'auth'     },
  { id: 'config',  label: 'Config.',   cat: 'config'   },
  { id: 'system',  label: 'Sistema',   cat: 'system'   },
];

const CHIPS: Chip[] = [
  { id: 'critical',  label: 'Solo críticos',       icon: 'alert'     },
  { id: 'mine',      label: 'Mis acciones',         icon: 'user'      },
  { id: 'lasthour',  label: 'Última hora',          icon: 'clock'     },
  { id: 'caja',      label: 'Movimientos de caja',  icon: 'wallet'    },
  { id: 'cancel',    label: 'Cancelaciones',        icon: 'ban'       },
  { id: 'reopen',    label: 'Reaperturas',          icon: 'lock-open' },
  { id: 'auth-fail', label: 'Fallos de acceso',     icon: 'shield-off'},
  { id: 'transfer',  label: 'Transferencias',       icon: 'swap'      },
];

const SAVED_VIEWS: SavedView[] = [
  { id: 'sv-default',      name: 'Vista por defecto',                icon: 'list',      filters: { tab: 'all',   chip: null,       sev: 'all', user: 'all' } },
  { id: 'sv-criticos',     name: 'Críticos del turno',               icon: 'alert',     filters: { tab: 'all',   chip: 'critical', sev: 'all', user: 'all' } },
  { id: 'sv-reaperturas',  name: 'Mis reaperturas (Ana)',             icon: 'lock-open', filters: { tab: 'order', chip: 'reopen',   sev: 'all', user: 'Ana Martínez' } },
  { id: 'sv-cuadres',      name: 'Cuadres con discrepancia',         icon: 'wallet',    filters: { tab: 'caja',  chip: null,       sev: 'warning', user: 'all' } },
  { id: 'sv-fallos',       name: 'Fallos de acceso (24h)',           icon: 'shield-off',filters: { tab: 'auth',  chip: 'auth-fail',sev: 'all', user: 'all' } },
];

const SPARK_HOURLY = [0,0,0,0,0,0,1,2,4,8,15,12,9,14,22,18,11,7,5,3,2,1,0,0];

const ANOMALIES: Record<string, { label: string; severity: string }> = {
  'evt-001': { label: '3 intentos en 90s',      severity: 'critical' },
  'evt-003': { label: 'Discrepancia atípica',    severity: 'warning'  },
  'evt-009': { label: '2º cierre forzado',       severity: 'danger'   },
  'evt-011': { label: 'Abono > 20 €',           severity: 'warning'  },
};

function formatTimeHM(iso: string): string {
  const d = new Date(iso);
  return d.toTimeString().slice(0, 5);
}
function formatTimestampAbsolute(iso: string): string {
  const d = new Date(iso);
  const dd = String(d.getDate()).padStart(2,'0');
  const months = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
  const mm = months[d.getMonth()];
  const hh = String(d.getHours()).padStart(2,'0');
  const min = String(d.getMinutes()).padStart(2,'0');
  const ss = String(d.getSeconds()).padStart(2,'0');
  return `${dd} ${mm} ${d.getFullYear()} · ${hh}:${min}:${ss}`;
}
function formatRelative(iso: string): string {
  const now = new Date('2026-05-25T14:35:00').getTime();
  const then = new Date(iso).getTime();
  const diff = Math.round((now - then) / 1000);
  if (diff < 60) return `hace ${diff}s`;
  if (diff < 3600) return `hace ${Math.floor(diff/60)}m`;
  if (diff < 86400) return `hace ${Math.floor(diff/3600)}h`;
  return `hace ${Math.floor(diff/86400)}d`;
}
function dayKey(iso: string): string { return iso.slice(0, 10); }
function dayLabel(key: string): string {
  if (key === '2026-05-25') return 'HOY · 25 mayo 2026';
  if (key === '2026-05-24') return 'AYER · 24 mayo 2026';
  const months = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
  const d = new Date(key);
  return `${String(d.getDate()).padStart(2,'0')} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

function chipMatches(chipId: string, evt: AuditEvent): boolean {
  switch (chipId) {
    case 'critical':  return evt.sev === 'critical' || evt.sev === 'danger';
    case 'mine':      return evt.user.name === 'Ana Martínez';
    case 'lasthour':  return evt.ts >= '2026-05-25T13:35:00';
    case 'caja':      return evt.cat === 'caja';
    case 'cancel':    return /cancel/i.test(evt.action);
    case 'reopen':    return /reapertura|reabri/i.test(evt.action);
    case 'auth-fail': return evt.cat === 'auth' && /fallido/i.test(evt.action);
    case 'transfer':  return /transfer/i.test(evt.action);
    default: return true;
  }
}

@Component({
  selector: 'app-registro-auditoria',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './registro-auditoria.page.html',
  styleUrls: ['./registro-auditoria.page.scss'],
})
export class RegistroAuditoriaPage implements OnInit, OnDestroy {
  // ── Constants exposed to template ────────────────────────────
  readonly TABS = TABS;
  readonly CHIPS = CHIPS;
  readonly CATEGORIES = CATEGORIES;
  readonly SAVED_VIEWS = SAVED_VIEWS;
  readonly SEV_LABEL = SEV_LABEL;
  readonly ANOMALIES = ANOMALIES;
  readonly SPARK_HOURLY = SPARK_HOURLY;
  readonly EVENT_INDEX = EVENT_INDEX;

  // ── State ────────────────────────────────────────────────────
  readonly activeTab = signal('all');
  readonly activeChip = signal<string | null>(null);
  readonly filterCategory = signal('all');
  readonly filterSeverity = signal('all');
  readonly filterUser = signal('all');
  readonly filterDevice = signal('all');
  readonly searchRaw = signal('');
  readonly selectedId = signal('evt-002');
  readonly toastMsg = signal<string | null>(null);
  readonly refreshCount = signal(3);
  readonly liveTail = signal(true);
  readonly savedViewsOpen = signal(false);
  readonly activeView = signal('sv-default');
  readonly jsonOpen = signal(false);

  // ── Timers ───────────────────────────────────────────────────
  private refreshTimer?: ReturnType<typeof setInterval>;
  private toastTimer?: ReturnType<typeof setTimeout>;

  // ── Computed: filtered events ─────────────────────────────────
  get filtered(): AuditEvent[] {
    const tab = TABS.find(t => t.id === this.activeTab());
    const chip = this.activeChip();
    const catF = this.filterCategory();
    const sevF = this.filterSeverity();
    const userF = this.filterUser();
    const devF = this.filterDevice();
    const q = this.searchRaw().toLowerCase().trim();

    return EVENTS.filter(e => {
      if (tab?.cat && e.cat !== tab.cat) return false;
      if (chip && !chipMatches(chip, e)) return false;
      if (catF !== 'all' && e.cat !== catF) return false;
      if (sevF !== 'all' && e.sev !== sevF) return false;
      if (userF !== 'all' && e.user.name !== userF) return false;
      if (devF !== 'all' && e.device !== devF) return false;
      if (q) {
        const hay = `${e.action} ${e.entityLabel || ''} ${e.ip} ${e.reason || ''} ${e.user.name}`.toLowerCase();
        if (!hay.includes(q)) return false;
      }
      return true;
    });
  }

  // ── Computed: grouped events ─────────────────────────────────
  get grouped(): Array<{ key: string; label: string; events: AuditEvent[] }> {
    const groups: Record<string, { label: string; events: AuditEvent[] }> = {};
    const order: string[] = [];
    for (const e of this.filtered) {
      const key = dayKey(e.ts);
      const label = dayLabel(key);
      if (!groups[key]) { groups[key] = { label, events: [] }; order.push(key); }
      groups[key].events.push(e);
    }
    order.sort((a, b) => b.localeCompare(a));
    return order.map(k => ({ key: k, label: groups[k].label, events: groups[k].events }));
  }

  // ── Computed: tab counts ──────────────────────────────────────
  get tabCounts(): Record<string, number> {
    const counts: Record<string, number> = { all: EVENTS.length };
    for (const tab of TABS) {
      if (!tab.cat) continue;
      counts[tab.id] = EVENTS.filter(e => e.cat === tab.cat).length;
    }
    return counts;
  }

  // ── Computed: chip counts ─────────────────────────────────────
  get chipCounts(): Record<string, number> {
    const counts: Record<string, number> = {};
    for (const c of CHIPS) counts[c.id] = EVENTS.filter(e => chipMatches(c.id, e)).length;
    return counts;
  }

  // ── Computed: KPIs ────────────────────────────────────────────
  get kpiTotal(): number { return EVENTS.filter(e => dayKey(e.ts) === '2026-05-25').length; }
  get kpiCritical(): number { return EVENTS.filter(e => dayKey(e.ts) === '2026-05-25' && (e.sev === 'critical' || e.sev === 'danger')).length; }
  get kpiUsers(): number { return new Set(EVENTS.filter(e => dayKey(e.ts) === '2026-05-25').map(e => e.user.name)).size; }
  get kpiLast(): string { return formatRelative(EVENTS[0].ts); }

  // ── Computed: selected event ──────────────────────────────────
  get selected(): AuditEvent | null { return EVENT_INDEX[this.selectedId()] ?? null; }

  // ── Computed: related events for detail panel ─────────────────
  get relatedTimeline(): AuditEvent[] {
    const sel = this.selected;
    if (!sel) return [];
    const related = (sel.related || []).map(id => EVENT_INDEX[id]).filter(Boolean);
    return [...related, sel].sort((a, b) => a.ts.localeCompare(b.ts));
  }

  get activeViewMeta(): SavedView | undefined {
    return SAVED_VIEWS.find(v => v.id === this.activeView());
  }

  constructor(private readonly router: Router) {}

  ngOnInit(): void {
    this.startRefreshTimer();
  }

  ngOnDestroy(): void {
    if (this.refreshTimer) clearInterval(this.refreshTimer);
    if (this.toastTimer) clearTimeout(this.toastTimer);
  }

  private startRefreshTimer(): void {
    this.refreshTimer = setInterval(() => {
      if (this.liveTail()) this.refreshCount.update(c => (c + 1) % 30);
    }, 1000);
  }

  // ── Actions ───────────────────────────────────────────────────
  goBack(): void { this.router.navigateByUrl('/app/gestion'); }

  selectTab(id: string): void { this.activeTab.set(id); }

  toggleChip(id: string): void {
    this.activeChip.update(c => c === id ? null : id);
  }

  selectEvent(id: string): void { this.selectedId.set(id); }

  resetFilters(): void {
    this.filterCategory.set('all');
    this.filterSeverity.set('all');
    this.filterUser.set('all');
    this.filterDevice.set('all');
    this.searchRaw.set('');
    this.activeChip.set(null);
    this.activeView.set('sv-default');
  }

  applyView(viewId: string): void {
    const v = SAVED_VIEWS.find(s => s.id === viewId);
    if (!v) return;
    this.activeView.set(viewId);
    this.activeTab.set((v.filters['tab'] as string) || 'all');
    this.activeChip.set(v.filters['chip'] ?? null);
    this.filterSeverity.set((v.filters['sev'] as string) || 'all');
    this.filterUser.set((v.filters['user'] as string) || 'all');
    this.savedViewsOpen.set(false);
    this.showToast(`Vista aplicada: ${v.name}`);
  }

  showToast(msg: string): void {
    this.toastMsg.set(msg);
    if (this.toastTimer) clearTimeout(this.toastTimer);
    this.toastTimer = setTimeout(() => this.toastMsg.set(null), 1800);
  }

  copyToClipboard(txt: string): void {
    try { navigator.clipboard?.writeText(txt); } catch (_) {}
    const label = txt.length > 40 ? txt.slice(0, 40) + '…' : txt;
    this.showToast(`Copiado: ${label}`);
  }

  toggleLiveTail(): void { this.liveTail.update(v => !v); }

  exportData(): void { this.showToast('Exportando CSV...'); }

  onSearch(value: string): void { this.searchRaw.set(value); }

  // ── Helpers for template ──────────────────────────────────────
  formatTimeHM = formatTimeHM;
  formatTimestampAbsolute = formatTimestampAbsolute;
  formatRelative = formatRelative;
  makeHash = makeHash;

  getCatStyle(cat: CategoryKey): Record<string, string> {
    const c = CATEGORIES[cat];
    return { background: c.bg, color: c.color };
  }

  getSevClass(sev: SeverityKey): string {
    return `sev-${sev}`;
  }

  getAmountColor(amount: string): string {
    return (amount.startsWith('−') || amount.startsWith('-')) ? '#B64040' : '#1A9E5A';
  }

  isCurrentEvent(eventId: string): boolean {
    return this.selectedId() === eventId;
  }

  buildSparklinePath(): { area: string; line: string } {
    const data = SPARK_HOURLY;
    const max = Math.max(...data, 1);
    const pts = data.map((v, i) => [(i / (data.length - 1)) * 100, 28 - (v / max) * 22]);
    const line = pts.map((p, i) => (i === 0 ? 'M' : 'L') + p[0] + ',' + p[1]).join(' ');
    const area = line + ` L 100,28 L 0,28 Z`;
    return { area, line };
  }

  buildWaterfallData(): Array<{ id: string; x: number; sev: string; action: string; ts: string }> {
    return this.filtered
      .filter(e => dayKey(e.ts) === '2026-05-25')
      .map(e => {
        const d = new Date(e.ts);
        const min = d.getHours() * 60 + d.getMinutes();
        const start = 10 * 60, end = 16 * 60;
        const x = Math.max(0, Math.min(1, (min - start) / (end - start)));
        return { id: e.id, x, sev: e.sev, action: e.action, ts: e.ts };
      });
  }

  closeDropdowns(): void { this.savedViewsOpen.set(false); }

  @HostListener('document:keydown', ['$event'])
  onKeyDown(e: KeyboardEvent): void {
    if (e.key === 'Escape') this.savedViewsOpen.set(false);
    if (e.key === '/' && document.activeElement?.tagName !== 'INPUT') {
      e.preventDefault();
      (document.querySelector('.audit-search-input') as HTMLInputElement | null)?.focus();
    }
  }
}
