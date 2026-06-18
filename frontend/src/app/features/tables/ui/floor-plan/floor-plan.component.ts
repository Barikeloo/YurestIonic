import { Component, computed, input, output } from '@angular/core';
import { TableWithStatus } from '../../facades/mesas.facade';
import { OrderStatus } from '../../../../core/enums/order-status.enum';

export interface MergedGroupInfo {
  groupId: string;
  tables: TableWithStatus[];
  anchor: TableWithStatus;
}

@Component({
  selector: 'app-floor-plan',
  standalone: true,
  templateUrl: './floor-plan.component.html',
  styleUrls: ['./floor-plan.component.scss'],
})
export class FloorPlanComponent {
  readonly tables                = input.required<TableWithStatus[]>();
  readonly selectedTableId       = input<string | null>(null);
  readonly isMergeMode           = input(false);
  readonly mergeSelectedTableIds = input<string[]>([]);
  readonly dragTargetTableId     = input<string | null>(null);
  readonly draggingSourceTableId = input<string | null>(null);
  readonly mergeAnchorTableIds   = input<string[]>([]);

  readonly tableSelected    = output<TableWithStatus>();
  readonly tableDragStarted = output<{ table: TableWithStatus; event: PointerEvent }>();

  protected readonly OrderStatus = OrderStatus;

  protected readonly placedTables = computed(() =>
    this.tables().filter(t => t.layout != null && !t.merged_table_group_id)
  );

  protected readonly placedMergedGroups = computed(() => {
    const anchorIds = new Set(this.mergeAnchorTableIds());
    const groups = new Map<string, TableWithStatus[]>();

    for (const t of this.tables()) {
      if (t.merged_table_group_id && t.layout != null) {
        const gid = t.merged_table_group_id;
        if (!groups.has(gid)) groups.set(gid, []);
        groups.get(gid)!.push(t);
      }
    }

    const result: MergedGroupInfo[] = [];
    for (const [groupId, tables] of groups) {
      if (tables.length < 2) continue;
      // Use the explicitly tracked target table as anchor; fallback to first
      const anchor = tables.find(t => anchorIds.has(t.id)) ?? tables[0];
      result.push({ groupId, tables, anchor });
    }
    return result;
  });

  protected readonly unpositionedCount = computed(() =>
    this.tables().filter(t => t.layout == null).length
  );

  protected statusClass(t: TableWithStatus): string {
    if (t.status === OrderStatus.TO_CHARGE) return 'st-charge';
    if (t.occupied) return 'st-open';
    return 'st-free';
  }

  protected mergedGroupStatus(tables: TableWithStatus[]): string {
    if (tables.some(t => t.status === OrderStatus.TO_CHARGE)) return 'st-charge';
    if (tables.some(t => t.occupied)) return 'st-open';
    return 'st-free';
  }

  protected groupHasCharge(tables: TableWithStatus[]): boolean {
    return tables.some(t => t.status === OrderStatus.TO_CHARGE);
  }

  protected mergedGroupName(tables: TableWithStatus[]): string {
    return tables.map(t => t.name).join(' + ');
  }

  protected isMergeSelected(t: TableWithStatus): boolean {
    return this.mergeSelectedTableIds().includes(t.id);
  }

  protected isGroupSelected(g: MergedGroupInfo): boolean {
    const sel = this.selectedTableId();
    return g.tables.some(t => t.id === sel);
  }

  protected tableNameY(t: TableWithStatus): number {
    const h = t.layout!.shape === 'circle' ? t.layout!.width : t.layout!.height;
    return h / 2 + (t.diners ? -4 : 4);
  }

  protected dinersY(t: TableWithStatus): number {
    const h = t.layout!.shape === 'circle' ? t.layout!.width : t.layout!.height;
    return h / 2 + 10;
  }

  protected anchorH(g: MergedGroupInfo): number {
    const lyt = g.anchor.layout!;
    return lyt.shape === 'circle' ? lyt.width : lyt.height;
  }

  protected onTableClick(t: TableWithStatus): void {
    this.tableSelected.emit(t);
  }

  protected onGroupClick(group: MergedGroupInfo): void {
    this.tableSelected.emit(group.anchor);
  }

  protected onTablePointerDown(event: PointerEvent, t: TableWithStatus): void {
    if (event.button !== 0) return;
    if (this.isMergeMode()) return;
    this.tableDragStarted.emit({ table: t, event });
  }

  protected onGroupPointerDown(event: PointerEvent, group: MergedGroupInfo): void {
    if (event.button !== 0) return;
    if (this.isMergeMode()) return;
    this.tableDragStarted.emit({ table: group.anchor, event });
  }
}
