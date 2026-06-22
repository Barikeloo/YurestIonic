import { Component, computed, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { GuestOrderFacade } from '../../facades/guest-order.facade';
import { CartLine } from '../../models/guest-cart.models';

@Component({
  selector: 'app-guest-cart',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './guest-cart.component.html',
  styleUrls: ['./guest-cart.component.scss'],
})
export class GuestCartComponent {
  protected readonly facade = inject(GuestOrderFacade);

  protected readonly selectedIds = signal<Set<string>>(new Set());
  protected readonly roundLabel = signal('');

  protected readonly pendingLines = computed(() => this.facade.cart.pendingLines());

  protected readonly selectedLines = computed(() =>
    this.pendingLines().filter((l) => this.selectedIds().has(l.localId)),
  );

  protected readonly selectedTotal = computed(() =>
    this.selectedLines().reduce((s, l) => s + l.unitPrice * l.quantity, 0),
  );

  protected readonly canSubmit = computed(
    () => this.selectedLines().length > 0 && !this.facade.isLoading(),
  );

  toggleLine(localId: string): void {
    const next = new Set(this.selectedIds());
    if (next.has(localId)) {
      next.delete(localId);
    } else {
      next.add(localId);
    }
    this.selectedIds.set(next);
  }

  isSelected(localId: string): boolean {
    return this.selectedIds().has(localId);
  }

  selectAll(): void {
    this.selectedIds.set(new Set(this.pendingLines().map((l) => l.localId)));
  }

  clearAll(): void {
    this.selectedIds.set(new Set());
  }

  deleteLine(localId: string, event: Event): void {
    event.stopPropagation();
    this.selectedIds.update((s) => { const n = new Set(s); n.delete(localId); return n; });
    this.facade.deleteLine(localId);
  }

  submitRound(): void {
    const lines = this.selectedLines();
    if (lines.length === 0) return;

    const backendIds = lines
      .filter((l) => l.backendLineId)
      .map((l) => l.backendLineId as string);

    if (backendIds.length === 0) return;

    this.facade.submitRound(backendIds, this.roundLabel() || undefined);
    this.selectedIds.set(new Set());
    this.roundLabel.set('');
  }

  formatLine(line: CartLine): string {
    const parts = [line.name];
    if (line.modifiers.length > 0) {
      parts.push(`+ ${line.modifiers.map((m) => m.name).join(', ')}`);
    }
    if (line.notes) parts.push(`(${line.notes})`);
    return parts.join(' ');
  }
}
