import { Component, computed, input, output } from '@angular/core';
import { TableWithStatus } from '../../facades/mesas.facade';
import { OrderStatus } from '../../../../core/enums/order-status.enum';

@Component({
  selector: 'app-floor-plan',
  standalone: true,
  templateUrl: './floor-plan.component.html',
  styleUrls: ['./floor-plan.component.scss'],
})
export class FloorPlanComponent {
  readonly tables         = input.required<TableWithStatus[]>();
  readonly selectedTableId = input<string | null>(null);
  readonly tableSelected  = output<TableWithStatus>();

  protected readonly OrderStatus = OrderStatus;

  protected readonly placedTables = computed(() =>
    this.tables().filter(t => t.layout != null)
  );

  protected readonly unpositionedCount = computed(() =>
    this.tables().filter(t => t.layout == null).length
  );

  protected tableStatusClass(table: TableWithStatus): string {
    if (table.status === OrderStatus.TO_CHARGE) return 'table-to-charge';
    if (table.occupied) return 'table-open';
    return 'table-free';
  }

  protected onTableClick(table: TableWithStatus): void {
    this.tableSelected.emit(table);
  }
}
