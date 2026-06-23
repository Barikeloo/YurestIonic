import { computed, inject, Injectable, signal } from '@angular/core';
import { Subject, takeUntil } from 'rxjs';
import {
  DiscountType,
  LoyaltyCustomer,
  LoyaltyCustomerDetail,
  LoyaltyCustomerList,
  LoyaltyOffer,
  LoyaltyOfferForm,
  LoyaltyService,
  LoyaltyStats,
} from '../../../../services/loyalty.service';

@Injectable()
export class GestionLoyaltyFacade {
  private readonly loyaltyService = inject(LoyaltyService);
  private readonly destroy$ = new Subject<void>();

  private readonly _stats = signal<LoyaltyStats | null>(null);
  private readonly _customers = signal<LoyaltyCustomerList | null>(null);
  private readonly _selectedCustomer = signal<LoyaltyCustomerDetail | null>(null);
  private readonly _offers = signal<LoyaltyOffer[]>([]);
  private readonly _loading = signal(false);
  private readonly _loadingDetail = signal(false);
  private readonly _savingOffer = signal(false);
  private readonly _search = signal('');
  private readonly _page = signal(1);
  private readonly _tab = signal<'customers' | 'offers'>('customers');

  readonly stats = this._stats.asReadonly();
  readonly customers = this._customers.asReadonly();
  readonly selectedCustomer = this._selectedCustomer.asReadonly();
  readonly offers = this._offers.asReadonly();
  readonly loading = this._loading.asReadonly();
  readonly loadingDetail = this._loadingDetail.asReadonly();
  readonly savingOffer = this._savingOffer.asReadonly();
  readonly search = this._search.asReadonly();
  readonly page = this._page.asReadonly();
  readonly tab = this._tab.asReadonly();

  readonly retentionRate = computed(() => {
    const s = this._stats();
    if (!s || s.total_customers === 0) return 0;
    return Math.round((s.returning_customers / s.total_customers) * 100);
  });

  setTab(t: 'customers' | 'offers'): void {
    this._tab.set(t);
    if (t === 'offers' && this._offers().length === 0) this.loadOffers();
  }

  loadAll(): void {
    this._loading.set(true);
    this.loyaltyService.getStats().pipe(takeUntil(this.destroy$)).subscribe({
      next: (stats) => this._stats.set(stats),
      error: () => {},
    });
    this.loadCustomers();
  }

  loadOffers(): void {
    this.loyaltyService.getOffers().pipe(takeUntil(this.destroy$)).subscribe({
      next: (offers) => this._offers.set(offers),
      error: () => {},
    });
  }

  createOffer(form: LoyaltyOfferForm): void {
    this._savingOffer.set(true);
    this.loyaltyService.createOffer(form).pipe(takeUntil(this.destroy$)).subscribe({
      next: () => { this._savingOffer.set(false); this.loadOffers(); },
      error: () => this._savingOffer.set(false),
    });
  }

  toggleOfferActive(offer: LoyaltyOffer): void {
    this.loyaltyService.updateOffer(offer.id, { active: !offer.active })
      .pipe(takeUntil(this.destroy$))
      .subscribe({ next: () => this.loadOffers(), error: () => {} });
  }

  deleteOffer(id: string): void {
    this.loyaltyService.deleteOffer(id).pipe(takeUntil(this.destroy$)).subscribe({
      next: () => this.loadOffers(),
      error: () => {},
    });
  }

  discountLabel(offer: LoyaltyOffer): string {
    if (offer.discount_type === 'percent') return `${offer.discount_value}% descuento`;
    if (offer.discount_type === 'fixed_cents') return `${(offer.discount_value / 100).toFixed(2)}€ dto.`;
    return `×${(offer.discount_value / 10).toFixed(1)} puntos`;
  }

  loadCustomers(): void {
    this._loading.set(true);
    this.loyaltyService
      .getCustomers(this._page(), this._search() || undefined)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (list) => {
          this._customers.set(list);
          this._loading.set(false);
        },
        error: () => this._loading.set(false),
      });
  }

  setSearch(term: string): void {
    this._search.set(term);
    this._page.set(1);
    this.loadCustomers();
  }

  setPage(p: number): void {
    this._page.set(p);
    this.loadCustomers();
  }

  selectCustomer(customer: LoyaltyCustomer): void {
    this._loadingDetail.set(true);
    this._selectedCustomer.set(null);
    this.loyaltyService.getCustomer(customer.id).pipe(takeUntil(this.destroy$)).subscribe({
      next: (detail) => {
        this._selectedCustomer.set(detail);
        this._loadingDetail.set(false);
      },
      error: () => this._loadingDetail.set(false),
    });
  }

  closeDetail(): void {
    this._selectedCustomer.set(null);
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }
}
