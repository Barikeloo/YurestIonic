import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { CardComponent } from '../card/card.component';
import { BadgeComponent } from '../badge/badge.component';
import { BtnComponent } from '../btn/btn.component';

export interface ZReportSession {
  zNum: number;
  date: string;
  opened: string;
  closed: string;
  tickets: number;
  diners: number;
  gross: number;
  discounts: number;
  invitations: number;
  invValue: number;
  cancellations: number;
  net: number;
  initial: number;
  movIn: number;
  movOut: number;
  expected: number;
  counted: number;
  diff: number;
  diffReason?: string;
}

@Component({
  selector: 'app-z-report-modal',
  templateUrl: './z-report-modal.component.html',
  styleUrls: ['./z-report-modal.component.scss'],
  imports: [CommonModule, CardComponent, BadgeComponent, BtnComponent],
  standalone: true,
})
export class ZReportModalComponent {
  @Input() isOpen = false;
  @Input() session: ZReportSession | null = null;
  @Input() restaurantName = '';
  @Input() deviceName = '';
  @Output() closeModal = new EventEmitter<void>();

  public onClose(): void {
    this.closeModal.emit();
  }

  public formatCents(cents: number): string {
    return (cents / 100).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  public formatNum(cents: number): string {
    return (cents / 100).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  public formatDate(dateString: string): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('es-ES', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  public get isSquared(): boolean {
    return this.session?.diff === 0;
  }

  public get diffColor(): string {
    return this.session?.diff ? (this.session.diff < 0 ? '#ff4d4d' : '#1a9e5a') : '#0d0d0d';
  }

  public get statusText(): string {
    if (!this.session) return '';
    if (this.session.diff === 0) return '✓ Caja cuadrada';
    return `⚠ ${this.session.diff > 0 ? 'Sobrante' : 'Faltante'}`;
  }
}
