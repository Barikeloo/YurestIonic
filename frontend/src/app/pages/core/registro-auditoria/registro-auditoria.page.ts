import { Component, OnInit, OnDestroy, signal, computed, effect, inject, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';

import { AuditCategoryApi, AuditLogService, AuditSeverityApi, ListAuditEventsFilters } from '../../../services/audit-log.service';
import { RestaurantService, AdminRestaurantUser } from '../../../services/restaurant.service';
import { RestaurantContextFacade } from '../../../core/facades/restaurant-context.facade';
import { AuthService } from '../../../core/services/auth.service';
import { adaptApiEvent, AuditEvent, UserDirectoryEntry } from './audit-event.adapter';
import { RegistroAuditoriaFacade } from './facades/registro-auditoria.facade';
import { SaveViewModalComponent } from '../../../components/modals/save-view-modal/save-view-modal.component';

const SEARCH_DEBOUNCE_MS = 350;
const MIN_SEARCH_CHARS = 2;
const UUID_REGEX = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

function isoToday(): string { return new Date().toISOString().slice(0, 10); }

type CategoryKey = 'order' | 'caja' | 'sale' | 'table' | 'catalog' | 'auth' | 'config' | 'system';
type SeverityKey = 'info' | 'warning' | 'danger' | 'critical' | 'success';

interface Category { label: string; color: string; bg: string; }
interface Chip { id: string; label: string; icon: string; }
interface Tab { id: string; label: string; cat: CategoryKey | null; }

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
  imports: [CommonModule, FormsModule, SaveViewModalComponent],
  templateUrl: './registro-auditoria.page.html',
  styleUrls: ['./registro-auditoria.page.scss'],
  providers: [RegistroAuditoriaFacade],
})
export class RegistroAuditoriaPage implements OnInit, OnDestroy {
  // ── Constants ──────────────────────────────────────────────
  readonly TABS = TABS;
  readonly CHIPS = CHIPS;
  readonly CATEGORIES = CATEGORIES;
  readonly SEV_LABEL = SEV_LABEL;
  readonly SPARK_HOURLY = SPARK_HOURLY;
  readonly ANOMALIES = ANOMALIES;

  // ── Facade proxy: data signals ─────────────────────────────
  get events() { return this.facade.events; }
  get isLoading() { return this.facade.isLoading; }
  get isLoadingMore() { return this.facade.isLoadingMore; }
  get loadError() { return this.facade.loadError; }
  get nextCursor() { return this.facade.nextCursor; }
  get hasMore() { return this.facade.hasMore; }
  get usersDirectory() { return this.facade.usersDirectory; }
  get alerts() { return this.facade.alerts; }
  get unreadAlertCount() { return this.facade.unreadAlertCount; }
  get alertsOpen() { return this.facade.alertsOpen; }

  readonly eventIndex = computed<Record<string, AuditEvent>>(() => {
    const idx: Record<string, AuditEvent> = {};
    for (const e of this.events()) idx[e.id] = e;
    return idx;
  });

  // ── Local UI / filter signals ────────────────────────────────
  readonly activeTab = signal('all');
  readonly activeChip = signal<string | null>(null);
  readonly filterCategory = signal('all');
  readonly filterSeverity = signal('all');
  readonly filterUser = signal('all');
  readonly filterDevice = signal('all');
  readonly dateFrom = signal<string>(isoToday());
  readonly dateTo = signal<string>(isoToday());
  readonly searchRaw = signal('');
  readonly searchDebounced = signal('');
  readonly selectedId = signal('');
  readonly toastMsg = signal<string | null>(null);
  readonly refreshCount = signal(0);
  readonly liveTail = signal(true);
  readonly savedViewsOpen = signal(false);
  readonly activeView = signal('sv-default');
  readonly jsonOpen = signal(false);
  readonly saveViewModalOpen = signal(false);

  private searchDebounceTimer?: ReturnType<typeof setTimeout>;

  // ── Computed: users & devices dropdowns ───────────────────────
  readonly usersDropdownOptions = computed<Array<{ uuid: string; name: string }>>(() => {
    const dir = this.usersDirectory();
    return Object.entries(dir)
      .map(([uuid, entry]) => ({ uuid, name: entry.name }))
      .sort((a, b) => a.name.localeCompare(b.name, 'es'));
  });

  readonly devicesDropdownOptions = computed<string[]>(() => {
    const set = new Set<string>();
    for (const e of this.events()) {
      if (e.device && e.device !== '—') set.add(e.device);
    }
    return Array.from(set).sort();
  });

  // ── Computed: server filters ───────────────────────────────
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

  // ── Presentation getters ───────────────────────────────────
  get filtered(): AuditEvent[] {
    const chip = this.activeChip();
    if (!chip) return this.events();
    return this.events().filter((e: AuditEvent) => chipMatches(chip, e));
  }

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

  get tabCounts(): Record<string, number> {
    const all = this.events();
    const counts: Record<string, number> = { all: all.length };
    for (const tab of TABS) {
      if (!tab.cat) continue;
      counts[tab.id] = all.filter((e: AuditEvent) => e.cat === tab.cat).length;
    }
    return counts;
  }

  get chipCounts(): Record<string, number> {
    const all = this.events();
    const counts: Record<string, number> = {};
    for (const c of CHIPS) counts[c.id] = all.filter((e: AuditEvent) => chipMatches(c.id, e)).length;
    return counts;
  }

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

  get selected(): AuditEvent | null { return this.eventIndex()[this.selectedId()] ?? null; }

  get relatedTimeline(): AuditEvent[] {
    const sel = this.selected;
    if (!sel) return [];
    const idx = this.eventIndex();
    const related = (sel.related || []).map(id => idx[id]).filter(Boolean);
    return [...related, sel].sort((a, b) => a.ts.localeCompare(b.ts));
  }

  get activeViewMeta() {
    return this.facade.mergedSavedViews().find(v => v.id === this.activeView());
  }

  protected readonly facade = inject(RegistroAuditoriaFacade);
  private readonly router = inject(Router);

  constructor() {
    effect(() => {
      const filters = this.serverFilters();
      this.facade.loadInitial(filters);
    });
  }

  ngOnInit(): void {
    this.facade.startRefreshTimer();
    this.facade.startLiveTailTimer();
    this.facade.startAlertPolling();
    this.facade.loadUsersDirectory();
    this.facade.loadSavedViews();
  }

  ngOnDestroy(): void {
    this.facade.destroy();
    if (this.searchDebounceTimer) clearTimeout(this.searchDebounceTimer);
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
  }

  applyView(viewId: string): void {
    const v = this.facade.mergedSavedViews().find(s => s.id === viewId);
    if (!v) return;
    this.activeView.set(viewId);
    this.activeTab.set((v.filters['tab'] as string) || 'all');
    this.activeChip.set(v.filters['chip'] ?? null);
    this.filterSeverity.set((v.filters['sev'] as string) || 'all');
    this.filterUser.set((v.filters['user'] as string) || 'all');
    this.savedViewsOpen.set(false);
    this.facade.showToast(`Vista aplicada: ${v.name}`);
  }

  openSaveViewModal(): void {
    this.saveViewModalOpen.set(true);
    this.savedViewsOpen.set(false);
  }

  onSaveViewConfirm(name: string): void {
    this.saveViewModalOpen.set(false);
    const filters: Record<string, unknown> = {
      tab: this.activeTab(),
      chip: this.activeChip(),
      sev: this.filterSeverity(),
      user: this.filterUser(),
      device: this.filterDevice(),
      category: this.filterCategory(),
      dateFrom: this.dateFrom(),
      dateTo: this.dateTo(),
      search: this.searchDebounced(),
    };
    this.facade.saveCurrentView(name.trim(), filters);
  }

  deleteView(uuid: string, event: Event): void {
    event.stopPropagation();
    this.facade.deleteView(uuid);
  }

  showToast(msg: string): void {
    this.facade.showToast(msg);
  }

  copyToClipboard(txt: string): void {
    try { navigator.clipboard?.writeText(txt); } catch (_) {}
    const label = txt.length > 40 ? txt.slice(0, 40) + '…' : txt;
    this.facade.showToast(`Copiado: ${label}`);
  }

  toggleLiveTail(): void { this.facade.toggleLiveTail(); }

  toggleAlerts(): void { this.facade.toggleAlerts(); }

  markAlertRead(uuid: string): void { this.facade.markAlertRead(uuid); }

  exportData(): void { this.facade.showToast('Exportando CSV...'); }

  onSearch(value: string): void {
    this.searchRaw.set(value);
    if (this.searchDebounceTimer) clearTimeout(this.searchDebounceTimer);
    this.searchDebounceTimer = setTimeout(() => {
      this.searchDebounced.set(value);
    }, SEARCH_DEBOUNCE_MS);
  }

  loadMore(): void {
    this.facade.loadMore(this.serverFilters());
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
