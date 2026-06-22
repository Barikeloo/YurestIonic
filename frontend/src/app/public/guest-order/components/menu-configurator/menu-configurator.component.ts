import { Component, computed, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { GuestOrderFacade } from '../../facades/guest-order.facade';
import { MenuItemCatalogItem, MenuSectionCatalogItem } from '../../models/guest-catalog.models';
import { AddToCartSpec, MenuSelection } from '../../models/guest-cart.models';

type SectionSelections = Map<string, MenuItemCatalogItem[]>;

@Component({
  selector: 'app-menu-configurator',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './menu-configurator.component.html',
  styleUrls: ['./menu-configurator.component.scss'],
})
export class MenuConfiguratorComponent {
  protected readonly facade = inject(GuestOrderFacade);

  protected readonly selections = signal<SectionSelections>(new Map());
  protected readonly quantity = signal(1);

  protected readonly menu = computed(() => this.facade.selectedMenu());

  protected readonly extraTotal = computed(() => {
    let total = 0;
    for (const items of this.selections().values()) {
      for (const item of items) {
        total += item.extra_price_cents;
      }
    }
    return total;
  });

  protected readonly unitPrice = computed(() => {
    const m = this.menu();
    return m ? m.price_cents + this.extraTotal() : 0;
  });

  protected readonly totalPrice = computed(() => this.unitPrice() * this.quantity());

  protected readonly sectionErrors = computed((): Map<string, string> => {
    const errors = new Map<string, string>();
    const m = this.menu();
    if (!m) return errors;

    for (const section of m.sections) {
      const selected = this.selections().get(section.id) ?? [];
      if (selected.length < section.min_choices) {
        errors.set(
          section.id,
          section.min_choices === 1
            ? 'Elige al menos una opción'
            : `Elige al menos ${section.min_choices} opciones`,
        );
      }
    }
    return errors;
  });

  protected readonly canAdd = computed(
    () => this.sectionErrors().size === 0 && (this.menu()?.sections.length ?? 0) > 0,
  );

  isSelected(sectionId: string, itemId: string): boolean {
    return (this.selections().get(sectionId) ?? []).some((i) => i.id === itemId);
  }

  toggleItem(section: MenuSectionCatalogItem, item: MenuItemCatalogItem): void {
    const current = new Map(this.selections());
    const sectionSelected = [...(current.get(section.id) ?? [])];

    if (section.max_choices === 1) {
      current.set(section.id, [item]);
    } else {
      const idx = sectionSelected.findIndex((i) => i.id === item.id);
      if (idx >= 0) {
        sectionSelected.splice(idx, 1);
        current.set(section.id, sectionSelected);
      } else if (sectionSelected.length < section.max_choices) {
        current.set(section.id, [...sectionSelected, item]);
      }
    }

    this.selections.set(current);
  }

  selectionLabel(section: MenuSectionCatalogItem): string {
    if (section.min_choices === 0 && section.max_choices === 1) return 'Opcional · elige 1';
    if (section.min_choices === 1 && section.max_choices === 1) return 'Obligatorio · elige 1';
    if (section.min_choices === 0) return `Opcional · hasta ${section.max_choices}`;
    if (section.min_choices === section.max_choices) return `Elige ${section.min_choices}`;
    return `Elige entre ${section.min_choices} y ${section.max_choices}`;
  }

  selectedCount(sectionId: string): number {
    return (this.selections().get(sectionId) ?? []).length;
  }

  setQuantity(n: number): void {
    this.quantity.set(n);
  }

  addToCart(): void {
    const m = this.menu();
    if (!m || !this.canAdd()) return;

    const menuSelections: MenuSelection[] = [];
    for (const section of m.sections) {
      for (const item of this.selections().get(section.id) ?? []) {
        menuSelections.push({
          section_id: section.id,
          product_id: item.product_id,
          product_name: item.product_name,
          variant_id: item.variant_id,
          variant_name: item.variant_name,
          extra_price: item.extra_price_cents,
        });
      }
    }

    const spec: AddToCartSpec = {
      menuId: m.id,
      name: m.name,
      modifiers: [],
      menuSelections,
      unitPrice: this.unitPrice(),
      quantity: this.quantity(),
    };

    this.facade.addToCart(spec);
  }
}
