import { Component, EventEmitter, Input, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TpvTableItem, TpvZoneItem } from '../../../cash/services/tpv.service';

@Component({
  selector: 'app-transfer-table-modal',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './transfer-table-modal.component.html',
  styleUrls: ['./transfer-table-modal.component.scss'],
})
export class TransferTableModalComponent {
  @Input() isOpen = false;
  @Input() sourceTableId: string | null = null;
  @Input() sourceTableName = '';
  @Input() hasPartialPayments = false;
  @Input() zones: TpvZoneItem[] = [];
  @Input() tables: TpvTableItem[] = [];
  @Input() occupiedTableIds: string[] = [];
  @Input() isLoading = false;
  @Input() errorMessage: string | null = null;

  @Output() closeModal = new EventEmitter<void>();
  @Output() confirmTransfer = new EventEmitter<string>();

  public selectedTableId: string | null = null;

  public getTablesForZone(zoneId: string): TpvTableItem[] {
    return this.tables.filter((t) => t.zone_id === zoneId);
  }

  public getZonesWithTables(): TpvZoneItem[] {
    return this.zones.filter((z) => this.getTablesForZone(z.id).length > 0);
  }

  public isOrigin(table: TpvTableItem): boolean {
    return this.sourceTableId !== null && table.id === this.sourceTableId;
  }

  public isOccupied(table: TpvTableItem): boolean {
    return this.occupiedTableIds.includes(table.id);
  }

  public isSelectable(table: TpvTableItem): boolean {
    return !this.isOrigin(table) && !this.isOccupied(table);
  }

  public selectTable(table: TpvTableItem): void {
    if (!this.isSelectable(table) || this.isLoading) return;
    this.selectedTableId = this.selectedTableId === table.id ? null : table.id;
  }

  public selectedTableName(): string {
    if (this.selectedTableId === null) return '';
    return this.tables.find((t) => t.id === this.selectedTableId)?.name ?? '';
  }

  public onConfirm(): void {
    if (this.selectedTableId === null || this.isLoading) return;
    this.confirmTransfer.emit(this.selectedTableId);
  }

  public onClose(): void {
    if (this.isLoading) return;
    this.closeModal.emit();
  }
}
