import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { GestionLoyaltyFacade } from '../../../pages/core/gestion/facades/gestion-loyalty.facade';
import { LoyaltyCustomer } from '../../../services/loyalty.service';

@Component({
  selector: 'app-loyalty-management',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './loyalty-management.component.html',
  styleUrls: ['./loyalty-management.component.scss'],
})
export class LoyaltyManagementComponent implements OnInit {
  protected readonly facade = inject(GestionLoyaltyFacade);

  ngOnInit(): void {
    this.facade.loadAll();
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
