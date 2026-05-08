import { computed, inject, Injectable, Signal, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { AuthService, AuthUser, QuickAccessUserResponse } from '../../../core/services/auth.service';
import {
  TpvFamilyItem,
  TpvOrder,
  TpvOrderLine,
  TpvProductItem,
  TpvService,
  TpvTaxItem,
} from '../../cash/services/tpv.service';

export interface CartLine {
  productId: string;
  productName: string;
  price: number;
  taxId: string;
  quantity: number;
}

@Injectable()
export class ComandaFacade {
  private readonly tpvService = inject(TpvService);
  private readonly authService = inject(AuthService);

  private readonly _loading = signal<boolean>(true);
  private readonly _order = signal<TpvOrder | null>(null);
  private readonly _existingLines = signal<TpvOrderLine[]>([]);
  private readonly _families = signal<TpvFamilyItem[]>([]);
  private readonly _products = signal<TpvProductItem[]>([]);
  private readonly _taxes = signal<TpvTaxItem[]>([]);
  private readonly _activeFamilyId = signal<string | null>(null);
  private readonly _searchQuery = signal<string>('');
  private readonly _cartLines = signal<CartLine[]>([]);
  private readonly _sendingOrder = signal<boolean>(false);
  private readonly _sendError = signal<string | null>(null);
  private readonly _quickUsers = signal<QuickAccessUserResponse[]>([]);
  private readonly _selectedCloser = signal<QuickAccessUserResponse | null>(null);
  private readonly _closing = signal<boolean>(false);
  private readonly _closeError = signal<string | null>(null);

  private orderId: string | null = null;

  public readonly loading: Signal<boolean> = this._loading.asReadonly();
  public readonly order: Signal<TpvOrder | null> = this._order.asReadonly();
  public readonly existingLines: Signal<TpvOrderLine[]> = this._existingLines.asReadonly();
  public readonly families: Signal<TpvFamilyItem[]> = this._families.asReadonly();
  public readonly products: Signal<TpvProductItem[]> = this._products.asReadonly();
  public readonly taxes: Signal<TpvTaxItem[]> = this._taxes.asReadonly();
  public readonly activeFamilyId: Signal<string | null> = this._activeFamilyId.asReadonly();
  public readonly searchQuery: Signal<string> = this._searchQuery.asReadonly();
  public readonly cartLines: Signal<CartLine[]> = this._cartLines.asReadonly();
  public readonly sendingOrder: Signal<boolean> = this._sendingOrder.asReadonly();
  public readonly sendError: Signal<string | null> = this._sendError.asReadonly();
  public readonly quickUsers: Signal<QuickAccessUserResponse[]> = this._quickUsers.asReadonly();
  public readonly selectedCloser: Signal<QuickAccessUserResponse | null> = this._selectedCloser.asReadonly();
  public readonly closing: Signal<boolean> = this._closing.asReadonly();
  public readonly closeError: Signal<string | null> = this._closeError.asReadonly();

  public readonly cartTotal: Signal<number> = computed(() =>
    this._cartLines().reduce((acc, line) => acc + line.price * line.quantity, 0),
  );

  public readonly cartCount: Signal<number> = computed(() =>
    this._cartLines().reduce((acc, line) => acc + line.quantity, 0),
  );

  public readonly existingSubtotal: Signal<number> = computed(() =>
    this._existingLines().reduce(
      (acc, line) => acc + Math.round((line.price * line.quantity) / (1 + line.tax_percentage / 100)),
      0,
    ),
  );

  public readonly existingTax: Signal<number> = computed(() =>
    this._existingLines().reduce(
      (acc, line) =>
        acc + (line.price * line.quantity - Math.round((line.price * line.quantity) / (1 + line.tax_percentage / 100))),
      0,
    ),
  );

  public readonly existingTotal: Signal<number> = computed(() =>
    this._existingLines().reduce((acc, line) => acc + line.price * line.quantity, 0),
  );

  public readonly orderTotal: Signal<number> = computed(() => this.existingTotal() + this.cartTotal());

  public readonly orderLineCount: Signal<number> = computed(() => {
    const existingCount = this._existingLines().reduce((acc, line) => acc + line.quantity, 0);

    return existingCount + this.cartCount();
  });

  public async loadData(orderId: string | null): Promise<void> {
    this.orderId = orderId;
    this._loading.set(true);

    try {
      const [families, productsResponse, taxes] = await Promise.all([
        firstValueFrom(this.tpvService.listFamilies()),
        firstValueFrom(this.tpvService.listProducts()),
        firstValueFrom(this.tpvService.listTaxes()),
      ]);

      const products = Array.isArray(productsResponse) ? productsResponse : (productsResponse as any).items || [];
      const familiesArray = Array.isArray(families) ? families : (families as any).items || [];

      this._families.set(familiesArray.filter((family: TpvFamilyItem) => family.active));
      this._products.set(products.filter((product: TpvProductItem) => product.active));
      this._taxes.set(taxes);

      if (orderId) {
        const [order, lines] = await Promise.all([
          firstValueFrom(this.tpvService.getOrder(orderId)),
          firstValueFrom(this.tpvService.getOrderLines(orderId)),
        ]);
        this._order.set(order);
        this._existingLines.set(lines);
      }
    } finally {
      this._loading.set(false);
    }
  }

  public setActiveFamily(familyId: string | null): void {
    this._activeFamilyId.set(familyId);
  }

  public setSearchQuery(query: string): void {
    this._searchQuery.set(query);
  }

  // ----- Cart -----
  public addToCart(product: TpvProductItem): void {
    const lines = this._cartLines();
    const existing = lines.find((line) => line.productId === product.id);

    if (existing) {
      this._cartLines.set(
        lines.map((line) =>
          line.productId === product.id ? { ...line, quantity: line.quantity + 1 } : line,
        ),
      );

      return;
    }

    this._cartLines.set([
      ...lines,
      {
        productId: product.id,
        productName: product.name,
        price: product.price,
        taxId: product.tax_id,
        quantity: 1,
      },
    ]);
  }

  public changeQty(target: CartLine, delta: number): void {
    this._cartLines.update((lines) =>
      lines
        .map((line) => (line === target ? { ...line, quantity: line.quantity + delta } : line))
        .filter((line) => line.quantity > 0),
    );
  }

  public clearCart(): void {
    this._cartLines.set([]);
  }

  public async sendComanda(currentUser: AuthUser | null): Promise<boolean> {
    const lines = this._cartLines();

    if (!this.orderId || lines.length === 0 || this._sendingOrder()) {
      return false;
    }

    if (!currentUser?.id) {
      this._sendError.set('No se pudo identificar al usuario actual.');

      return false;
    }

    this._sendingOrder.set(true);
    this._sendError.set(null);

    try {
      for (const line of lines) {
        await firstValueFrom(
          this.tpvService.addOrderLine({
            order_id: this.orderId,
            product_id: line.productId,
            quantity: line.quantity,
          }),
        );
      }

      this._cartLines.set([]);

      return true;
    } catch (err) {
      this._sendError.set(err instanceof Error ? err.message : 'Error al enviar la comanda.');

      return false;
    } finally {
      this._sendingOrder.set(false);
    }
  }

  public async changeDiners(delta: number): Promise<void> {
    const order = this._order();

    if (!this.orderId || !order) {
      return;
    }

    const next = (order.diners ?? 1) + delta;

    if (next < 1) {
      return;
    }

    try {
      const updated = await firstValueFrom(this.tpvService.updateOrder(this.orderId, { diners: next }));
      this._order.set(updated);
    } catch {
    }
  }

  public async deleteLine(line: TpvOrderLine): Promise<void> {
    if (!this.orderId) {
      return;
    }

    try {
      await firstValueFrom(this.tpvService.deleteOrderLine(line.id));
      this._existingLines.update((current) => current.filter((existing) => existing.id !== line.id));
    } catch (err) {
      this._sendError.set(err instanceof Error ? err.message : 'No se pudo eliminar la linea.');
    }
  }

  public async loadQuickUsersForClose(): Promise<void> {
    this._selectedCloser.set(null);
    this._closeError.set(null);

    try {
      const deviceId = this.authService.getDeviceId();
      const users = await firstValueFrom(this.authService.getQuickUsers(deviceId));
      this._quickUsers.set(users);

      if (users.length > 0) {
        this._selectedCloser.set(users[0]);
      }
    } catch {
      this._quickUsers.set([]);
    }
  }

  public selectCloser(user: QuickAccessUserResponse): void {
    this._selectedCloser.set(user);
  }

  public async confirmClose(): Promise<boolean> {
    const closer = this._selectedCloser();

    if (!this.orderId || !closer || this._closing()) {
      return false;
    }

    this._closing.set(true);
    this._closeError.set(null);

    try {
      await firstValueFrom(
        this.tpvService.updateOrder(this.orderId, {
          action: 'mark-to-charge',
          closed_by_user_id: closer.user_uuid,
        }),
      );

      return true;
    } catch (err) {
      this._closeError.set(err instanceof Error ? err.message : 'No se pudo cerrar la cuenta.');

      return false;
    } finally {
      this._closing.set(false);
    }
  }
}
