import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CardComponent } from '../card/card.component';
import { BtnComponent } from '../btn/btn.component';
import { BadgeComponent } from '../badge/badge.component';

export interface PendingTable {
  order_id: string;
  table_name: string;
  diners: number;
  opened_at: string;
  total: number;
}

@Component({
  selector: 'app-mesas-abiertas',
  templateUrl: './mesas-abiertas.component.html',
  styleUrls: ['./mesas-abiertas.component.scss'],
  imports: [CommonModule, CardComponent, BtnComponent, BadgeComponent],
  standalone: true,
})
export class MesasAbiertasComponent {
  @Input() mesas: PendingTable[] = [];
  @Output() cobrar = new EventEmitter<PendingTable>();

  public formatCents(cents: number): string {
    return (cents / 100).toFixed(2);
  }

  public formatDate(dateString: string): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
  }
}
