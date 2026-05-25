import { Component, EventEmitter, Input, Output } from '@angular/core';
import { TpvOrderLine } from '../../../features/cash/services/tpv.service';

@Component({
  selector: 'app-line-detail-modal',
  templateUrl: './line-detail-modal.component.html',
  styleUrls: ['./line-detail-modal.component.scss'],
  standalone: true,
})
export class LineDetailModalComponent {
  @Input() public line: TpvOrderLine | null = null;
  @Output() public close = new EventEmitter<void>();

  public isOpen(): boolean {
    return this.line !== null;
  }

  public onOverlayClick(): void {
    this.close.emit();
  }

  public onBoxClick(event: MouseEvent): void {
    event.stopPropagation();
  }

  public lineDisplayName(line: TpvOrderLine): string {
    if (line.product_name) {
      let title = line.product_name;
      if (line.variant_name) {
        title += ` (${line.variant_name})`;
      }

      return title;
    }

    if (line.menu_name) {
      return line.menu_name;
    }

    return 'Producto';
  }

  public isMenuLine(line: TpvOrderLine): boolean {
    return !!line.menu_id;
  }

  public lineAccompaniments(line: TpvOrderLine): Array<{ id: string; name: string; price: number }> {
    return (line.modifiers ?? []).filter((m) => m.type === 'accompaniment');
  }

  public lineExtras(line: TpvOrderLine): Array<{ id: string; name: string; price: number }> {
    return (line.modifiers ?? []).filter((m) => m.type === 'extra');
  }

  public lineLegacyModifiers(line: TpvOrderLine): Array<{ id: string; name: string; price: number }> {
    return (line.modifiers ?? []).filter((m) => m.type !== 'extra' && m.type !== 'accompaniment');
  }

  public getLineTotal(line: TpvOrderLine): number {
    const modTotal = (line.modifiers ?? []).reduce((acc, m) => acc + m.price, 0);

    return (line.price + modTotal) * line.quantity;
  }

  public formatCents(cents: number): string {
    return (cents / 100).toFixed(2).replace('.', ',') + '€';
  }
}
