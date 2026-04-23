import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';

export type ManagementEntityKey = 'restaurant' | 'users' | 'families' | 'products' | 'zones' | 'taxes' | 'zreports';

export interface ManagementEntity {
  key: ManagementEntityKey;
  label: string;
}

@Component({
  selector: 'app-entity-tabs',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './entity-tabs.component.html',
  styleUrls: ['./entity-tabs.component.scss'],
})
export class EntityTabsComponent {
  @Input() entities: ManagementEntity[] = [];
  @Input() activeEntity: ManagementEntityKey = 'restaurant';
  @Output() selectEntity = new EventEmitter<ManagementEntityKey>();

  isActive(entityKey: ManagementEntityKey): boolean {
    return this.activeEntity === entityKey;
  }

  onSelect(entityKey: ManagementEntityKey): void {
    this.selectEntity.emit(entityKey);
  }
}
