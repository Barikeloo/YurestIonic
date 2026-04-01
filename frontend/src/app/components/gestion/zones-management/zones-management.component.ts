import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';

export interface TableRow {
  name: string;
}

export interface ZoneRow {
  name: string;
  tables: TableRow[];
}

export interface ZoneFormData {
  name: string;
}

export interface TableFormData {
  name: string;
}

@Component({
  selector: 'app-zones-management',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './zones-management.component.html',
  styleUrls: ['./zones-management.component.scss'],
})
export class ZonesManagementComponent {
  @Input() zones: ZoneRow[] = [];
  @Input() selectedZone: ZoneRow | null = null;
  @Input() selectedZoneIndex: number = 0;
  @Input() selectedTableIndex: number = 0;
  @Input() zoneFormData: ZoneFormData = { name: '' };
  @Input() tableFormData: TableFormData = { name: '' };
  @Output() selectZone = new EventEmitter<number>();
  @Output() createZone = new EventEmitter<void>();
  @Output() deleteZone = new EventEmitter<void>();
  @Output() saveZone = new EventEmitter<void>();
  @Output() selectTable = new EventEmitter<number>();
  @Output() createTable = new EventEmitter<void>();
  @Output() deleteTable = new EventEmitter<void>();
  @Output() saveTable = new EventEmitter<void>();

  isZoneSelected(index: number): boolean {
    return this.selectedZoneIndex === index;
  }

  isTableSelected(index: number): boolean {
    return this.selectedTableIndex === index;
  }

  onSelectZone(index: number): void {
    this.selectZone.emit(index);
  }

  onCreateZone(): void {
    this.createZone.emit();
  }

  onDeleteZone(): void {
    this.deleteZone.emit();
  }

  onSubmitZone(): void {
    this.saveZone.emit();
  }

  onSelectTable(index: number): void {
    this.selectTable.emit(index);
  }

  onCreateTable(): void {
    this.createTable.emit();
  }

  onDeleteTable(): void {
    this.deleteTable.emit();
  }

  onSubmitTable(): void {
    this.saveTable.emit();
  }
}
