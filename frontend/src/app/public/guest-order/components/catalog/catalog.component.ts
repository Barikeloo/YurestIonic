import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { GuestOrderFacade } from '../../facades/guest-order.facade';
import {
  ALLERGEN_LABELS,
  FamilyCatalogItem,
  MenuCatalogItem,
  ProductCatalogItem,
} from '../../models/guest-catalog.models';
import { GuestIconComponent } from '../ui/guest-icon.component';
import { ProductCardComponent } from './product-card.component';
import { MenuCardComponent } from './menu-card.component';

interface CatalogTab {
  id: string;
  label: string;
  icon: string | null;
  color: string | null;
  type: 'family' | 'menus';
}

@Component({
  selector: 'app-catalog',
  standalone: true,
  imports: [CommonModule, ProductCardComponent, MenuCardComponent, GuestIconComponent],
  templateUrl: './catalog.component.html',
  styleUrls: ['./catalog.component.scss'],
})
export class CatalogComponent implements OnInit {
  protected readonly facade = inject(GuestOrderFacade);

  protected readonly activeTabId = signal<string | null>(null);
  protected readonly blockedAllergens = signal<Set<string>>(new Set());
  protected readonly showAllergenPanel = signal(false);

  protected readonly allergenOptions = Object.entries(ALLERGEN_LABELS).map(
    ([code, { name }]) => ({ code, name }),
  );

  protected readonly tabs = computed((): CatalogTab[] => {
    const catalog = this.facade.catalog();
    if (!catalog) return [];
    const familyTabs: CatalogTab[] = catalog.families
      .filter((f) => f.products.length > 0)
      .map((f) => ({ id: f.id, label: f.name, icon: f.icon, color: f.color, type: 'family' }));
    if (catalog.menus.length > 0) {
      familyTabs.push({ id: '__menus__', label: 'Menús', icon: null, color: null, type: 'menus' });
    }
    return familyTabs;
  });

  protected readonly activeFamily = computed((): FamilyCatalogItem | null => {
    const id = this.activeTabId();
    if (!id || id === '__menus__') return null;
    return this.facade.catalog()?.families.find((f) => f.id === id) ?? null;
  });

  protected readonly filteredProducts = computed((): ProductCatalogItem[] => {
    const family = this.activeFamily();
    if (!family) return [];
    const blocked = this.blockedAllergens();
    if (blocked.size === 0) return family.products;
    return family.products.filter(
      (p) => !p.allergens.some((a) => blocked.has(a)),
    );
  });

  protected readonly hiddenCount = computed((): number => {
    const family = this.activeFamily();
    if (!family || this.blockedAllergens().size === 0) return 0;
    return family.products.length - this.filteredProducts().length;
  });

  protected readonly showMenus = computed(() => this.activeTabId() === '__menus__');
  protected readonly menus = computed((): MenuCatalogItem[] => this.facade.catalog()?.menus ?? []);
  protected readonly activeFilterCount = computed(() => this.blockedAllergens().size);

  ngOnInit(): void {
    const first = this.tabs()[0];
    if (first) this.activeTabId.set(first.id);
  }

  selectTab(id: string): void {
    this.activeTabId.set(id);
  }

  toggleAllergen(code: string): void {
    this.blockedAllergens.update((set) => {
      const next = new Set(set);
      if (next.has(code)) next.delete(code);
      else next.add(code);
      return next;
    });
  }

  isBlocked(code: string): boolean {
    return this.blockedAllergens().has(code);
  }

  clearFilters(): void {
    this.blockedAllergens.set(new Set());
  }

  onSelectProduct(product: ProductCatalogItem): void {
    if (!product.available) return;
    this.facade.openProductDetail(product);
  }

  onSelectMenu(menu: MenuCatalogItem): void {
    this.facade.openMenuConfig(menu);
  }

  onGoToHistory(): void {
    this.facade.clearUnreadRounds();
    this.facade.goToHistory();
  }
}
