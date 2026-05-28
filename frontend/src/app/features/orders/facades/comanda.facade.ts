import { computed, inject, Injectable, Signal, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { AuthService, AuthUser, QuickAccessUserResponse } from '../../../core/services/auth.service';
import {
  TpvFamilyItem,
  TpvMenu,
  TpvOrder,
  TpvOrderLine,
  TpvProductItem,
  TpvService,
  TpvTaxItem,
} from '../../cash/services/tpv.service';

export interface SelectedModifier {
  id: string;
  name: string;
  price: number;
  type: 'extra' | 'accompaniment';
}

export interface CartLine {
  productId: string;
  productName: string;
  price: number;
  taxId: string;
  quantity: number;
  variantId?: string;
  variantName?: string;
  modifiers: SelectedModifier[];
}

export interface MenuSelectionPayload {
  section_id: string;
  product_id: string;
  variant_id: string | null;
  modifiers: SelectedModifier[];
}

export interface CartMenuLineSelection {
  sectionId: string;
  sectionName: string;
  productId: string;
  productName: string;
  variantId: string | null;
  variantName: string | null;
  modifiers: SelectedModifier[];
  extraPrice: number;
}

export interface CartMenuLine {
  menuId: string;
  menuName: string;
  taxId: string;
  /** Precio total de la unidad del menú (base + suplementos + modificadores). */
  unitPrice: number;
  quantity: number;
  selections: CartMenuLineSelection[];
  notes: string | null;
}

@Injectable()
export class ComandaFacade {
  private readonly tpvService = inject(TpvService);
  private readonly authService = inject(AuthService);

  public readonly instanceId = Math.random().toString(36).slice(2, 8);

  constructor() {
    // eslint-disable-next-line no-console
    console.log('[ComandaFacade] CONSTRUCTOR instance', this.instanceId);
  }

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
  private readonly _menus = signal<TpvMenu[]>([]);
  /** 'products' (catálogo clásico) o 'menus' (cards de menu para añadir como línea menu). */
  private readonly _activeCatalog = signal<'products' | 'menus'>('products');
  private readonly _cartMenuLines = signal<CartMenuLine[]>([]);

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
  public readonly menus: Signal<TpvMenu[]> = this._menus.asReadonly();
  public readonly activeCatalog: Signal<'products' | 'menus'> = this._activeCatalog.asReadonly();
  public readonly cartMenuLines: Signal<CartMenuLine[]> = this._cartMenuLines.asReadonly();

  public readonly cartMenusTotal: Signal<number> = computed(() =>
    this._cartMenuLines().reduce((acc, line) => acc + line.unitPrice * line.quantity, 0),
  );

  public readonly cartTotal: Signal<number> = computed(() => {
    const productsTotal = this._cartLines().reduce((acc, line) => {
      const modifierTotal = line.modifiers.reduce((mAcc, m) => mAcc + m.price, 0);
      return acc + (line.price + modifierTotal) * line.quantity;
    }, 0);
    return productsTotal + this.cartMenusTotal();
  });

  public readonly cartCount: Signal<number> = computed(() =>
    this._cartLines().reduce((acc, line) => acc + line.quantity, 0) +
      this._cartMenuLines().reduce((acc, line) => acc + line.quantity, 0),
  );

  public readonly hasPendingCart: Signal<boolean> = computed(
    () => this._cartLines().length > 0 || this._cartMenuLines().length > 0,
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
      const [families, productsResponse, taxes, menus] = await Promise.all([
        firstValueFrom(this.tpvService.listFamilies()),
        firstValueFrom(this.tpvService.listProducts()),
        firstValueFrom(this.tpvService.listTaxes()),
        firstValueFrom(this.tpvService.listMenus()).catch(() => [] as TpvMenu[]),
      ]);

      const products = Array.isArray(productsResponse) ? productsResponse : (productsResponse as any).items || [];
      const familiesArray = Array.isArray(families) ? families : (families as any).items || [];

      const activeFamilies = familiesArray.filter((family: TpvFamilyItem) => family.active);
      const activeFamilyIds = new Set(activeFamilies.map((family: TpvFamilyItem) => family.id));

      this._families.set(activeFamilies);
      this._products.set(products.filter((product: TpvProductItem) => product.active && activeFamilyIds.has(product.family_id)));
      this._taxes.set(taxes);
      this._menus.set(menus);

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

  public setActiveCatalog(catalog: 'products' | 'menus'): void {
    this._activeCatalog.set(catalog);
  }

  /**
   * Añade un menú al cart local (no toca backend). Se enviará junto al resto
   * de líneas cuando el camarero pulse "Enviar comanda".
   */
  public addMenuLine(
    menu: TpvMenu,
    selections: MenuSelectionPayload[],
    notes: string | null,
  ): void {
    if (!this.orderId) {
      return;
    }

    const enriched: CartMenuLineSelection[] = [];
    let unitPrice = menu.price;

    for (const sel of selections) {
      const section = menu.sections.find((s) => s.id === sel.section_id);
      const product = this._products().find((p) => p.id === sel.product_id);
      const item = section?.items.find((it) => it.product_id === sel.product_id);
      const extraPrice = item?.extra_price ?? 0;

      const variant = sel.variant_id
        ? product?.variants?.find((v) => v.id === sel.variant_id) ?? null
        : null;

      const modifiersTotal = sel.modifiers.reduce((acc, m) => acc + m.price, 0);
      unitPrice += extraPrice + modifiersTotal;

      enriched.push({
        sectionId: sel.section_id,
        sectionName: section?.name ?? '',
        productId: sel.product_id,
        productName: product?.name ?? 'Producto',
        variantId: sel.variant_id,
        variantName: variant?.name ?? null,
        modifiers: sel.modifiers,
        extraPrice,
      });
    }

    this._cartMenuLines.update((current) => [
      ...current,
      {
        menuId: menu.id,
        menuName: menu.name,
        taxId: menu.tax_id,
        unitPrice,
        quantity: 1,
        selections: enriched,
        notes,
      },
    ]);
  }

  public removeMenuFromCart(target: CartMenuLine): void {
    this._cartMenuLines.update((lines) => lines.filter((line) => line !== target));
  }

  public changeMenuQty(target: CartMenuLine, delta: number): void {
    this._cartMenuLines.update((lines) =>
      lines
        .map((line) => (line === target ? { ...line, quantity: line.quantity + delta } : line))
        .filter((line) => line.quantity > 0),
    );
  }

  private getCartQuantity(productId: string): number {
    return this._cartLines()
      .filter((line) => line.productId === productId)
      .reduce((acc, line) => acc + line.quantity, 0);
  }

  public canAddToCart(product: TpvProductItem): boolean {
    return product.stock > this.getCartQuantity(product.id);
  }

  public getAvailableStock(product: TpvProductItem): number {
    return Math.max(0, product.stock - this.getCartQuantity(product.id));
  }

  public canIncreaseQty(line: CartLine): boolean {
    const product = this._products().find((p) => p.id === line.productId);
    if (!product) return false;
    return product.stock > this.getCartQuantity(line.productId);
  }

  // ----- Cart -----
  public addToCart(
    product: TpvProductItem,
    config?: { variantId?: string; variantName?: string; variantPrice?: number; modifiers?: SelectedModifier[] },
  ): void {
    if (!this.canAddToCart(product)) {
      return;
    }

    const lines = this._cartLines();

    // Si hay config con variant, buscar línea con mismo product+variant
    const existing = config?.variantId
      ? lines.find((line) => line.productId === product.id && line.variantId === config.variantId)
      : lines.find((line) => line.productId === product.id && !line.variantId);

    if (existing) {
      this._cartLines.set(
        lines.map((line) =>
          line === existing ? { ...line, quantity: line.quantity + 1 } : line,
        ),
      );

      return;
    }

    const price = config?.variantPrice ?? product.price;

    this._cartLines.set([
      ...lines,
      {
        productId: product.id,
        productName: product.name,
        price,
        taxId: product.tax_id,
        quantity: 1,
        variantId: config?.variantId,
        variantName: config?.variantName,
        modifiers: config?.modifiers ?? [],
      },
    ]);
  }

  public changeQty(target: CartLine, delta: number): void {
    if (delta > 0 && !this.canIncreaseQty(target)) {
      return;
    }

    this._cartLines.update((lines) =>
      lines
        .map((line) => (line === target ? { ...line, quantity: line.quantity + delta } : line))
        .filter((line) => line.quantity > 0),
    );
  }

  public clearCart(): void {
    this._cartLines.set([]);
    this._cartMenuLines.set([]);
  }

  public async sendComanda(currentUser: AuthUser | null): Promise<boolean> {
    const productLines = this._cartLines();
    const menuLines = this._cartMenuLines();

    if (!this.orderId || (productLines.length === 0 && menuLines.length === 0) || this._sendingOrder()) {
      return false;
    }

    if (!currentUser?.id) {
      this._sendError.set('No se pudo identificar al usuario actual.');

      return false;
    }

    this._sendingOrder.set(true);
    this._sendError.set(null);

    try {
      const payloadProductLines = productLines.map((line) => ({
        product_id: line.productId,
        quantity: line.quantity,
        variant_id: line.variantId ?? null,
        modifiers: line.modifiers.length > 0 ? line.modifiers : null,
        diner_number: null as number | null,
      }));

      const payloadMenuLines: Array<{ menu_id: string; notes: string | null; selections: Array<{ section_id: string; product_id: string; variant_id: string | null; modifiers: SelectedModifier[] }> }> = [];
      for (const menuLine of menuLines) {
        const mappedSelections = menuLine.selections.map((s) => ({
          section_id: s.sectionId,
          product_id: s.productId,
          variant_id: s.variantId,
          modifiers: s.modifiers,
        }));
        for (let i = 0; i < menuLine.quantity; i++) {
          payloadMenuLines.push({
            menu_id: menuLine.menuId,
            notes: menuLine.notes,
            selections: mappedSelections,
          });
        }
      }

      await firstValueFrom(
        this.tpvService.batchAddLines({
          order_id: this.orderId,
          product_lines: payloadProductLines,
          menu_lines: payloadMenuLines,
        }),
      );

      this._cartLines.set([]);
      this._cartMenuLines.set([]);

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

      this._products.update((products) =>
        products.map((p) =>
          p.id === line.product_id ? { ...p, stock: p.stock + line.quantity } : p,
        ),
      );
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
      await firstValueFrom(this.tpvService.markOrderToCharge(this.orderId, closer.user_uuid));

      return true;
    } catch (err) {
      this._closeError.set(err instanceof Error ? err.message : 'No se pudo cerrar la cuenta.');

      return false;
    } finally {
      this._closing.set(false);
    }
  }
}
