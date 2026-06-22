import { computed, inject, Injectable, signal } from '@angular/core';
import { CartLine, AddToCartSpec, RetryQueueEntry } from '../models/guest-cart.models';
import { GuestSessionService } from './guest-session.service';

const CART_KEY_PREFIX = 'guest_cart_';
const RETRY_KEY_PREFIX = 'guest_retry_';

@Injectable()
export class GuestCartService {
  private readonly sessionService = inject(GuestSessionService);

  private readonly _cart = signal<CartLine[]>([]);
  private readonly _retryQueue = signal<RetryQueueEntry[]>([]);
  private readonly _isOffline = signal(false);

  readonly cart = this._cart.asReadonly();
  readonly retryQueue = this._retryQueue.asReadonly();
  readonly isOffline = this._isOffline.asReadonly();

  readonly pendingLines = computed(() =>
    this._cart().filter((l) => l.sendStatus !== 'sent'),
  );
  readonly sentLines = computed(() =>
    this._cart().filter((l) => l.sendStatus === 'sent'),
  );
  readonly cartTotal = computed(() =>
    this.pendingLines().reduce((s, l) => s + l.unitPrice * l.quantity, 0),
  );
  readonly itemCount = computed(() =>
    this.pendingLines().reduce((s, l) => s + l.quantity, 0),
  );

  restoreFromStorage(qrToken: string): void {
    try {
      const raw = localStorage.getItem(`${CART_KEY_PREFIX}${qrToken}`);
      if (raw) {
        this._cart.set(JSON.parse(raw) as CartLine[]);
      }
      const retryRaw = localStorage.getItem(`${RETRY_KEY_PREFIX}${qrToken}`);
      if (retryRaw) {
        this._retryQueue.set(JSON.parse(retryRaw) as RetryQueueEntry[]);
      }
    } catch (_) { }
  }

  addLine(spec: AddToCartSpec): string {
    const localId = this.sessionService.generateIdempotencyKey();
    const line: CartLine = {
      localId,
      type: spec.productId ? 'product' : 'menu',
      name: spec.name,
      quantity: spec.quantity,
      productId: spec.productId,
      menuId: spec.menuId,
      variantId: spec.variantId,
      variantName: spec.variantName,
      modifiers: spec.modifiers,
      menuSelections: spec.menuSelections,
      notes: spec.notes,
      unitPrice: spec.unitPrice,
      sendStatus: 'local',
    };
    this._cart.update((c) => [...c, line]);
    return localId;
  }

  removeLocalLine(localId: string): void {
    this._cart.update((c) => c.filter((l) => l.localId !== localId));
  }

  updateBackendLineId(localId: string, backendLineId: string): void {
    this._cart.update((c) =>
      c.map((l) => (l.localId === localId ? { ...l, backendLineId, sendStatus: 'pending' as const } : l)),
    );
  }

  markLinesAsSent(backendLineIds: string[]): void {
    this._cart.update((c) =>
      c.map((l) =>
        l.backendLineId && backendLineIds.includes(l.backendLineId)
          ? { ...l, sendStatus: 'sent' as const }
          : l,
      ),
    );
  }

  addRetryEntry(entry: RetryQueueEntry): void {
    this._retryQueue.update((q) => [...q, entry]);
  }

  removeRetryEntry(idempotencyKey: string): void {
    this._retryQueue.update((q) => q.filter((e) => e.idempotencyKey !== idempotencyKey));
  }

  setOffline(offline: boolean): void {
    this._isOffline.set(offline);
  }

  persist(qrToken: string): void {
    localStorage.setItem(`${CART_KEY_PREFIX}${qrToken}`, JSON.stringify(this._cart()));
    localStorage.setItem(`${RETRY_KEY_PREFIX}${qrToken}`, JSON.stringify(this._retryQueue()));
  }

  clear(qrToken: string): void {
    this._cart.set([]);
    this._retryQueue.set([]);
    localStorage.removeItem(`${CART_KEY_PREFIX}${qrToken}`);
    localStorage.removeItem(`${RETRY_KEY_PREFIX}${qrToken}`);
  }
}
