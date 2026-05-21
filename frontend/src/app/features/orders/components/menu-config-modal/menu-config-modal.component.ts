import { Component, computed, EventEmitter, Input, Output, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { TpvMenu, TpvMenuSection, TpvProductItem } from '../../../cash/services/tpv.service';

export interface MenuSelectionPayload {
  section_id: string;
  product_id: string;
  variant_id: string | null;
  modifiers: Array<{ id: string; name: string; price: number; type: 'extra' | 'accompaniment' }>;
}

export interface MenuConfigResult {
  selections: MenuSelectionPayload[];
  notes: string | null;
}

/**
 * Estado interno por item seleccionado dentro de una sección del menú.
 * Permite seleccionar variante (si el producto tiene) y extras/acompañamientos
 * de forma análoga al product-config-modal, pero anidado por sección.
 */
interface SelectionState {
  productId: string;
  variantId: string | null;
  selectedModifierIds: Set<string>;
}

@Component({
  selector: 'app-menu-config-modal',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './menu-config-modal.component.html',
  styleUrls: ['./menu-config-modal.component.scss'],
})
export class MenuConfigModalComponent {
  @Input() isOpen = false;
  @Output() confirm = new EventEmitter<MenuConfigResult>();
  @Output() closeModal = new EventEmitter<void>();

  private readonly _menu = signal<TpvMenu | null>(null);
  private readonly _products = signal<TpvProductItem[]>([]);
  /** sectionId → lista de selecciones (su orden importa para min/max). */
  private readonly _selectionsBySection = signal<Map<string, SelectionState[]>>(new Map());
  private readonly _notes = signal<string>('');

  @Input() set menu(value: TpvMenu | null) {
    this._menu.set(value);
  }
  get menu(): TpvMenu | null {
    return this._menu();
  }

  @Input() set products(value: TpvProductItem[]) {
    this._products.set(value ?? []);
  }
  get products(): TpvProductItem[] {
    return this._products();
  }

  /** Reset al abrir, para no arrastrar selecciones de la apertura anterior. */
  public ngOnChanges(): void {
    if (!this.isOpen) {
      this._selectionsBySection.set(new Map());
      this._notes.set('');
    }
  }

  // ─── Helpers de datos ──────────────────────────────────────────────────

  public productById(productId: string): TpvProductItem | null {
    return this._products().find((p) => p.id === productId) ?? null;
  }

  public sectionItems(section: TpvMenuSection): TpvProductItem[] {
    // Mantiene el orden de menu items (definidos por el restaurador en el editor).
    return section.items
      .map((it) => this.productById(it.product_id))
      .filter((p): p is TpvProductItem => p !== null && p.active);
  }

  public sectionRuleLabel(section: TpvMenuSection): string {
    const { min_choices: min, max_choices: max } = section;
    if (min === 1 && max === 1) return 'Obligatorio · Elige 1';
    if (min === 0 && max === 1) return 'Opcional · Elige 1 o ninguno';
    return `Elige entre ${min} y ${max}`;
  }

  public sectionExtraPriceForProduct(section: TpvMenuSection, productId: string): number {
    const item = section.items.find((it) => it.product_id === productId);
    return item?.extra_price ?? 0;
  }

  // ─── Selection state ───────────────────────────────────────────────────

  public sectionSelections(sectionId: string): SelectionState[] {
    return this._selectionsBySection().get(sectionId) ?? [];
  }

  public isProductSelected(sectionId: string, productId: string): boolean {
    return this.sectionSelections(sectionId).some((s) => s.productId === productId);
  }

  public toggleProduct(section: TpvMenuSection, product: TpvProductItem): void {
    this._selectionsBySection.update((map) => {
      const next = new Map(map);
      const current = next.get(section.id) ?? [];
      const isMulti = section.max_choices > 1;
      const alreadySelected = current.find((s) => s.productId === product.id);

      if (alreadySelected) {
        // Deseleccionar
        next.set(section.id, current.filter((s) => s.productId !== product.id));
        return next;
      }

      const newSelection: SelectionState = {
        productId: product.id,
        variantId: null,
        selectedModifierIds: new Set(),
      };

      if (!isMulti) {
        // Reemplaza (radio behavior)
        next.set(section.id, [newSelection]);
      } else if (current.length < section.max_choices) {
        next.set(section.id, [...current, newSelection]);
      }
      // si está al límite, no hacemos nada (UI debe deshabilitar)
      return next;
    });
  }

  public selectVariant(sectionId: string, productId: string, variantId: string | null): void {
    this._selectionsBySection.update((map) => {
      const next = new Map(map);
      const list = next.get(sectionId) ?? [];
      next.set(
        sectionId,
        list.map((s) => (s.productId === productId ? { ...s, variantId } : s)),
      );
      return next;
    });
  }

  public toggleModifier(sectionId: string, productId: string, modifierId: string): void {
    this._selectionsBySection.update((map) => {
      const next = new Map(map);
      const list = next.get(sectionId) ?? [];
      next.set(
        sectionId,
        list.map((s) => {
          if (s.productId !== productId) return s;
          const ids = new Set(s.selectedModifierIds);
          if (ids.has(modifierId)) ids.delete(modifierId);
          else ids.add(modifierId);
          return { ...s, selectedModifierIds: ids };
        }),
      );
      return next;
    });
  }

  public isModifierSelected(sectionId: string, productId: string, modifierId: string): boolean {
    const sel = this.sectionSelections(sectionId).find((s) => s.productId === productId);
    return sel?.selectedModifierIds.has(modifierId) ?? false;
  }

  public selectionFor(sectionId: string, productId: string): SelectionState | null {
    return this.sectionSelections(sectionId).find((s) => s.productId === productId) ?? null;
  }

  // ─── Cómputos para el footer ───────────────────────────────────────────

  public readonly totalPrice = computed(() => {
    const menu = this._menu();
    if (!menu) return 0;
    let total = menu.price;
    for (const section of menu.sections) {
      const selections = this.sectionSelections(section.id);
      for (const sel of selections) {
        const product = this.productById(sel.productId);
        if (!product) continue;
        total += this.sectionExtraPriceForProduct(section, sel.productId);
        for (const mod of product.modifiers ?? []) {
          if (sel.selectedModifierIds.has(mod.id)) total += mod.price;
        }
      }
    }
    return total;
  });

  public readonly blockReason = computed<string>(() => {
    const menu = this._menu();
    if (!menu) return 'Cargando…';
    for (const section of menu.sections) {
      const count = this.sectionSelections(section.id).length;
      if (count < section.min_choices) {
        return `Falta elegir en "${section.name}" (${count}/${section.min_choices})`;
      }
      if (count > section.max_choices) {
        return `Demasiadas elecciones en "${section.name}"`;
      }
    }
    return '';
  });

  public readonly canConfirm = computed(() => this.blockReason() === '');

  // ─── Notes & actions ───────────────────────────────────────────────────

  public get notes(): string {
    return this._notes();
  }
  public set notes(value: string) {
    this._notes.set(value);
  }

  public onClose(): void {
    this.closeModal.emit();
  }

  public onConfirm(): void {
    const menu = this._menu();
    if (!menu || !this.canConfirm()) return;

    const selections: MenuSelectionPayload[] = [];
    for (const section of menu.sections) {
      for (const sel of this.sectionSelections(section.id)) {
        const product = this.productById(sel.productId);
        const modifiers: MenuSelectionPayload['modifiers'] = [];
        for (const mod of product?.modifiers ?? []) {
          if (sel.selectedModifierIds.has(mod.id)) {
            modifiers.push({ id: mod.id, name: mod.name, price: mod.price, type: mod.type });
          }
        }
        selections.push({
          section_id: section.id,
          product_id: sel.productId,
          variant_id: sel.variantId,
          modifiers,
        });
      }
    }

    this.confirm.emit({ selections, notes: this._notes().trim() || null });
  }
}
