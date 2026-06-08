import { Component, EventEmitter, Input, Output } from '@angular/core';
import { ManagementEntityKey } from '../../../core/enums/management-entity-key.enum';

export { ManagementEntityKey };

export interface ManagementEntity {
  key: ManagementEntityKey;
  label: string;
}

@Component({
  selector: 'app-entity-tabs',
  standalone: true,
  imports: [],
  templateUrl: './entity-tabs.component.html',
  styleUrls: ['./entity-tabs.component.scss'],
})
export class EntityTabsComponent {
  @Input() entities: ManagementEntity[] = [];
  @Input() activeEntity: ManagementEntityKey = ManagementEntityKey.RESTAURANT;
  @Output() selectEntity = new EventEmitter<ManagementEntityKey>();

  isActive(entityKey: ManagementEntityKey): boolean {
    return this.activeEntity === entityKey;
  }

  onSelect(entityKey: ManagementEntityKey): void {
    this.selectEntity.emit(entityKey);
  }
}
