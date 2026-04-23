import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { CardComponent } from '../../card/card.component';
import { BadgeComponent } from '../../badge/badge.component';
import { BtnComponent } from '../../btn/btn.component';
import { ZReportModalComponent, ZReportSession } from '../../z-report-modal/z-report-modal.component';

export interface ZReportRow {
  id: string;
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
  selector: 'app-zreports-management',
  templateUrl: './zreports-management.component.html',
  styleUrls: ['./zreports-management.component.scss'],
  imports: [CommonModule, CardComponent, BadgeComponent, BtnComponent, ZReportModalComponent],
  standalone: true,
})
export class ZReportsManagementComponent {
  @Input() reports: ZReportRow[] = [];
  @Input() loading = false;
  @Input() restaurantName = '';
  @Input() deviceName = '';
  @Output() refresh = new EventEmitter<void>();

  public selectedReport: ZReportSession | null = null;
  public showReportModal = false;

  public onViewReport(report: ZReportRow): void {
    this.selectedReport = {
      zNum: report.zNum,
      date: report.date,
      opened: report.opened,
      closed: report.closed,
      tickets: report.tickets,
      diners: report.diners,
      gross: report.gross,
      discounts: report.discounts,
      invitations: report.invitations,
      invValue: report.invValue,
      cancellations: report.cancellations,
      net: report.net,
      initial: report.initial,
      movIn: report.movIn,
      movOut: report.movOut,
      expected: report.expected,
      counted: report.counted,
      diff: report.diff,
      diffReason: report.diffReason,
    };
    this.showReportModal = true;
  }

  public onCloseModal(): void {
    this.showReportModal = false;
    this.selectedReport = null;
  }

  public onRefresh(): void {
    this.refresh.emit();
  }

  public formatCents(cents: number): string {
    return (cents / 100).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  public formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  public getDiffColor(diff: number): string {
    if (diff === 0) return '#1a9e5a';
    return diff > 0 ? '#ff9500' : '#ff4d4d';
  }

  public getDiffText(diff: number): string {
    if (diff === 0) return 'Cuadrada';
    return diff > 0 ? `Sobrante ${this.formatCents(diff)}` : `Faltante ${this.formatCents(Math.abs(diff))}`;
  }
}
