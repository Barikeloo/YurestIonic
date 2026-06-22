import { Component, computed, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { GuestOrderFacade } from '../../facades/guest-order.facade';
import {
  ModifierCatalogItem,
  VariantCatalogItem,
} from '../../models/guest-catalog.models';
import { AddToCartSpec, CartLineModifier } from '../../models/guest-cart.models';
import { AllergenIconPipe, AllergenNamePipe } from '../../pipes/allergen-icon.pipe';
import { DinersStepperComponent } from '../table-status/diners-stepper.component';

@Component({
  selector: 'app-product-detail',
  standalone: true,
  imports: [CommonModule, AllergenIconPipe, AllergenNamePipe, DinersStepperComponent],
  templateUrl: './product-detail.component.html',
  styleUrls: ['./product-detail.component.scss'],
})
export class ProductDetailComponent {
  protected readonly facade = inject(GuestOrderFacade);

  protected readonly selectedVariant = signal<VariantCatalogItem | null>(null);
  protected readonly selectedModifiers = signal<ModifierCatalogItem[]>([]);
  protected readonly notes = signal('');
  protected readonly quantity = signal(1);

  protected readonly product = computed(() => this.facade.selectedProduct());

  protected readonly variantPrice = computed(() => {
    const v = this.selectedVariant();
    const p = this.product();
    if (!p) return 0;
    return v ? v.price_cents : p.price_cents;
  });

  protected readonly modifiersTotal = computed(() =>
    this.selectedModifiers().reduce((s, m) => s + m.price_cents, 0),
  );

  protected readonly unitPrice = computed(() => this.variantPrice() + this.modifiersTotal());

  protected readonly totalPrice = computed(() => this.unitPrice() * this.quantity());

  protected readonly hasRequiredMods = computed(() =>
    (this.product()?.modifiers ?? []).some((m) => m.is_required),
  );

  protected readonly canAdd = computed(() => {
    const p = this.product();
    if (!p) return false;
    const requiredMods = p.modifiers.filter((m) => m.is_required && m.selection_type === 'single');
    return requiredMods.every((rm) =>
      this.selectedModifiers().some((sm) => sm.id === rm.id),
    );
  });

  selectVariant(v: VariantCatalogItem): void {
    this.selectedVariant.set(v);
  }

  toggleModifier(mod: ModifierCatalogItem): void {
    const current = this.selectedModifiers();
    const exists = current.some((m) => m.id === mod.id);
    if (exists) {
      this.selectedModifiers.set(current.filter((m) => m.id !== mod.id));
    } else {
      this.selectedModifiers.set([...current, mod]);
    }
  }

  isModifierSelected(id: string): boolean {
    return this.selectedModifiers().some((m) => m.id === id);
  }

  setQuantity(n: number): void {
    this.quantity.set(n);
  }

  addToCart(): void {
    const p = this.product();
    if (!p || !this.canAdd()) return;

    const v = this.selectedVariant();
    const mods: CartLineModifier[] = this.selectedModifiers().map((m) => ({
      id: m.id,
      name: m.name,
      price: m.price_cents,
    }));

    const spec: AddToCartSpec = {
      productId: p.id,
      name: v ? `${p.name} (${v.name})` : p.name,
      variantId: v?.id,
      variantName: v?.name,
      modifiers: mods,
      notes: this.notes() || undefined,
      unitPrice: this.unitPrice(),
      quantity: this.quantity(),
    };

    this.facade.addToCart(spec);
  }
}
