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
  readonly tables          = input.required<TableWithStatus[]>();
  readonly selectedTableId = input<string | null>(null);
  readonly tableSelected   = output<TableWithStatus>();

  protected readonly OrderStatus = OrderStatus;

  protected readonly placedTables = computed(() =>
    this.tables().filter(t => t.layout != null)
  );

  protected readonly unpositionedCount = computed(() =>
    this.tables().filter(t => t.layout == null).length
  );

  protected statusClass(t: TableWithStatus): string {
    if (t.status === OrderStatus.TO_CHARGE) return 'st-charge';
    if (t.occupied) return 'st-open';
    return 'st-free';
  }

  protected centerY(t: TableWithStatus): number {
    const h = t.layout!.shape === 'circle' ? t.layout!.width : t.layout!.height;
    return h / 2 + (t.diners ? -4 : 4);
  }

  protected dinersY(t: TableWithStatus): number {
    const h = t.layout!.shape === 'circle' ? t.layout!.width : t.layout!.height;
    return h / 2 + 10;
  }

  protected onTableClick(t: TableWithStatus): void {
    this.tableSelected.emit(t);
  }
}
