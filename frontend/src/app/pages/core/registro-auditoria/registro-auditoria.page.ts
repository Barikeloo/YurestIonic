import { Component, OnInit, OnDestroy, signal, computed, effect, inject, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';

import { AuditCategoryApi, AuditLogService, AuditSeverityApi, ListAuditEventsFilters } from '../../../services/audit-log.service';
import { RestaurantService, AdminRestaurantUser } from '../../../services/restaurant.service';
import { RestaurantContextFacade } from '../../../core/facades/restaurant-context.facade';
import { AuthService } from '../../../core/services/auth.service';
import { adaptApiEvent, AuditEvent, UserDirectoryEntry } from './audit-event.adapter';

const SEARCH_DEBOUNCE_MS = 350;
const MIN_SEARCH_CHARS = 2;
const UUID_REGEX = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

function isoToday(): string { return new Date().toISOString().slice(0, 10); }

type CategoryKey = 'order' | 'caja' | 'sale' | 'table' | 'catalog' | 'auth' | 'config' | 'system';
type SeverityKey = 'info' | 'warning' | 'danger' | 'critical' | 'success';

interface Category { label: string; color: string; bg: string; }
interface Chip { id: string; label: string; icon: string; }
interface Tab { id: string; label: string; cat: CategoryKey | null; }
interface SavedView { id: string; name: string; icon: string; filters: Record<string, string | null>; }

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

  // ── State ────────────────────────────────────────────────────
  readonly events = signal<AuditEvent[]>([]);
  readonly eventIndex = computed<Record<string, AuditEvent>>(() => {
    const idx: Record<string, AuditEvent> = {};
    for (const e of this.events()) idx[e.id] = e;
    return idx;
  });
  readonly isLoading = signal(false);
  readonly isLoadingMore = signal(false);
  readonly loadError = signal<string | null>(null);
  readonly nextCursor = signal<string | null>(null);
  readonly hasMore = signal(false);

  // Directorio de usuarios del restaurante actual (uuid -> {name, role}) cargado una vez al iniciar.
  readonly usersDirectory = signal<Record<string, UserDirectoryEntry>>({});
  // Lista para el dropdown de usuarios: ordenada por nombre.
  readonly usersDropdownOptions = computed<Array<{ uuid: string; name: string }>>(() => {
    const dir = this.usersDirectory();
    return Object.entries(dir)
      .map(([uuid, entry]) => ({ uuid, name: entry.name }))
      .sort((a, b) => a.name.localeCompare(b.name, 'es'));
  });
  // Lista de dispositivos derivada de los eventos cargados (únicos, ordenados).
  readonly devicesDropdownOptions = computed<string[]>(() => {
    const set = new Set<string>();
    for (const e of this.events()) {
      if (e.device && e.device !== '—') set.add(e.device);
    }
    return Array.from(set).sort();
  });

  readonly activeTab = signal('all');
  readonly activeChip = signal<string | null>(null);
  readonly filterCategory = signal('all');
  readonly filterSeverity = signal('all');
  readonly filterUser = signal('all');
  readonly filterDevice = signal('all');
  readonly dateFrom = signal<string>(isoToday());
  readonly dateTo = signal<string>(isoToday());
  readonly searchRaw = signal('');
  /** Versión debounceada de searchRaw que dispara refetch al backend. */
  readonly searchDebounced = signal('');
  readonly selectedId = signal<string>('');
  readonly toastMsg = signal<string | null>(null);
  readonly refreshCount = signal(0);
  readonly liveTail = signal(true);
  readonly savedViewsOpen = signal(false);
  readonly activeView = signal('sv-default');
  readonly jsonOpen = signal(false);

  // ── Timers ───────────────────────────────────────────────────
  private refreshTimer?: ReturnType<typeof setInterval>;
  private liveTailTimer?: ReturnType<typeof setInterval>;
  private toastTimer?: ReturnType<typeof setTimeout>;
  private searchDebounceTimer?: ReturnType<typeof setTimeout>;

  // ── Race-condition guard for in-flight requests ──────────────
  private loadVersion = 0;

  // ── Cache de respuesta cruda del backend para poder re-adaptar cuando llega el directorio ──
  private rawApiEvents: import('../../../services/audit-log.service').AuditEventApi[] = [];

  // ── Computed: filtros traducidos a parámetros server-side ────
  // Tab > dropdown: si la pestaña fija una categoría, prevalece sobre el dropdown.
  // Chip se mantiene en cliente porque combina criterios o usa data no expuesta.
  readonly serverFilters = computed<ListAuditEventsFilters>(() => {
    const tab = TABS.find(t => t.id === this.activeTab());
    const tabCategory = tab?.cat ?? null;
    const dropdownCategory = this.filterCategory();
    const category = tabCategory ?? (dropdownCategory !== 'all' ? dropdownCategory : null);

    const severity = this.filterSeverity();
    const userId = this.filterUser();
    const deviceId = this.filterDevice();
    const dateFrom = this.dateFrom();
    const dateTo = this.dateTo();
    const search = this.searchDebounced().trim();

    const filters: ListAuditEventsFilters = {};
    if (category) filters.category = category as AuditCategoryApi;
    if (severity !== 'all') filters.severity = severity as AuditSeverityApi;
    if (userId !== 'all' && UUID_REGEX.test(userId)) filters.userId = userId;
    if (deviceId !== 'all') filters.deviceId = deviceId;
    if (dateFrom) filters.dateFrom = dateFrom;
    if (dateTo) filters.dateTo = dateTo;
    if (search.length >= MIN_SEARCH_CHARS) filters.search = search;

    return filters;
  });

  // ── Computed: filtered events ─────────────────────────────────
  // Los filtros principales (categoría, severidad, usuario, dispositivo, búsqueda) ya los aplica
  // el backend. Aquí sólo se aplica el chip, que combina criterios o usa lógica que aún no está
  // expuesta por la API.
  get filtered(): AuditEvent[] {
    const chip = this.activeChip();
    if (!chip) return this.events();
    return this.events().filter((e: AuditEvent) => chipMatches(chip, e));
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
    const all = this.events();
    const counts: Record<string, number> = { all: all.length };
    for (const tab of TABS) {
      if (!tab.cat) continue;
      counts[tab.id] = all.filter((e: AuditEvent) => e.cat === tab.cat).length;
    }
    return counts;
  }

  // ── Computed: chip counts ─────────────────────────────────────
  get chipCounts(): Record<string, number> {
    const all = this.events();
    const counts: Record<string, number> = {};
    for (const c of CHIPS) counts[c.id] = all.filter((e: AuditEvent) => chipMatches(c.id, e)).length;
    return counts;
  }

  // ── Computed: KPIs (sobre los eventos cargados) ────────────────
  private todayKey(): string { return new Date().toISOString().slice(0, 10); }
  get kpiTotal(): number {
    const today = this.todayKey();
    return this.events().filter((e: AuditEvent) => dayKey(e.ts) === today).length;
  }
  get kpiCritical(): number {
    const today = this.todayKey();
    return this.events().filter((e: AuditEvent) => dayKey(e.ts) === today && (e.sev === 'critical' || e.sev === 'danger')).length;
  }
  get kpiUsers(): number {
    const today = this.todayKey();
    return new Set(this.events().filter((e: AuditEvent) => dayKey(e.ts) === today).map((e: AuditEvent) => e.user.name)).size;
  }
  get kpiLast(): string {
    const first = this.events()[0];
    return first ? formatRelative(first.ts) : '—';
  }

  // ── Computed: selected event ──────────────────────────────────
  get selected(): AuditEvent | null { return this.eventIndex()[this.selectedId()] ?? null; }

  // ── Computed: related events for detail panel ─────────────────
  get relatedTimeline(): AuditEvent[] {
    const sel = this.selected;
    if (!sel) return [];
    const idx = this.eventIndex();
    const related = (sel.related || []).map(id => idx[id]).filter(Boolean);
    return [...related, sel].sort((a, b) => a.ts.localeCompare(b.ts));
  }

  get activeViewMeta(): SavedView | undefined {
    return SAVED_VIEWS.find(v => v.id === this.activeView());
  }

  private readonly restaurantService = inject(RestaurantService);
  private readonly restaurantContextFacade = inject(RestaurantContextFacade);
  private readonly authService = inject(AuthService);

  constructor(
    private readonly router: Router,
    private readonly auditLogService: AuditLogService,
  ) {
    // Refetch automático al cambiar cualquier filtro server-side. Se dispara en cuanto el componente
    // monta (con filtros vacíos = última página global) y en cada cambio posterior.
    effect(() => {
      const filters = this.serverFilters();
      this.loadInitial(filters);
    });
  }

  ngOnInit(): void {
    this.startRefreshTimer();
    this.startLiveTailTimer();
    this.loadUsersDirectory();
  }

  private loadUsersDirectory(): void {
    let restaurantUuid = this.restaurantContextFacade.selectedRestaurantUuid;
    if (!restaurantUuid) {
      restaurantUuid = localStorage.getItem('gestion_selected_restaurant_uuid');
    }
    if (!restaurantUuid) {
      restaurantUuid = this.authService.currentUserSnapshot?.restaurantId ?? null;
    }
    if (!restaurantUuid) return;
    this.restaurantService.getRestaurantUsers(restaurantUuid).subscribe({
      next: (resp) => {
        const dir: Record<string, UserDirectoryEntry> = {};
        for (const u of resp.users as AdminRestaurantUser[]) {
          dir[u.uuid] = { name: u.name, role: u.role };
        }
        this.usersDirectory.set(dir);
        // Re-adaptamos los eventos ya cargados para que muestren el nombre real del usuario.
        this.reAdaptEventsWithDirectory();
      },
      error: () => {
        // Silenciamos: si falla, los eventos siguen mostrando 'Usuario desconocido'.
      },
    });
  }

  /** Reaplica el adapter sobre los eventos crudos del backend, ahora con directorio de usuarios. */
  private reAdaptEventsWithDirectory(): void {
    if (this.rawApiEvents.length === 0) return;
    const dir = this.usersDirectory();
    this.events.set(this.rawApiEvents.map(api => adaptApiEvent(api, dir)));
  }

  ngOnDestroy(): void {
    if (this.refreshTimer) clearInterval(this.refreshTimer);
    if (this.liveTailTimer) clearInterval(this.liveTailTimer);
    if (this.toastTimer) clearTimeout(this.toastTimer);
    if (this.searchDebounceTimer) clearTimeout(this.searchDebounceTimer);
  }

  private loadInitial(filters: ListAuditEventsFilters): void {
    const version = ++this.loadVersion;
    this.isLoading.set(true);
    this.isLoadingMore.set(false);
    this.loadError.set(null);
    this.nextCursor.set(null);
    this.hasMore.set(false);

    this.auditLogService.list(filters).subscribe({
      next: (resp) => {
        if (version !== this.loadVersion) return;
        this.rawApiEvents = resp.data;
        const dir = this.usersDirectory();
        const adapted = resp.data.map(api => adaptApiEvent(api, dir));
        this.events.set(adapted);
        this.nextCursor.set(resp.next_cursor);
        this.hasMore.set(resp.has_more);
        const currentSelected = this.selectedId();
        const stillVisible = currentSelected && adapted.some(e => e.id === currentSelected);
        if (!stillVisible) {
          this.selectedId.set(adapted[0]?.id ?? '');
        }
        this.isLoading.set(false);
      },
      error: (err: Error) => {
        if (version !== this.loadVersion) return;
        this.loadError.set(err.message);
        this.isLoading.set(false);
      },
    });
  }

  loadMore(): void {
    const cursor = this.nextCursor();
    if (!cursor || this.isLoadingMore()) return;

    const version = ++this.loadVersion;
    this.isLoadingMore.set(true);
    this.loadError.set(null);

    const filters: ListAuditEventsFilters = { ...this.serverFilters(), cursor };
    this.auditLogService.list(filters).subscribe({
      next: (resp) => {
        if (version !== this.loadVersion) return;
        this.rawApiEvents = [...this.rawApiEvents, ...resp.data];
        const dir = this.usersDirectory();
        const adapted = resp.data.map(api => adaptApiEvent(api, dir));
        this.events.update(prev => [...prev, ...adapted]);
        this.nextCursor.set(resp.next_cursor);
        this.hasMore.set(resp.has_more);
        this.isLoadingMore.set(false);
      },
      error: (err: Error) => {
        if (version !== this.loadVersion) return;
        this.loadError.set(err.message);
        this.isLoadingMore.set(false);
      },
    });
  }

  private startRefreshTimer(): void {
    this.refreshTimer = setInterval(() => {
      if (this.liveTail()) this.refreshCount.update(c => (c + 1) % 30);
    }, 1000);
  }

  private startLiveTailTimer(): void {
    this.liveTailTimer = setInterval(() => this.pollLiveTail(), 5000);
  }

  /** Pide eventos más recientes que el último cargado y los prepende a la lista. */
  private pollLiveTail(): void {
    if (!this.liveTail() || this.events().length === 0) return;
    const since = this.events()[0].id;
    const version = ++this.loadVersion;
    this.auditLogService.list({ since }).subscribe({
      next: (resp) => {
        if (version !== this.loadVersion) return;
        if (resp.data.length === 0) return;
        const dir = this.usersDirectory();
        const adapted = resp.data.map(api => adaptApiEvent(api, dir));
        this.rawApiEvents = [...resp.data, ...this.rawApiEvents];
        this.events.update(prev => [...adapted, ...prev]);
      },
      error: () => { /* silencioso: no interrumpimos la UI por errores de polling */ },
    });
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
    this.dateFrom.set(isoToday());
    this.dateTo.set(isoToday());
    this.searchRaw.set('');
    this.searchDebounced.set('');
    if (this.searchDebounceTimer) clearTimeout(this.searchDebounceTimer);
    this.activeChip.set(null);
    this.activeView.set('sv-default');
    this.nextCursor.set(null);
    this.hasMore.set(false);
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

  onSearch(value: string): void {
    this.searchRaw.set(value);
    if (this.searchDebounceTimer) clearTimeout(this.searchDebounceTimer);
    this.searchDebounceTimer = setTimeout(() => {
      this.searchDebounced.set(value);
    }, SEARCH_DEBOUNCE_MS);
  }

  // ── Helpers for template ──────────────────────────────────────
  formatTimeHM = formatTimeHM;
  formatTimestampAbsolute = formatTimestampAbsolute;
  formatRelative = formatRelative;

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
