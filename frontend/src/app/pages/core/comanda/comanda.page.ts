import { CommonModule } from '@angular/common';
import { Component, OnDestroy, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { firstValueFrom, Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { AuthService, AuthUser, QuickAccessUserResponse } from '../../../services/auth.service';
import { TpvFamilyItem, TpvOrder, TpvOrderLine, TpvProductItem, TpvService, TpvTaxItem } from '../../../services/tpv.service';

interface CartLine {
  productId: string;
  productName: string;
  price: number;
  taxId: string;
  quantity: number;
}

const AVATAR_COLORS = ['#E8440A', '#1A6FE8', '#1A9E5A', '#9B59B6', '#F39C12', '#E74C3C'];

@Component({
  selector: 'app-comanda',
  templateUrl: './comanda.page.html',
  styleUrls: ['./comanda.page.scss'],
  imports: [CommonModule, FormsModule],
})
export class ComandaPage implements OnInit, OnDestroy {
  orderId: string | null = null;
  tableId: string | null = null;

  order: TpvOrder | null = null;
  existingLines: TpvOrderLine[] = [];

  families: TpvFamilyItem[] = [];
  products: TpvProductItem[] = [];
  taxes: TpvTaxItem[] = [];
  taxMap = new Map<string, number>();
  activeFamilyId: string | null = null;
  searchQuery = '';

  cartLines: CartLine[] = [];

  loading = true;
  sendingOrder = false;
  sendError: string | null = null;

  currentUser: AuthUser | null = null;

  closeModalOpen = false;
  quickUsers: QuickAccessUserResponse[] = [];
  selectedCloser: QuickAccessUserResponse | null = null;
  closing = false;
  closeError: string | null = null;

  private readonly destroy$ = new Subject<void>();

  constructor(
    private readonly route: ActivatedRoute,
    private readonly router: Router,
    private readonly tpvService: TpvService,
    private readonly authService: AuthService,
  ) {}

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  async ngOnInit(): Promise<void> {
    this.orderId = this.route.snapshot.queryParamMap.get('orderId');
    this.tableId = this.route.snapshot.queryParamMap.get('tableId');
    this.authService.currentUser$.pipe(takeUntil(this.destroy$)).subscribe((user) => { this.currentUser = user; });
    await this.loadData();
  }

  async loadData(): Promise<void> {
    this.loading = true;
    try {
      const [families, products, taxes] = await Promise.all([
        firstValueFrom(this.tpvService.listFamilies()),
        firstValueFrom(this.tpvService.listProducts()),
        firstValueFrom(this.tpvService.listTaxes()),
      ]);

      this.families = families.filter((f) => f.active);
      this.products = products.filter((p) => p.active);
      this.taxes = taxes;
      for (const tax of taxes) {
        this.taxMap.set(tax.id, tax.percentage);
      }

      if (this.orderId) {
        const [order, lines] = await Promise.all([
          firstValueFrom(this.tpvService.getOrder(this.orderId)),
          firstValueFrom(this.tpvService.getOrderLines(this.orderId)),
        ]);
        this.order = order;
        this.existingLines = lines;
      }
    } finally {
      this.loading = false;
    }
  }

  get filteredProducts(): TpvProductItem[] {
    let result = this.activeFamilyId
      ? this.products.filter((p) => p.family_id === this.activeFamilyId)
      : this.products;

    const q = this.searchQuery.trim().toLowerCase();
    if (q) result = result.filter((p) => p.name.toLowerCase().includes(q));

    return result;
  }

  setFamily(familyId: string | null): void {
    this.activeFamilyId = familyId;
  }

  addToCart(product: TpvProductItem): void {
    const existing = this.cartLines.find((l) => l.productId === product.id);
    if (existing) {
      existing.quantity++;
    } else {
      this.cartLines.push({
        productId: product.id,
        productName: product.name,
        price: product.price,
        taxId: product.tax_id,
        quantity: 1,
      });
    }
  }

  changeQty(line: CartLine, delta: number): void {
    line.quantity += delta;
    if (line.quantity <= 0) {
      this.cartLines = this.cartLines.filter((l) => l !== line);
    }
  }

  get cartTotal(): number {
    return this.cartLines.reduce((acc, l) => acc + l.price * l.quantity, 0);
  }

  get cartCount(): number {
    return this.cartLines.reduce((acc, l) => acc + l.quantity, 0);
  }

  async sendComanda(): Promise<void> {
    if (!this.orderId || this.cartLines.length === 0 || this.sendingOrder) return;

    if (!this.currentUser?.id) {
      this.sendError = 'No se pudo identificar al usuario actual.';
      return;
    }

    this.sendingOrder = true;
    this.sendError = null;

    try {
      for (const line of this.cartLines) {
        await firstValueFrom(
          this.tpvService.addOrderLine({
            order_id: this.orderId,
            product_id: line.productId,
            quantity: line.quantity,
          }),
        );
      }
      this.cartLines = [];
      void this.router.navigate(['/app/mesas']);
      return;
    } catch (err) {
      this.sendError = err instanceof Error ? err.message : 'Error al enviar la comanda.';
    } finally {
      this.sendingOrder = false;
    }
  }

  async changeDiners(delta: number): Promise<void> {
    if (!this.orderId || !this.order) return;
    const next = (this.order.diners ?? 1) + delta;
    if (next < 1) return;
    try {
      const updated = await firstValueFrom(this.tpvService.updateOrder(this.orderId, { diners: next }));
      this.order = updated;
    } catch {
    }
  }

  async deleteLine(line: TpvOrderLine): Promise<void> {
    if (!this.orderId) return;
    try {
      await firstValueFrom(this.tpvService.deleteOrderLine(line.id));
      this.existingLines = this.existingLines.filter((l) => l.id !== line.id);
    } catch (err) {
      this.sendError = err instanceof Error ? err.message : 'No se pudo eliminar la línea.';
    }
  }

  clearCart(): void {
    this.cartLines = [];
  }

  async openCloseModal(): Promise<void> {
    this.selectedCloser = null;
    this.closeError = null;
    this.closeModalOpen = true;

    try {
      const deviceId = this.authService.getDeviceId();
      this.quickUsers = await firstValueFrom(this.authService.getQuickUsers(deviceId));
      if (this.quickUsers.length > 0) this.selectedCloser = this.quickUsers[0];
    } catch {
      this.quickUsers = [];
    }
  }

  closeCloseModal(): void {
    this.closeModalOpen = false;
  }

  selectCloser(user: QuickAccessUserResponse): void {
    this.selectedCloser = user;
  }

  async confirmClose(): Promise<void> {
    if (!this.orderId || !this.selectedCloser || this.closing) return;
    this.closing = true;
    this.closeError = null;

    try {
      await firstValueFrom(
        this.tpvService.updateOrder(this.orderId, {
          action: 'mark-to-charge',
          closed_by_user_id: this.selectedCloser.user_uuid,
        }),
      );
      this.closeModalOpen = false;
      void this.router.navigate(['/app/mesas']);
    } catch (err) {
      this.closeError = err instanceof Error ? err.message : 'No se pudo cerrar la cuenta.';
    } finally {
      this.closing = false;
    }
  }

  goBack(): void {
    void this.router.navigate(['/app/mesas']);
  }

  get existingSubtotal(): number {
    return this.existingLines.reduce(
      (acc, l) => acc + Math.round((l.price * l.quantity) / (1 + l.tax_percentage / 100)),
      0,
    );
  }

  get existingTax(): number {
    return this.existingLines.reduce(
      (acc, l) => acc + (l.price * l.quantity - Math.round((l.price * l.quantity) / (1 + l.tax_percentage / 100))),
      0,
    );
  }

  get existingTotal(): number {
    return this.existingLines.reduce((acc, l) => acc + l.price * l.quantity, 0);
  }

  get orderTotal(): number {
    return this.existingTotal + this.cartTotal;
  }

  get orderLineCount(): number {
    const existingCount = this.existingLines.reduce((acc, l) => acc + l.quantity, 0);
    return existingCount + this.cartCount;
  }

  formatCents(cents: number): string {
    return (cents / 100).toFixed(2).replace('.', ',') + '€';
  }

  getUserInitials(name: string): string {
    const parts = name.trim().split(/\s+/);
    return (parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? parts[0]?.[1] ?? '');
  }

  avatarColor(index: number): string {
    return AVATAR_COLORS[index % AVATAR_COLORS.length];
  }
}
