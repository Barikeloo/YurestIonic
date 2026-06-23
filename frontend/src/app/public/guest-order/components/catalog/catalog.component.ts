import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { GuestOrderFacade } from '../../facades/guest-order.facade';
import { FamilyCatalogItem, MenuCatalogItem, ProductCatalogItem } from '../../models/guest-catalog.models';
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

  protected readonly showMenus = computed(() => this.activeTabId() === '__menus__');

  protected readonly menus = computed((): MenuCatalogItem[] => {
    return this.facade.catalog()?.menus ?? [];
  });

  ngOnInit(): void {
    const first = this.tabs()[0];
    if (first) this.activeTabId.set(first.id);
  }

  selectTab(id: string): void {
    this.activeTabId.set(id);
  }

  onSelectProduct(product: ProductCatalogItem): void {
    if (!product.available) return;
    this.facade.openProductDetail(product);
  }

  onSelectMenu(menu: MenuCatalogItem): void {
    this.facade.openMenuConfig(menu);
  }
}
