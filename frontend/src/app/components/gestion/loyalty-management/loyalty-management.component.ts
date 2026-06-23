import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { GestionLoyaltyFacade } from '../../../pages/core/gestion/facades/gestion-loyalty.facade';
import { DiscountType, LoyaltyCustomer, LoyaltyOfferForm } from '../../../services/loyalty.service';

@Component({
  selector: 'app-loyalty-management',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './loyalty-management.component.html',
  styleUrls: ['./loyalty-management.component.scss'],
})
export class LoyaltyManagementComponent implements OnInit {
  protected readonly facade = inject(GestionLoyaltyFacade);

  protected readonly showNewOfferForm = signal(false);
  protected readonly offerForm = signal<LoyaltyOfferForm>({
    title: '', discount_type: 'percent', discount_value: 10, min_points: 0,
  });

  ngOnInit(): void {
    this.facade.loadAll();
  }

  openNewOfferForm(): void {
    this.offerForm.set({ title: '', discount_type: 'percent', discount_value: 10, min_points: 0 });
    this.showNewOfferForm.set(true);
  }

  cancelOffer(): void {
    this.showNewOfferForm.set(false);
  }

  saveOffer(): void {
    const f = this.offerForm();
    if (!f.title || !f.discount_value) return;
    this.facade.createOffer(f);
    this.showNewOfferForm.set(false);
  }

  setOfferField(field: keyof LoyaltyOfferForm, value: string | number): void {
    this.offerForm.update((f) => ({ ...f, [field]: value }));
  }

  discountTypeName(t: DiscountType): string {
    return t === 'percent' ? 'Porcentaje' : t === 'fixed_cents' ? 'Importe fijo' : '× Puntos';
  }

  formatCents(cents: number): string {
    return (cents / 100).toLocaleString('es-ES', { style: 'currency', currency: 'EUR' });
  }

  formatDate(date: string | null): string {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
  }

  onSearch(event: Event): void {
    this.facade.setSearch((event.target as HTMLInputElement).value);
  }

  selectCustomer(c: LoyaltyCustomer): void {
    this.facade.selectCustomer(c);
  }
}
