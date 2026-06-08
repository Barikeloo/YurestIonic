import { Component, computed, EventEmitter, inject, Input, Output, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import {
  TpvModifierItem,
  TpvProductItem,
  TpvVariantItem,
} from '../../../cash/services/tpv.service';

export interface ProductConfigResult {
  variantId?: string;
  variantName?: string;
  variantPrice: number;
  modifiers: Array<{ id: string; name: string; price: number; type: 'extra' | 'accompaniment' }>;
}

@Component({
  selector: 'app-product-config-modal',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './product-config-modal.component.html',
  styleUrls: ['./product-config-modal.component.scss'],
})
export class ProductConfigModalComponent {
  @Input() isOpen = false;
  @Output() confirm = new EventEmitter<ProductConfigResult>();
  @Output() closeModal = new EventEmitter<void>();

  private readonly _product = signal<TpvProductItem | null>(null);
  private readonly _selectedVariantId = signal<string | null>(null);
  private readonly _selectedModifierIds = signal<Set<string>>(new Set());

  @Input() set product(value: TpvProductItem | null) {
    this._product.set(value);
  }

  get product(): TpvProductItem | null {
    return this._product();
  }

  public readonly selectedVariantId = computed(() => this._selectedVariantId());

  public readonly variants = computed(() => {
    const p = this._product();
    if (!p?.variants) return [];
    return p.variants.filter((v) => v.active);
  });

  public readonly hasVariants = computed(() => this.variants().length > 0);

  public readonly extras = computed(() => {
    const p = this._product();
    if (!p?.modifiers) return [];
    return p.modifiers.filter((m) => m.type === 'extra' && m.active);
  });

  public readonly hasExtras = computed(() => this.extras().length > 0);

  public readonly accompaniments = computed(() => {
    const p = this._product();
    if (!p?.modifiers) return [];
    return p.modifiers.filter((m) => m.type === 'accompaniment' && m.active);
  });

  public readonly hasAccompaniments = computed(() => this.accompaniments().length > 0);

  public readonly hasRequiredAccompaniments = computed(() =>
    this.accompaniments().some((a) => a.is_required),
  );

  public readonly selectedVariant = computed(() => {
    const id = this._selectedVariantId();
    if (!id) return null;
    return this.variants().find((v) => v.id === id) ?? null;
  });

  public readonly totalPrice = computed(() => {
    const p = this._product();
    if (!p) return 0;

    let total = p.price;
    const variant = this.selectedVariant();
    if (variant) {
      total = variant.price;
    }

    const selectedIds = this._selectedModifierIds();
    for (const modifier of [...this.extras(), ...this.accompaniments()]) {
      if (selectedIds.has(modifier.id)) {
        total += modifier.price;
      }
    }

    return total;
  });

  public readonly canConfirm = computed(() => {
    if (!this._product()) return false;
    if (this.hasVariants() && !this._selectedVariantId()) return false;

    const requiredAccompaniments = this.accompaniments().filter((a) => a.is_required);
    if (requiredAccompaniments.length > 0) {
      const selectedIds = this._selectedModifierIds();
      const hasRequired = requiredAccompaniments.some((a) => selectedIds.has(a.id));
      if (!hasRequired) return false;
    }

    return true;
  });

  public ngOnChanges(): void {
    if (!this.isOpen) {
      this._selectedVariantId.set(null);
      this._selectedModifierIds.set(new Set());
      return;
    }

    const defaultModifiers = new Set<string>();
    for (const mod of this.accompaniments()) {
      if (mod.is_required && mod.selection_type === 'single') {
        defaultModifiers.add(mod.id);
        break;

      }
    }
    this._selectedModifierIds.set(defaultModifiers);
  }

  public readonly blockReason = computed(() => {
    if (!this._product()) return '';
    if (this.hasVariants() && !this._selectedVariantId()) {
      return 'Selecciona una opción';
    }
    const required = this.accompaniments().filter((a) => a.is_required);
    if (required.length > 0) {
      const selected = this._selectedModifierIds();
      const hasOne = required.some((a) => selected.has(a.id));
      if (!hasOne) {
        return 'Selecciona un acompañamiento obligatorio';
      }
    }
    return '';
  });

  public onClose(): void {
    this.closeModal.emit();
  }

  public selectVariant(variant: TpvVariantItem): void {
    this._selectedVariantId.set(variant.id);
  }

  public onModifierClick(event: Event, modifier: TpvModifierItem): void {
    event.preventDefault();
    this.toggleModifier(modifier);
  }

  public toggleModifier(modifier: TpvModifierItem): void {
    this._selectedModifierIds.update((current) => {
      const next = new Set(current);
      if (modifier.selection_type === 'single') {
        if (next.has(modifier.id)) {

          next.delete(modifier.id);
          return next;
        }

        const sameGroup = modifier.type === 'extra'
          ? this.extras().filter((m) => m.selection_type === 'single')
          : this.accompaniments().filter((m) => m.selection_type === 'single');
        for (const m of sameGroup) {
          next.delete(m.id);
        }
        next.add(modifier.id);
        return next;
      }

      if (next.has(modifier.id)) {
        next.delete(modifier.id);
      } else {
        next.add(modifier.id);
      }
      return next;
    });
  }

  public isModifierSelected(modifierId: string): boolean {
    return this._selectedModifierIds().has(modifierId);
  }

  public isSingle(modifier: TpvModifierItem): boolean {
    return modifier.selection_type === 'single';
  }

  public onConfirm(): void {
    const variant = this.selectedVariant();
    const selectedIds = this._selectedModifierIds();
    const modifiers: Array<{ id: string; name: string; price: number; type: 'extra' | 'accompaniment' }> = [];

    for (const modifier of [...this.extras(), ...this.accompaniments()]) {
      if (selectedIds.has(modifier.id)) {
        modifiers.push({ id: modifier.id, name: modifier.name, price: modifier.price, type: modifier.type });
      }
    }

    this.confirm.emit({
      variantId: variant?.id,
      variantName: variant?.name,
      variantPrice: variant?.price ?? this._product()?.price ?? 0,
      modifiers,
    });
    this.closeModal.emit();
  }
}
