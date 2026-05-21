import { CdkDragDrop, DragDropModule, moveItemInArray } from '@angular/cdk/drag-drop';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { ToastService } from '../../../core/services/toast.service';
import { ToggleComponent } from '../../../shared/components/toggle/toggle.component';
import {
  CreateMenuPayload,
  MenuDto,
  MenuItemPayload,
  MenuProductOption,
  MenuSectionPayload,
  MenuService,
} from '../../../services/menu.service';
import { FamilyItem, FamilyService } from '../../../services/family.service';
import { ProductItem, ProductService } from '../../../services/product.service';
import { ProductVariantItem, ProductVariantService } from '../../../services/product-variant.service';
import { TaxItem, TaxService } from '../../../services/tax.service';

interface EditorItem {
  productId: string;
  variantId: string | null;
  extraPrice: number;
}

/**
 * Versión enriquecida de MenuProductOption con familyId, necesaria solo
 * para el filtrado por familia en el sidebar del catálogo.
 */
interface CatalogProduct {
  id: string;
  name: string;
  price: number;
  active: boolean;
  familyId: string;
}

interface EditorSection {
  name: string;
  minChoices: number;
  maxChoices: number;
  items: EditorItem[];
}

interface EditorHeader {
  name: string;
  description: string;
  price: number;
  taxId: string;
  validityFrom: string;
  validityTo: string;
  /** Bitmask de días ISO (bit 0 = Lunes ... bit 6 = Domingo) */
  availableDays: number;
  availableFromTime: string;
  availableToTime: string;
  active: boolean;
}

const ISO_WEEKDAYS: Array<{ index: number; bit: number; label: string; short: string }> = [
  { index: 1, bit: 1 << 0, label: 'Lunes', short: 'L' },
  { index: 2, bit: 1 << 1, label: 'Martes', short: 'M' },
  { index: 3, bit: 1 << 2, label: 'Miércoles', short: 'X' },
  { index: 4, bit: 1 << 3, label: 'Jueves', short: 'J' },
  { index: 5, bit: 1 << 4, label: 'Viernes', short: 'V' },
  { index: 6, bit: 1 << 5, label: 'Sábado', short: 'S' },
  { index: 7, bit: 1 << 6, label: 'Domingo', short: 'D' },
];

const DEFAULT_HEADER = (): EditorHeader => ({
  name: '',
  description: '',
  price: 0,
  taxId: '',
  validityFrom: '',
  validityTo: '',
  availableDays: 0b1111111,
  availableFromTime: '',
  availableToTime: '',
  active: true,
});

const DEFAULT_SECTION = (): EditorSection => ({
  name: '',
  minChoices: 1,
  maxChoices: 1,
  items: [],
});

@Component({
  selector: 'app-gestion-menus-editor',
  standalone: true,
  imports: [FormsModule, DragDropModule, ToggleComponent],
  templateUrl: './gestion-menus-editor.page.html',
  styleUrls: ['./gestion-menus-editor.page.scss'],
})
export class GestionMenusEditorPage implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly menuService = inject(MenuService);
  private readonly productService = inject(ProductService);
  private readonly productVariantService = inject(ProductVariantService);
  private readonly taxService = inject(TaxService);
  private readonly familyService = inject(FamilyService);
  private readonly toastService = inject(ToastService);

  public readonly weekdays = ISO_WEEKDAYS;

  protected readonly mode = signal<'create' | 'edit'>('create');
  protected readonly menuId = signal<string | null>(null);
  protected readonly isLoading = signal(false);
  protected readonly isSaving = signal(false);

  protected readonly products = signal<MenuProductOption[]>([]);
  protected readonly catalog = signal<CatalogProduct[]>([]);
  protected readonly taxes = signal<TaxItem[]>([]);
  protected readonly families = signal<FamilyItem[]>([]);
  /** Variantes por producto, cargadas lazy cuando un producto se añade a una sección. */
  protected readonly variantsByProduct = signal<Record<string, ProductVariantItem[]>>({});

  /** Estado del sidebar de catálogo */
  protected readonly catalogSearch = signal<string>('');
  protected readonly catalogFamilyFilter = signal<string | null>(null);

  protected readonly header = signal<EditorHeader>(DEFAULT_HEADER());
  protected readonly sections = signal<EditorSection[]>([]);
  protected readonly productSearchByItem = signal<Record<string, string>>({});
  protected readonly errorMessages = signal<string[]>([]);

  /** IDs de las drop-lists destino de productos (una por sección). */
  public readonly itemsDropIds = computed(() =>
    this.sections().map((_, i) => `section-${i}`),
  );
  public readonly priceEuros = computed(() => (this.header().price / 100).toFixed(2));

  /** Productos visibles en el sidebar tras aplicar filtros de familia y búsqueda. */
  public readonly filteredCatalog = computed<CatalogProduct[]>(() => {
    const term = this.catalogSearch().trim().toLowerCase();
    const familyId = this.catalogFamilyFilter();
    return this.catalog()
      .filter((p) => p.active)
      .filter((p) => (familyId === null ? true : p.familyId === familyId))
      .filter((p) => (term ? p.name.toLowerCase().includes(term) : true));
  });

  public async ngOnInit(): Promise<void> {
    const id = this.route.snapshot.paramMap.get('id');
    this.mode.set(id ? 'edit' : 'create');
    this.menuId.set(id);

    this.isLoading.set(true);
    try {
      // Carga inicial: productos + impuestos + familias en paralelo
      const [productsResponse, taxesResponse, familiesResponse] = await Promise.all([
        firstValueFrom(this.productService.listProducts()),
        firstValueFrom(this.taxService.listTaxes()),
        firstValueFrom(this.familyService.listFamilies()),
      ]);

      const products = Array.isArray(productsResponse)
        ? productsResponse
        : (productsResponse as any).items || [];
      const taxes = Array.isArray(taxesResponse)
        ? taxesResponse
        : (taxesResponse as any).items || [];
      const families = Array.isArray(familiesResponse)
        ? familiesResponse
        : (familiesResponse as any).items || [];

      this.products.set(this.toProductOptions(products));
      this.catalog.set(this.toCatalogProducts(products));
      this.taxes.set(taxes);
      this.families.set(families);

      if (id) {
        const menu = await firstValueFrom(this.menuService.getMenu(id));
        this.hydrateFromMenu(menu);
      } else {
        const header = DEFAULT_HEADER();
        if (taxes.length > 0) header.taxId = taxes[0].id;
        this.header.set(header);
      }
    } catch (error) {
      const message = error instanceof Error ? error.message : 'No se pudo cargar el editor.';
      this.toastService.presentError(message);
      this.router.navigateByUrl('/app/gestion');
    } finally {
      this.isLoading.set(false);
    }
  }

  // ──────────────────────────────────────────────────────────────────────────
  // Header
  // ──────────────────────────────────────────────────────────────────────────

  public updateHeader<K extends keyof EditorHeader>(key: K, value: EditorHeader[K]): void {
    this.header.update((h) => ({ ...h, [key]: value }));
  }

  public onPriceChange(value: string): void {
    const normalized = (value ?? '').toString().replace(',', '.');
    const amount = Number.parseFloat(normalized);
    const cents = Number.isFinite(amount) ? Math.round(amount * 100) : 0;
    this.updateHeader('price', cents);
  }

  public toggleDay(bit: number): void {
    this.header.update((h) => ({ ...h, availableDays: h.availableDays ^ bit }));
  }

  public isDayActive(bit: number): boolean {
    return (this.header().availableDays & bit) !== 0;
  }

  // ──────────────────────────────────────────────────────────────────────────
  // Sections / Items
  // ──────────────────────────────────────────────────────────────────────────

  public addSection(): void {
    this.sections.update((list) => [...list, DEFAULT_SECTION()]);
  }

  public removeSection(idx: number): void {
    this.sections.update((list) => list.filter((_, i) => i !== idx));
  }

  public updateSection<K extends keyof EditorSection>(idx: number, key: K, value: EditorSection[K]): void {
    this.sections.update((list) =>
      list.map((section, i) => (i === idx ? { ...section, [key]: value } : section)),
    );
  }

  /**
   * Modo de elección para mostrar como chips:
   * - `single-required`: el comensal está obligado a elegir exactamente 1 producto.
   * - `single-optional`: puede elegir 1 producto o ninguno.
   * - `multi`: combinación libre min/max (≥1 productos o rangos custom).
   */
  public sectionChoiceMode(section: EditorSection): 'single-required' | 'single-optional' | 'multi' {
    if (section.minChoices === 1 && section.maxChoices === 1) return 'single-required';
    if (section.minChoices === 0 && section.maxChoices === 1) return 'single-optional';
    return 'multi';
  }

  public setSectionChoiceMode(
    sectionIdx: number,
    mode: 'single-required' | 'single-optional' | 'multi',
  ): void {
    this.sections.update((list) =>
      list.map((section, i) => {
        if (i !== sectionIdx) return section;
        if (mode === 'single-required') return { ...section, minChoices: 1, maxChoices: 1 };
        if (mode === 'single-optional') return { ...section, minChoices: 0, maxChoices: 1 };
        // multi: si ya era multi, mantenemos valores; si no, default 1..2
        if (section.maxChoices > 1) return section;
        return { ...section, minChoices: 1, maxChoices: 2 };
      }),
    );
  }

  public dropSection(event: CdkDragDrop<EditorSection[]>): void {
    if (event.previousIndex === event.currentIndex) return;
    this.sections.update((list) => {
      const next = [...list];
      moveItemInArray(next, event.previousIndex, event.currentIndex);
      return next;
    });
  }

  public addItem(sectionIdx: number, productId: string): void {
    if (!productId) return;
    this.sections.update((list) =>
      list.map((section, i) => {
        if (i !== sectionIdx) return section;
        const exists = section.items.some((it) => it.productId === productId && it.variantId === null);
        if (exists) {
          this.toastService.presentInfo('Este producto ya está en la sección.');
          return section;
        }
        return {
          ...section,
          items: [...section.items, { productId, variantId: null, extraPrice: 0 }],
        };
      }),
    );
    this.productSearchByItem.update((current) => ({ ...current, [`section-${sectionIdx}`]: '' }));
    void this.ensureVariantsLoaded(productId);
  }

  public removeItem(sectionIdx: number, itemIdx: number): void {
    this.sections.update((list) =>
      list.map((section, i) => {
        if (i !== sectionIdx) return section;
        return { ...section, items: section.items.filter((_, j) => j !== itemIdx) };
      }),
    );
  }

  public updateItem<K extends keyof EditorItem>(
    sectionIdx: number,
    itemIdx: number,
    key: K,
    value: EditorItem[K],
  ): void {
    this.sections.update((list) =>
      list.map((section, i) => {
        if (i !== sectionIdx) return section;
        return {
          ...section,
          items: section.items.map((item, j) => (j === itemIdx ? { ...item, [key]: value } : item)),
        };
      }),
    );
  }

  public onItemExtraPriceChange(sectionIdx: number, itemIdx: number, value: string): void {
    const normalized = (value ?? '').toString().replace(',', '.');
    const amount = Number.parseFloat(normalized);
    const cents = Number.isFinite(amount) ? Math.round(amount * 100) : 0;
    this.updateItem(sectionIdx, itemIdx, 'extraPrice', cents);
  }

  public dropItem(event: CdkDragDrop<EditorItem[]>, targetSectionIdx: number): void {
    const fromContainer = event.previousContainer.id;
    const toContainer = event.container.id;

    if (fromContainer === toContainer) {
      if (event.previousIndex === event.currentIndex) return;
      this.sections.update((list) =>
        list.map((section, i) => {
          if (i !== targetSectionIdx) return section;
          const items = [...section.items];
          moveItemInArray(items, event.previousIndex, event.currentIndex);
          return { ...section, items };
        }),
      );
      return;
    }

    // Drop from catalog sidebar → create a new item
    if (fromContainer === 'catalog-sidebar') {
      const product = event.item.data as CatalogProduct;
      this.sections.update((list) =>
        list.map((section, i) => {
          if (i !== targetSectionIdx) return section;
          if (section.items.some((it) => it.productId === product.id && it.variantId === null)) {
            this.toastService.presentInfo('Este producto ya está en la sección.');
            return section;
          }
          const next = [...section.items];
          next.splice(event.currentIndex, 0, { productId: product.id, variantId: null, extraPrice: 0 });
          return { ...section, items: next };
        }),
      );
      void this.ensureVariantsLoaded(product.id);
      return;
    }

    const fromIdx = Number.parseInt(fromContainer.replace('section-', ''), 10);
    if (Number.isNaN(fromIdx)) return;

    this.sections.update((list) => {
      const fromItems = [...list[fromIdx].items];
      const [moved] = fromItems.splice(event.previousIndex, 1);
      if (!moved) return list;

      return list.map((section, i) => {
        if (i === fromIdx) return { ...section, items: fromItems };
        if (i === targetSectionIdx) {
          if (section.items.some((it) => it.productId === moved.productId && it.variantId === moved.variantId)) {
            this.toastService.presentInfo('Ese producto ya está en la sección destino.');
            return section;
          }
          const next = [...section.items];
          next.splice(event.currentIndex, 0, moved);
          return { ...section, items: next };
        }
        return section;
      });
    });
  }

  // ──────────────────────────────────────────────────────────────────────────
  // Lookups y helpers
  // ──────────────────────────────────────────────────────────────────────────

  public productName(productId: string): string {
    return this.products().find((p) => p.id === productId)?.name ?? '— producto eliminado —';
  }

  /**
   * Precio de referencia para mostrar en la fila del item.
   * Si hay variante seleccionada, usa el precio de la variante; si no, el del producto base.
   */
  public itemReferencePriceEuros(item: EditorItem): string {
    if (item.variantId) {
      const variant = this.variantsByProduct()[item.productId]?.find((v) => v.id === item.variantId);
      if (variant) return `${(variant.price / 100).toFixed(2).replace('.', ',')}€`;
    }
    return this.productPriceEuros(item.productId);
  }

  public productPriceEuros(productId: string): string {
    const p = this.products().find((pr) => pr.id === productId);
    if (!p) return '';
    return `${(p.price / 100).toFixed(2).replace('.', ',')}€`;
  }

  /** Variantes del producto (vacío si aún no se han cargado o el producto no tiene). */
  public productVariants(productId: string): ProductVariantItem[] {
    return this.variantsByProduct()[productId] ?? [];
  }

  /**
   * Carga las variantes de un producto si no están cacheadas. Si la API falla,
   * lo logueamos y dejamos el array vacío — un producto sin variantes lookea igual.
   */
  public async ensureVariantsLoaded(productId: string): Promise<void> {
    if (this.variantsByProduct()[productId] !== undefined) return;
    // Marcamos con array vacío para evitar peticiones duplicadas mientras carga.
    this.variantsByProduct.update((current) => ({ ...current, [productId]: [] }));
    try {
      const response = await firstValueFrom(this.productVariantService.listVariants(productId));
      const active = response.variants.filter((v) => v.active).sort((a, b) => a.sort_order - b.sort_order);
      this.variantsByProduct.update((current) => ({ ...current, [productId]: active }));
    } catch {
      // No hacemos toast — la mayoría de productos no tienen variantes y el endpoint puede devolver 404.
    }
  }

  public productSearchValue(sectionIdx: number): string {
    return this.productSearchByItem()[`section-${sectionIdx}`] ?? '';
  }

  public onProductSearch(sectionIdx: number, value: string): void {
    this.productSearchByItem.update((current) => ({ ...current, [`section-${sectionIdx}`]: value }));
  }

  public productSearchResults(sectionIdx: number): MenuProductOption[] {
    const term = (this.productSearchByItem()[`section-${sectionIdx}`] ?? '').trim().toLowerCase();
    if (!term) return [];
    const sectionProductIds = new Set(this.sections()[sectionIdx]?.items.map((it) => it.productId) ?? []);
    return this.products()
      .filter((p) => p.active && !sectionProductIds.has(p.id))
      .filter((p) => p.name.toLowerCase().includes(term))
      .slice(0, 8);
  }

  public itemExtraPriceEuros(item: EditorItem): string {
    return (item.extraPrice / 100).toFixed(2);
  }

  public formatPriceEuros(cents: number): string {
    return `${(cents / 100).toFixed(2).replace('.', ',')}€`;
  }

  // ──────────────────────────────────────────────────────────────────────────
  // Submit / Cancel
  // ──────────────────────────────────────────────────────────────────────────

  public async onSave(): Promise<void> {
    const errors = this.validate();
    this.errorMessages.set(errors);
    if (errors.length > 0) {
      this.toastService.presentError(errors[0]);
      return;
    }

    const payload = this.toPayload();
    this.isSaving.set(true);

    try {
      if (this.mode() === 'create') {
        const created = await firstValueFrom(this.menuService.createMenu(payload));
        this.toastService.presentSuccess(`Menú "${created.name}" creado.`);
      } else {
        const id = this.menuId();
        if (!id) return;
        const updated = await firstValueFrom(
          this.menuService.updateMenu(id, { ...payload, active: this.header().active }),
        );
        this.toastService.presentSuccess(`Menú "${updated.name}" actualizado.`);
      }
      this.router.navigateByUrl('/app/gestion');
    } catch (error) {
      const message = error instanceof Error ? error.message : 'No se pudo guardar el menú.';
      this.toastService.presentError(message);
    } finally {
      this.isSaving.set(false);
    }
  }

  public onCancel(): void {
    this.router.navigateByUrl('/app/gestion');
  }

  // ──────────────────────────────────────────────────────────────────────────
  // Internals
  // ──────────────────────────────────────────────────────────────────────────

  private toProductOptions(products: ProductItem[]): MenuProductOption[] {
    return products.map((p) => ({
      id: p.id,
      name: p.name,
      price: p.price,
      active: p.active,
    }));
  }

  private toCatalogProducts(products: ProductItem[]): CatalogProduct[] {
    return products.map((p) => ({
      id: p.id,
      name: p.name,
      price: p.price,
      active: p.active,
      familyId: p.family_id,
    }));
  }

  // ──────────────────────────────────────────────────────────────────────────
  // Catalog sidebar
  // ──────────────────────────────────────────────────────────────────────────

  public onCatalogSearchChange(value: string): void {
    this.catalogSearch.set(value);
  }

  public setCatalogFamilyFilter(familyId: string | null): void {
    this.catalogFamilyFilter.set(familyId);
  }

  public familyName(familyId: string): string {
    return this.families().find((f) => f.id === familyId)?.name ?? '';
  }

  private hydrateFromMenu(menu: MenuDto): void {
    this.header.set({
      name: menu.name,
      description: menu.description ?? '',
      price: menu.price,
      taxId: menu.tax_id,
      validityFrom: menu.validity_from ?? '',
      validityTo: menu.validity_to ?? '',
      availableDays: menu.available_days,
      availableFromTime: menu.available_from_time ? menu.available_from_time.slice(0, 5) : '',
      availableToTime: menu.available_to_time ? menu.available_to_time.slice(0, 5) : '',
      active: menu.active,
    });

    const sections: EditorSection[] = [...menu.sections]
      .sort((a, b) => a.position - b.position)
      .map((s) => ({
        name: s.name,
        minChoices: s.min_choices,
        maxChoices: s.max_choices,
        items: [...s.items]
          .sort((a, b) => a.position - b.position)
          .map<EditorItem>((it) => ({
            productId: it.product_id,
            variantId: it.variant_id,
            extraPrice: it.extra_price,
          })),
      }));

    this.sections.set(sections);
    this.productSearchByItem.set({});

    // Pre-cargar variantes de los productos ya añadidos al menú para que el selector funcione al abrir.
    const productIds = new Set<string>();
    sections.forEach((s) => s.items.forEach((it) => productIds.add(it.productId)));
    productIds.forEach((id) => void this.ensureVariantsLoaded(id));
  }

  private validate(): string[] {
    const errors: string[] = [];
    const h = this.header();
    if (!h.name.trim()) errors.push('Indica el nombre del menú.');
    if (!h.taxId) errors.push('Selecciona un IVA para el menú.');
    if (h.price < 0) errors.push('El precio no puede ser negativo.');
    if (h.availableDays === 0) errors.push('Selecciona al menos un día de disponibilidad.');

    if ((h.availableFromTime && !h.availableToTime) || (!h.availableFromTime && h.availableToTime)) {
      errors.push('La franja horaria debe tener inicio y fin, o estar vacía.');
    }
    if (h.availableFromTime && h.availableToTime && h.availableFromTime >= h.availableToTime) {
      errors.push('La hora de inicio debe ser anterior a la de fin.');
    }
    if (h.validityFrom && h.validityTo && h.validityFrom > h.validityTo) {
      errors.push('La fecha de inicio de validez no puede ser posterior a la de fin.');
    }

    const sections = this.sections();
    if (sections.length === 0) errors.push('Añade al menos una sección al menú.');

    sections.forEach((s, i) => {
      const tag = s.name.trim() || `Sección ${i + 1}`;
      if (!s.name.trim()) errors.push(`La sección ${i + 1} necesita un nombre.`);
      if (s.minChoices < 0) errors.push(`"${tag}": el mínimo no puede ser negativo.`);
      if (s.maxChoices < 1) errors.push(`"${tag}": el máximo debe ser al menos 1.`);
      if (s.minChoices > s.maxChoices) errors.push(`"${tag}": el mínimo no puede superar al máximo.`);
      if (s.items.length === 0) errors.push(`"${tag}" debe tener al menos un producto.`);
    });

    return errors;
  }

  private toPayload(): CreateMenuPayload {
    const h = this.header();
    const days: number[] = ISO_WEEKDAYS.filter((d) => (h.availableDays & d.bit) !== 0).map((d) => d.index);

    const sections: MenuSectionPayload[] = this.sections().map((s, i) => ({
      name: s.name.trim(),
      position: i,
      min_choices: s.minChoices,
      max_choices: s.maxChoices,
      items: s.items.map<MenuItemPayload>((it, j) => ({
        product_id: it.productId,
        variant_id: it.variantId,
        extra_price: it.extraPrice,
        position: j,
      })),
    }));

    return {
      tax_id: h.taxId,
      name: h.name.trim(),
      description: h.description.trim() || null,
      price: h.price,
      validity_from: h.validityFrom || null,
      validity_to: h.validityTo || null,
      available_days: days,
      available_from_time: h.availableFromTime || null,
      available_to_time: h.availableToTime || null,
      active: h.active,
      sections,
    };
  }
}
