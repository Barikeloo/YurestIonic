import { computed, inject, Injectable, Signal, signal } from '@angular/core';
import {
  AuditEventApi,
  AuditLogService,
  AuditSavedViewApi,
  CreateAuditSavedViewPayload,
  ListAuditEventsFilters,
} from '../../../../services/audit-log.service';
import { AuditAlertApi, AuditAlertService } from '../../../../services/audit-alert.service';
import { RestaurantService, AdminRestaurantUser } from '../../../../services/restaurant.service';
import { RestaurantContextFacade } from '../../../../core/facades/restaurant-context.facade';
import { AuthService } from '../../../../core/services/auth.service';
import { adaptApiEvent, AuditEvent, UserDirectoryEntry } from '../audit-event.adapter';

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

const SAVED_VIEWS_DEFAULT: SavedViewItem[] = [
  { id: 'sv-default',      name: 'Vista por defecto',       icon: 'list',      filters: { tab: 'all',   chip: null,       sev: 'all', user: 'all' } },
  { id: 'sv-criticos',     name: 'Críticos del turno',      icon: 'alert',     filters: { tab: 'all',   chip: 'critical', sev: 'all', user: 'all' } },
  { id: 'sv-reaperturas',  name: 'Mis reaperturas',         icon: 'lock-open', filters: { tab: 'order', chip: 'reopen',   sev: 'all', user: 'all' } },
  { id: 'sv-cuadres',      name: 'Cuadres con discrepancia', icon: 'wallet',    filters: { tab: 'caja',  chip: null,       sev: 'warning', user: 'all' } },
  { id: 'sv-fallos',       name: 'Fallos de acceso (24h)',  icon: 'shield-off',filters: { tab: 'auth',  chip: 'auth-fail',sev: 'all', user: 'all' } },
];

export interface SavedViewItem {
  id: string;
  name: string;
  icon: string;
  filters: Record<string, string | null>;
}

export interface AuditSavedViewMergedItem {
  id: string;
  name: string;
  icon: string;
  filters: Record<string, string | null>;
  isDefault: boolean;
}

@Injectable()
export class RegistroAuditoriaFacade {
  private readonly auditLogService = inject(AuditLogService);
  private readonly auditAlertService = inject(AuditAlertService);
  private readonly restaurantService = inject(RestaurantService);
  private readonly restaurantContextFacade = inject(RestaurantContextFacade);
  private readonly authService = inject(AuthService);

  // ── Constants exposed ────────────────────────────────────────
  public readonly TABS = TABS;
  public readonly CHIPS = CHIPS;
  public readonly CATEGORIES = CATEGORIES;
  public readonly SEV_LABEL = SEV_LABEL;

  // ── Private state ────────────────────────────────────────────
  private readonly _events = signal<AuditEvent[]>([]);
  private rawApiEvents: AuditEventApi[] = [];
  private readonly _usersDirectory = signal<Record<string, UserDirectoryEntry>>({});
  private readonly _isLoading = signal(false);
  private readonly _isLoadingMore = signal(false);
  private readonly _loadError = signal<string | null>(null);
  private readonly _nextCursor = signal<string | null>(null);
  private readonly _hasMore = signal(false);

  private readonly _selectedId = signal('');
  private readonly _toastMsg = signal<string | null>(null);
  private readonly _refreshCount = signal(0);
  private readonly _liveTail = signal(true);
  private readonly _savedViewsOpen = signal(false);
  private readonly _activeView = signal('sv-default');
  private readonly _jsonOpen = signal(false);

  private readonly _savedViews = signal<AuditSavedViewApi[]>([]);
  private readonly _isLoadingViews = signal(false);

  private readonly _alerts = signal<AuditAlertApi[]>([]);
  private readonly _unreadAlertCount = signal(0);
  private readonly _alertsOpen = signal(false);

  // ── Timers ───────────────────────────────────────────────────
  private refreshTimer?: ReturnType<typeof setInterval>;
  private liveTailTimer?: ReturnType<typeof setInterval>;
  private toastTimer?: ReturnType<typeof setTimeout>;
  private alertTimer?: ReturnType<typeof setInterval>;

  // ── Race guard ───────────────────────────────────────────────
  private loadVersion = 0;

  // ── Public readonly signals ────────────────────────────────
  public readonly events: Signal<AuditEvent[]> = this._events.asReadonly();
  public readonly usersDirectory: Signal<Record<string, UserDirectoryEntry>> = this._usersDirectory.asReadonly();
  public readonly isLoading: Signal<boolean> = this._isLoading.asReadonly();
  public readonly isLoadingMore: Signal<boolean> = this._isLoadingMore.asReadonly();
  public readonly loadError: Signal<string | null> = this._loadError.asReadonly();
  public readonly nextCursor: Signal<string | null> = this._nextCursor.asReadonly();
  public readonly hasMore: Signal<boolean> = this._hasMore.asReadonly();

  public readonly selectedId: Signal<string> = this._selectedId.asReadonly();
  public readonly toastMsg: Signal<string | null> = this._toastMsg.asReadonly();
  public readonly refreshCount: Signal<number> = this._refreshCount.asReadonly();
  public readonly liveTail: Signal<boolean> = this._liveTail.asReadonly();
  public readonly savedViewsOpen: Signal<boolean> = this._savedViewsOpen.asReadonly();
  public readonly activeView: Signal<string> = this._activeView.asReadonly();
  public readonly jsonOpen: Signal<boolean> = this._jsonOpen.asReadonly();

  public readonly savedViews: Signal<AuditSavedViewApi[]> = this._savedViews.asReadonly();
  public readonly isLoadingViews: Signal<boolean> = this._isLoadingViews.asReadonly();

  public readonly alerts: Signal<AuditAlertApi[]> = this._alerts.asReadonly();
  public readonly unreadAlertCount: Signal<number> = this._unreadAlertCount.asReadonly();
  public readonly alertsOpen: Signal<boolean> = this._alertsOpen.asReadonly();

  // ── Computed: merged saved views (defaults + backend) ──────
  public readonly mergedSavedViews = computed<AuditSavedViewMergedItem[]>(() => {
    const backend = this._savedViews().map((v): AuditSavedViewMergedItem => ({
      id: v.uuid,
      name: v.name,
      icon: v.icon ?? 'list',
      filters: v.filters as Record<string, string | null>,
      isDefault: false,
    }));
    const defaults = SAVED_VIEWS_DEFAULT.map((v): AuditSavedViewMergedItem => ({ ...v, isDefault: true }));
    return [...defaults, ...backend];
  });

  // ── Data loading ───────────────────────────────────────────
  public loadInitial(filters: ListAuditEventsFilters): void {
    const version = ++this.loadVersion;
    this._isLoading.set(true);
    this._isLoadingMore.set(false);
    this._loadError.set(null);
    this._nextCursor.set(null);
    this._hasMore.set(false);

    this.auditLogService.list(filters).subscribe({
      next: (resp) => {
        if (version !== this.loadVersion) return;
        this.rawApiEvents = resp.data;
        const dir = this._usersDirectory();
        const adapted = resp.data.map(api => adaptApiEvent(api, dir));
        this._events.set(adapted);
        this._nextCursor.set(resp.next_cursor);
        this._hasMore.set(resp.has_more);
        const currentSelected = this._selectedId();
        const stillVisible = currentSelected && adapted.some(e => e.id === currentSelected);
        if (!stillVisible) {
          this._selectedId.set(adapted[0]?.id ?? '');
        }
        this._isLoading.set(false);
      },
      error: (err: Error) => {
        if (version !== this.loadVersion) return;
        this._loadError.set(err.message);
        this._isLoading.set(false);
      },
    });
  }

  public loadMore(filters: ListAuditEventsFilters): void {
    const cursor = this._nextCursor();
    if (!cursor || this._isLoadingMore()) return;

    const version = ++this.loadVersion;
    this._isLoadingMore.set(true);
    this._loadError.set(null);

    const reqFilters: ListAuditEventsFilters = { ...filters, cursor };
    this.auditLogService.list(reqFilters).subscribe({
      next: (resp) => {
        if (version !== this.loadVersion) return;
        this.rawApiEvents = [...this.rawApiEvents, ...resp.data];
        const dir = this._usersDirectory();
        const adapted = resp.data.map(api => adaptApiEvent(api, dir));
        this._events.update(prev => [...prev, ...adapted]);
        this._nextCursor.set(resp.next_cursor);
        this._hasMore.set(resp.has_more);
        this._isLoadingMore.set(false);
      },
      error: (err: Error) => {
        if (version !== this.loadVersion) return;
        this._loadError.set(err.message);
        this._isLoadingMore.set(false);
      },
    });
  }

  // ── Live tail ──────────────────────────────────────────────
  public startRefreshTimer(): void {
    this.refreshTimer = setInterval(() => {
      if (this._liveTail()) this._refreshCount.update(c => (c + 1) % 30);
    }, 1000);
  }

  public startLiveTailTimer(): void {
    this.liveTailTimer = setInterval(() => this.pollLiveTail(), 5000);
  }

  private pollLiveTail(): void {
    if (!this._liveTail() || this._events().length === 0) return;
    const since = this._events()[0].id;
    const version = ++this.loadVersion;
    this.auditLogService.list({ since }).subscribe({
      next: (resp) => {
        if (version !== this.loadVersion) return;
        if (resp.data.length === 0) return;
        const dir = this._usersDirectory();
        const adapted = resp.data.map(api => adaptApiEvent(api, dir));
        this.rawApiEvents = [...resp.data, ...this.rawApiEvents];
        this._events.update(prev => [...adapted, ...prev]);
      },
      error: () => { /* silencioso */ },
    });
  }

  // ── Users directory ────────────────────────────────────────
  public loadUsersDirectory(): void {
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
        this._usersDirectory.set(dir);
        this.reAdaptEventsWithDirectory();
      },
      error: () => { /* silenciamos */ },
    });
  }

  public reAdaptEventsWithDirectory(): void {
    if (this.rawApiEvents.length === 0) return;
    const dir = this._usersDirectory();
    this._events.set(this.rawApiEvents.map(api => adaptApiEvent(api, dir)));
  }

  // ── Saved Views CRUD ───────────────────────────────────────
  public loadSavedViews(): void {
    this._isLoadingViews.set(true);
    this.auditLogService.listSavedViews().subscribe({
      next: (resp) => {
        this._savedViews.set(resp.data);
        this._isLoadingViews.set(false);
      },
      error: () => {
        this._isLoadingViews.set(false);
      },
    });
  }

  public saveCurrentView(name: string, filters: Record<string, unknown>): void {
    const payload: CreateAuditSavedViewPayload = { name, icon: null, filters };
    this.auditLogService.createSavedView(payload).subscribe({
      next: () => {
        this.loadSavedViews();
        this.showToast(`Vista guardada: ${name}`);
      },
      error: (err: Error) => {
        this.showToast(err.message || 'Error al guardar la vista');
      },
    });
  }

  public deleteView(uuid: string): void {
    this.auditLogService.deleteSavedView(uuid).subscribe({
      next: () => {
        this._savedViews.update(prev => prev.filter(v => v.uuid !== uuid));
        if (this._activeView() === uuid) {
          this._activeView.set('sv-default');
        }
        this.showToast('Vista eliminada');
      },
      error: (err: Error) => {
        this.showToast(err.message || 'Error al eliminar la vista');
      },
    });
  }

  public showToast(msg: string): void {
    this._toastMsg.set(msg);
    if (this.toastTimer) clearTimeout(this.toastTimer);
    this.toastTimer = setTimeout(() => this._toastMsg.set(null), 1800);
  }

  public toggleLiveTail(): void { this._liveTail.update(v => !v); }

  public toggleAlerts(): void { this._alertsOpen.update(v => !v); }

  public closeDropdowns(): void { this._savedViewsOpen.set(false); this._alertsOpen.set(false); }

  public toggleSavedViewsOpen(): void { this._savedViewsOpen.update(v => !v); }

  public setJsonOpen(value: boolean): void { this._jsonOpen.set(value); }

  public setActiveView(value: string): void { this._activeView.set(value); }

  public setSelectedId(value: string): void { this._selectedId.set(value); }

  public setSavedViewsOpen(value: boolean): void { this._savedViewsOpen.set(value); }

  public setAlertsOpen(value: boolean): void { this._alertsOpen.set(value); }

  // ── Lifecycle ────────────────────────────────────────────────
  // ── Alert methods ──────────────────────────────────────────
  public loadAlerts(): void {
    this.auditAlertService.listAlerts().subscribe({
      next: (res) => {
        this._alerts.set(res.data);
        this._unreadAlertCount.set(res.unread_count);
      },
      error: () => {},
    });
  }

  public markAlertRead(uuid: string): void {
    this.auditAlertService.markAsRead(uuid).subscribe({
      next: () => {
        this._alerts.update(prev =>
          prev.map(a => a.uuid === uuid ? { ...a, read_at: new Date().toISOString() } : a),
        );
        this._unreadAlertCount.update(c => Math.max(0, c - 1));
      },
      error: () => {},
    });
  }

  public markAllAlertsRead(): void {
    this.auditAlertService.markAllAsRead().subscribe({
      next: () => {
        const now = new Date().toISOString();
        this._alerts.update(prev => prev.map(a => a.read_at ? a : { ...a, read_at: now }));
        this._unreadAlertCount.set(0);
      },
      error: () => {},
    });
  }

  public selectAlert(alert: AuditAlertApi): void {
    this._alertsOpen.set(false);
    if (alert.audit_log_uuid) {
      this._selectedId.set(alert.audit_log_uuid);
    }
    if (!alert.read_at) {
      this.markAlertRead(alert.uuid);
    }
  }

  public startAlertPolling(): void {
    this.loadAlerts();
    this.alertTimer = setInterval(() => this.loadAlerts(), 30000);
  }

  public stopAlertPolling(): void {
    if (this.alertTimer) clearInterval(this.alertTimer);
  }

  public destroy(): void {
    if (this.refreshTimer) clearInterval(this.refreshTimer);
    if (this.liveTailTimer) clearInterval(this.liveTailTimer);
    if (this.toastTimer) clearTimeout(this.toastTimer);
    if (this.alertTimer) clearInterval(this.alertTimer);
  }
}
