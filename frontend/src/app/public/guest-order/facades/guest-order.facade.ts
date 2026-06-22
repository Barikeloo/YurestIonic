import { computed, inject, Injectable, signal } from '@angular/core';
import { Subject, takeUntil } from 'rxjs';
import { GuestOrderApiService } from '../services/guest-order-api.service';
import { GuestSessionService } from '../services/guest-session.service';
import { GuestCartService } from '../services/guest-cart.service';
import {
  TableStatusResponse,
  IdentityMode,
  OpenTableBody,
  JoinSessionBody,
  OrderStatus,
} from '../models/guest-session.models';
import {
  CatalogResponse,
  FamilyCatalogItem,
  ProductCatalogItem,
  MenuCatalogItem,
} from '../models/guest-catalog.models';
import {
  AddToCartSpec,
  OrderHistoryResponse,
  RoundResult,
  CartApiLine,
} from '../models/guest-cart.models';

export type GuestScreen =
  | 'loading'
  | 'table-status'
  | 'catalog'
  | 'product-detail'
  | 'menu-config'
  | 'cart'
  | 'round-sent'
  | 'history';

const CATALOG_CACHE_PREFIX = 'catalog_';
const CATALOG_POLL_MS = 60_000;

@Injectable()
export class GuestOrderFacade {
  private readonly api = inject(GuestOrderApiService);
  private readonly sessionService = inject(GuestSessionService);
  readonly cart = inject(GuestCartService);

  private readonly destroy$ = new Subject<void>();
  private catalogPollTimer: ReturnType<typeof setInterval> | null = null;

  private readonly _screen = signal<GuestScreen>('loading');
  private readonly _qrToken = signal('');
  private readonly _sessionToken = signal<string | null>(null);
  private readonly _tableStatus = signal<TableStatusResponse | null>(null);
  private readonly _catalog = signal<CatalogResponse | null>(null);
  private readonly _selectedFamily = signal<FamilyCatalogItem | null>(null);
  private readonly _selectedProduct = signal<ProductCatalogItem | null>(null);
  private readonly _selectedMenu = signal<MenuCatalogItem | null>(null);
  private readonly _lastRound = signal<RoundResult | null>(null);
  private readonly _orderHistory = signal<OrderHistoryResponse | null>(null);
  private readonly _guestName = signal<string | null>(null);
  private readonly _identityMode = signal<IdentityMode>('anonymous');
  private readonly _isLoading = signal(false);
  private readonly _errorMessage = signal<string | null>(null);

  readonly screen = this._screen.asReadonly();
  readonly qrToken = this._qrToken.asReadonly();
  readonly sessionToken = this._sessionToken.asReadonly();
  readonly tableStatus = this._tableStatus.asReadonly();
  readonly catalog = this._catalog.asReadonly();
  readonly selectedFamily = this._selectedFamily.asReadonly();
  readonly selectedProduct = this._selectedProduct.asReadonly();
  readonly selectedMenu = this._selectedMenu.asReadonly();
  readonly lastRound = this._lastRound.asReadonly();
  readonly orderHistory = this._orderHistory.asReadonly();
  readonly guestName = this._guestName.asReadonly();
  readonly identityMode = this._identityMode.asReadonly();
  readonly isLoading = this._isLoading.asReadonly();
  readonly errorMessage = this._errorMessage.asReadonly();

  readonly orderStatus = computed((): OrderStatus => this._tableStatus()?.order_status ?? 'none');
  readonly restaurantName = computed(() => this._tableStatus()?.restaurant.name ?? '');
  readonly tableName = computed(() => this._tableStatus()?.table.name ?? '');

  init(token: string): void {
    this._qrToken.set(token);
    this.cart.restoreFromStorage(token);
    this.checkSessionAndLoad(token);

    window.addEventListener('online', this.onOnline);
    window.addEventListener('offline', this.onOffline);
  }

  private checkSessionAndLoad(token: string): void {
    const stored = this.sessionService.getSessionToken(token);

    if (stored) {
      this._isLoading.set(true);
      this.api
        .validateSession(token, stored)
        .pipe(takeUntil(this.destroy$))
        .subscribe({
          next: (res) => {
            this._isLoading.set(false);
            if (res.valid) {
              this._sessionToken.set(stored);
              this._guestName.set(res.guest_name);
              if (res.identity_mode) {
                this._identityMode.set(res.identity_mode);
              }
              this.loadTableStatusAndCatalog(token);
            } else {
              this.sessionService.clearSession(token);
              this.loadTableStatus(token);
            }
          },
          error: () => {
            this._isLoading.set(false);
            this.loadTableStatus(token);
          },
        });
    } else {
      this.loadTableStatus(token);
    }
  }

  private loadTableStatus(token: string): void {
    this._isLoading.set(true);
    this.api
      .getTableStatus(token)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (status) => {
          this._isLoading.set(false);
          this._tableStatus.set(status);
          this._screen.set('table-status');
        },
        error: () => {
          this._isLoading.set(false);
          this._errorMessage.set('No se pudo cargar el estado de la mesa.');
          this._screen.set('table-status');
        },
      });
  }

  private loadTableStatusAndCatalog(token: string): void {
    this._isLoading.set(true);
    this.api
      .getTableStatus(token)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (status) => {
          this._tableStatus.set(status);
          this.loadCatalog(token, () => {
            this._isLoading.set(false);
            this._screen.set('catalog');
          });
        },
        error: () => {
          this._isLoading.set(false);
          this._screen.set('table-status');
        },
      });
  }

  private loadCatalog(token: string, onDone: () => void): void {
    const cacheKey = `${CATALOG_CACHE_PREFIX}${token}`;
    try {
      const cached = localStorage.getItem(cacheKey);
      if (cached) {
        const parsed = JSON.parse(cached) as CatalogResponse;
        this._catalog.set(parsed);
        this.api
          .getCatalogVersion(token)
          .pipe(takeUntil(this.destroy$))
          .subscribe({
            next: (v) => {
              if (v.version !== parsed.version) {
                this.fetchAndCacheCatalog(token, cacheKey, onDone);
              } else {
                onDone();
                this.startCatalogPolling(token, cacheKey);
              }
            },
            error: () => onDone(),
          });
        return;
      }
    } catch (_) { }

    this.fetchAndCacheCatalog(token, cacheKey, onDone);
  }

  private fetchAndCacheCatalog(token: string, cacheKey: string, onDone: () => void): void {
    this.api
      .getCatalog(token)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (catalog) => {
          this._catalog.set(catalog);
          localStorage.setItem(cacheKey, JSON.stringify(catalog));
          onDone();
          this.startCatalogPolling(token, cacheKey);
        },
        error: () => onDone(),
      });
  }

  private startCatalogPolling(token: string, cacheKey: string): void {
    if (this.catalogPollTimer) return;
    this.catalogPollTimer = setInterval(() => {
      const current = this._catalog();
      if (!current) return;
      this.api
        .getCatalogVersion(token)
        .pipe(takeUntil(this.destroy$))
        .subscribe((v) => {
          if (v.version !== current.version) {
            this.fetchAndCacheCatalog(token, cacheKey, () => {});
          }
        });
    }, CATALOG_POLL_MS);
  }

  openTable(form: { dinersCount: number; identityMode: IdentityMode; guestName?: string }): void {
    const token = this._qrToken();
    const sessionToken = this.sessionService.generateSessionToken();
    const body: OpenTableBody = {
      session_token: sessionToken,
      diners_count: form.dinersCount,
      identity_mode: form.identityMode,
      guest_name: form.guestName,
    };

    this._isLoading.set(true);
    this._errorMessage.set(null);
    this.api
      .openTable(token, body)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (res) => {
          this._sessionToken.set(res.session_token);
          this._guestName.set(res.guest_name);
          this._identityMode.set(res.identity_mode);
          this.sessionService.saveSessionToken(token, res.session_token);
          this.loadCatalog(token, () => {
            this._isLoading.set(false);
            this._screen.set('catalog');
          });
        },
        error: (err) => {
          this._isLoading.set(false);
          this._errorMessage.set(err?.error?.error?.message ?? 'Error al abrir la mesa.');
        },
      });
  }

  joinSession(form: { identityMode: IdentityMode; guestName?: string }): void {
    const token = this._qrToken();
    const sessionToken = this.sessionService.generateSessionToken();
    const body: JoinSessionBody = {
      session_token: sessionToken,
      identity_mode: form.identityMode,
      guest_name: form.guestName,
    };

    this._isLoading.set(true);
    this._errorMessage.set(null);
    this.api
      .joinSession(token, body)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (res) => {
          this._sessionToken.set(res.session_token);
          this._guestName.set(res.guest_name);
          this._identityMode.set(res.identity_mode);
          this.sessionService.saveSessionToken(token, res.session_token);
          this.loadCatalog(token, () => {
            this._isLoading.set(false);
            this._screen.set('catalog');
          });
        },
        error: (err) => {
          this._isLoading.set(false);
          this._errorMessage.set(err?.error?.error?.message ?? 'Error al unirse a la mesa.');
        },
      });
  }

  selectFamily(family: FamilyCatalogItem): void {
    this._selectedFamily.set(family);
  }

  openProductDetail(product: ProductCatalogItem): void {
    this._selectedProduct.set(product);
    this._screen.set('product-detail');
  }

  openMenuConfig(menu: MenuCatalogItem): void {
    this._selectedMenu.set(menu);
    this._screen.set('menu-config');
  }

  addToCart(spec: AddToCartSpec): void {
    const localId = this.cart.addLine(spec);
    this.cart.persist(this._qrToken());
    this.syncPendingLine(localId, spec);
    this._screen.set('catalog');
  }

  private syncPendingLine(localId: string, spec: AddToCartSpec): void {
    const token = this._qrToken();
    const sessionToken = this._sessionToken();
    if (!sessionToken) return;

    const apiLine: CartApiLine = {
      product_id: spec.productId,
      menu_id: spec.menuId,
      quantity: spec.quantity,
      variant_id: spec.variantId,
      modifier_ids: spec.modifiers.map((m) => m.id),
      notes: spec.notes,
      menu_selections: spec.menuSelections?.map((s) => ({
        section_id: s.section_id,
        product_id: s.product_id,
        variant_id: s.variant_id,
      })),
    };

    this.api
      .savePendingLines(token, sessionToken, [apiLine])
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (res) => {
          if (res.line_ids[0]) {
            this.cart.updateBackendLineId(localId, res.line_ids[0]);
            this.cart.persist(token);
          }
        },
        error: () => { },
      });
  }

  goToCart(): void {
    this._screen.set('cart');
  }

  goToCatalog(): void {
    this._screen.set('catalog');
  }

  submitRound(lineIds: string[], label?: string): void {
    const token = this._qrToken();
    const sessionToken = this._sessionToken();
    if (!sessionToken) return;

    const idempotencyKey = this.sessionService.generateIdempotencyKey();
    this._isLoading.set(true);

    this.api
      .submitRound(token, sessionToken, {
        line_ids: lineIds,
        idempotency_key: idempotencyKey,
        round_label: label,
      })
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (round) => {
          this._isLoading.set(false);
          this._lastRound.set(round);
          this.cart.markLinesAsSent(lineIds);
          this.cart.persist(token);
          this._screen.set('round-sent');
        },
        error: () => {
          this._isLoading.set(false);
          this.cart.addRetryEntry({
            idempotencyKey,
            lineIds,
            roundLabel: label,
            attemptedAt: new Date().toISOString(),
          });
          this.cart.persist(token);
        },
      });
  }

  goToHistory(): void {
    const token = this._qrToken();
    const sessionToken = this._sessionToken();
    if (!sessionToken) return;

    this._isLoading.set(true);
    this.api
      .getOrderHistory(token, sessionToken)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (history) => {
          this._isLoading.set(false);
          this._orderHistory.set(history);
          this._screen.set('history');
        },
        error: () => {
          this._isLoading.set(false);
        },
      });
  }

  requestCheck(): void {
    const token = this._qrToken();
    const sessionToken = this._sessionToken();
    if (!sessionToken) return;

    this._isLoading.set(true);
    this.api
      .requestCheck(token, sessionToken)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: () => {
          this._isLoading.set(false);
          this._errorMessage.set(null);
        },
        error: () => {
          this._isLoading.set(false);
          this._errorMessage.set('No se pudo enviar la solicitud. Inténtalo de nuevo.');
        },
      });
  }

  navigateBack(): void {
    const current = this._screen();
    if (current === 'product-detail' || current === 'menu-config') {
      this._screen.set('catalog');
    } else if (current === 'cart' || current === 'round-sent') {
      this._screen.set('catalog');
    } else if (current === 'history') {
      this._screen.set('catalog');
    }
  }

  private readonly onOnline = (): void => {
    this.cart.setOffline(false);
    this.processRetryQueue();
  };

  private readonly onOffline = (): void => {
    this.cart.setOffline(true);
  };

  private processRetryQueue(): void {
    const token = this._qrToken();
    const sessionToken = this._sessionToken();
    if (!sessionToken) return;

    const queue = this.cart.retryQueue();
    if (queue.length === 0) return;

    const entry = queue[0];
    this.api
      .submitRound(token, sessionToken, {
        line_ids: entry.lineIds,
        idempotency_key: entry.idempotencyKey,
        round_label: entry.roundLabel,
      })
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (round) => {
          this.cart.removeRetryEntry(entry.idempotencyKey);
          this.cart.markLinesAsSent(entry.lineIds);
          this.cart.persist(token);
          this._lastRound.set(round);
          this.processRetryQueue();
        },
        error: () => { },
      });
  }

  ngOnDestroy(): void {
    window.removeEventListener('online', this.onOnline);
    window.removeEventListener('offline', this.onOffline);
    if (this.catalogPollTimer) {
      clearInterval(this.catalogPollTimer);
    }
    this.destroy$.next();
    this.destroy$.complete();
  }
}
