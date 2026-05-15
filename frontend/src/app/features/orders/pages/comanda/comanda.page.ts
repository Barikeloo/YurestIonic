
import { Component, inject, OnDestroy, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { AuthService, AuthUser, QuickAccessUserResponse } from '../../../../core/services/auth.service';
import { FilterByPipe, SearchPipe } from '../../../../pipes';
import { TpvOrderLine, TpvProductItem } from '../../../cash/services/tpv.service';
import { CartLine, ComandaFacade } from '../../facades/comanda.facade';
import {
  ProductConfigModalComponent,
  ProductConfigResult,
} from '../../components/product-config-modal/product-config-modal.component';

const AVATAR_COLORS = ['#E8440A', '#1A6FE8', '#1A9E5A', '#9B59B6', '#F39C12', '#E74C3C'];

@Component({
  selector: 'app-comanda',
  templateUrl: './comanda.page.html',
  styleUrls: ['./comanda.page.scss'],
  imports: [FormsModule, FilterByPipe, SearchPipe, ProductConfigModalComponent],
  providers: [ComandaFacade],
})
export class ComandaPage implements OnInit, OnDestroy {
  protected readonly facade = inject(ComandaFacade);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly authService = inject(AuthService);

  public orderId: string | null = null;
  public tableId: string | null = null;
  public currentUser: AuthUser | null = null;
  public closeModalOpen = false;
  public configModalOpen = false;
  public selectedProduct: TpvProductItem | null = null;
  public detailModalOpen = false;
  public selectedLine: CartLine | TpvOrderLine | null = null;

  private readonly destroy$ = new Subject<void>();

  public ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  public async ngOnInit(): Promise<void> {
    this.orderId = this.route.snapshot.queryParamMap.get('orderId');
    this.tableId = this.route.snapshot.queryParamMap.get('tableId');
    this.authService.currentUser$
      .pipe(takeUntil(this.destroy$))
      .subscribe((user) => { this.currentUser = user; });
    await this.facade.loadData(this.orderId);
  }

  public setFamily(familyId: string | null): void {
    this.facade.setActiveFamily(familyId);
  }

  public setSearch(query: string): void {
    this.facade.setSearchQuery(query);
  }

  public addToCart(product: TpvProductItem): void {
    const hasConfig = (product.variants && product.variants.length > 0)
      || (product.modifiers && product.modifiers.length > 0);

    if (hasConfig) {
      this.selectedProduct = product;
      this.configModalOpen = true;
      return;
    }

    this.facade.addToCart(product);
  }

  public onConfigConfirm(result: ProductConfigResult): void {
    if (!this.selectedProduct) return;

    this.facade.addToCart(this.selectedProduct, {
      variantId: result.variantId,
      variantName: result.variantName,
      variantPrice: result.variantPrice,
      modifiers: result.modifiers,
    });

    this.selectedProduct = null;
    this.configModalOpen = false;
  }

  public onConfigClose(): void {
    this.selectedProduct = null;
    this.configModalOpen = false;
  }

  public isOutOfStock(product: TpvProductItem): boolean {
    return !this.facade.canAddToCart(product);
  }

  public getAvailableStock(product: TpvProductItem): number {
    return this.facade.getAvailableStock(product);
  }

  public changeQty(line: CartLine, delta: number): void {
    this.facade.changeQty(line, delta);
  }

  public canIncreaseQty(line: CartLine): boolean {
    return this.facade.canIncreaseQty(line);
  }

  public clearCart(): void {
    this.facade.clearCart();
  }

  public async sendComanda(): Promise<void> {
    const sent = await this.facade.sendComanda(this.currentUser);

    if (sent) {
      void this.router.navigate(['/app/mesas']);
    }
  }

  public changeDiners(delta: number): Promise<void> {
    return this.facade.changeDiners(delta);
  }

  public deleteLine(line: TpvOrderLine): Promise<void> {
    return this.facade.deleteLine(line);
  }

  public async openCloseModal(): Promise<void> {
    this.closeModalOpen = true;
    await this.facade.loadQuickUsersForClose();
  }

  public closeCloseModal(): void {
    this.closeModalOpen = false;
  }

  public selectCloser(user: QuickAccessUserResponse): void {
    this.facade.selectCloser(user);
  }

  public async confirmClose(): Promise<void> {
    const closed = await this.facade.confirmClose();

    if (closed) {
      this.closeModalOpen = false;
      void this.router.navigate(['/app/mesas']);
    }
  }

  public goBack(): void {
    void this.router.navigate(['/app/mesas']);
  }

  public formatCents(cents: number): string {
    return (cents / 100).toFixed(2).replace('.', ',') + '€';
  }

  public formatModifiers(modifiers: { name: string }[]): string {
    return modifiers.map((m) => m.name).join(', ');
  }

  public getLineTotal(line: CartLine): number {
    const modifierTotal = line.modifiers.reduce((acc, m) => acc + m.price, 0);
    return (line.price + modifierTotal) * line.quantity;
  }

  public getExistingLineTotal(line: TpvOrderLine): number {
    const modTotal = (line.modifiers ?? []).reduce((acc, m) => acc + m.price, 0);
    return (line.price + modTotal) * line.quantity;
  }

  public openLineDetail(line: CartLine | TpvOrderLine): void {
    this.selectedLine = line;
    this.detailModalOpen = true;
  }

  public closeLineDetail(): void {
    this.detailModalOpen = false;
    this.selectedLine = null;
  }

  public lineProductName(line: CartLine | TpvOrderLine): string {
    return this.isCartLine(line) ? line.productName : (line.product_name ?? 'Producto');
  }

  public lineVariantName(line: CartLine | TpvOrderLine): string | null {
    const name = this.isCartLine(line) ? line.variantName : line.variant_name;
    return name ?? null;
  }

  public lineModifiers(line: CartLine | TpvOrderLine): { id: string; name: string; price: number; type?: 'extra' | 'accompaniment' }[] {
    return (this.isCartLine(line) ? line.modifiers : line.modifiers) ?? [];
  }

  public lineAccompaniments(line: CartLine | TpvOrderLine): { id: string; name: string; price: number }[] {
    return this.lineModifiers(line).filter((m) => m.type === 'accompaniment');
  }

  public lineExtras(line: CartLine | TpvOrderLine): { id: string; name: string; price: number }[] {
    return this.lineModifiers(line).filter((m) => m.type === 'extra');
  }

  public lineLegacyModifiers(line: CartLine | TpvOrderLine): { id: string; name: string; price: number }[] {
    return this.lineModifiers(line).filter((m) => m.type !== 'extra' && m.type !== 'accompaniment');
  }

  public lineUnitPrice(line: CartLine | TpvOrderLine): number {
    return line.price;
  }

  public lineDetailTotal(line: CartLine | TpvOrderLine): number {
    return this.isCartLine(line) ? this.getLineTotal(line) : this.getExistingLineTotal(line);
  }

  private isCartLine(line: CartLine | TpvOrderLine): line is CartLine {
    return 'productId' in line;
  }

  public getUserInitials(name: string): string {
    const parts = name.trim().split(/\s+/);

    return (parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? parts[0]?.[1] ?? '');
  }

  public avatarColor(index: number): string {
    return AVATAR_COLORS[index % AVATAR_COLORS.length];
  }
}
