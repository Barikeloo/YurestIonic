import { computed, inject, Injectable, Signal, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { AllergenCode, ProductItem, ProductService } from '../../../../services/product.service';

export interface ProductRow {
  uuid?: string;
  family_id: string;
  tax_id: string;
  image_src: string | null;
  name: string;
  price: number;
  stock: number;
  active: boolean;
  allergens: AllergenCode[];
}

export interface ProductFormData {
  name: string;
  family_id: string;
  tax_id: string;
  price: number;
  stock: number;
  active: boolean;
  allergens: AllergenCode[];
}

const EMPTY_FORM: ProductFormData = {
  name: '',
  family_id: '',
  tax_id: '',
  price: 0,
  stock: 0,
  active: true,
  allergens: [],
};

export interface OperationResult {
  ok: boolean;
  error?: string;
  message?: string;
}

@Injectable()
export class GestionProductsFacade {
  private readonly productService = inject(ProductService);

  private readonly _products = signal<ProductRow[]>([]);
  private readonly _selectedIndex = signal<number>(-1);
  private readonly _formData = signal<ProductFormData>({ ...EMPTY_FORM });
  private readonly _isSaving = signal<boolean>(false);
  private readonly _isLoading = signal<boolean>(false);

  public readonly products: Signal<ProductRow[]> = this._products.asReadonly();
  public readonly selectedIndex: Signal<number> = this._selectedIndex.asReadonly();
  public readonly formData: Signal<ProductFormData> = this._formData.asReadonly();
  public readonly isSaving: Signal<boolean> = this._isSaving.asReadonly();
  public readonly isLoading: Signal<boolean> = this._isLoading.asReadonly();

  public readonly selectedProduct: Signal<ProductRow | null> = computed(() => {
    const index = this._selectedIndex();
    const list = this._products();

    return index >= 0 && index < list.length ? list[index] : null;
  });

  public async load(): Promise<void> {
    this._isLoading.set(true);

    try {
      const productsResponse = await firstValueFrom(this.productService.listProducts());
      const products = Array.isArray(productsResponse) ? productsResponse : (productsResponse as any).items || [];

      const rows: ProductRow[] = products.map((product: ProductItem) => ({
        uuid: product.id,
        family_id: product.family_id,
        tax_id: product.tax_id,
        image_src: product.image_src ?? null,
        name: product.name,
        price: product.price,
        stock: product.stock,
        active: product.active,
        allergens: product.allergens ?? [],
      }));

      this._products.set(rows);
      this.syncFormFromIndex();
    } finally {
      this._isLoading.set(false);
    }
  }

  public clear(): void {
    this._products.set([]);
    this._selectedIndex.set(-1);
    this._formData.set({ ...EMPTY_FORM });
  }

  public select(index: number): void {
    this._selectedIndex.set(index);
    this.syncFormFromIndex();
  }

  public startCreate(): void {
    this._selectedIndex.set(-1);
    this._formData.set({ ...EMPTY_FORM });
  }

  public updateForm<K extends keyof ProductFormData>(key: K, value: ProductFormData[K]): void {
    this._formData.update((current) => ({ ...current, [key]: value }));
  }

  public setForm(data: ProductFormData): void {
    this._formData.set({ ...data });
  }

  public applyPhoto(uuid: string, imageSrc: string): void {
    this._products.update((current) =>
      current.map((product) =>
        product.uuid === uuid ? { ...product, image_src: imageSrc } : product,
      ),
    );
  }

  public applyAllergens(uuid: string, allergens: AllergenCode[]): void {
    this._products.update((current) =>
      current.map((product) =>
        product.uuid === uuid ? { ...product, allergens: [...allergens] } : product,
      ),
    );
    this.syncFormFromIndex();
  }

  public async deleteSelected(): Promise<OperationResult> {
    const product = this.selectedProduct();

    if (!product?.uuid) {
      return { ok: false, error: 'No se puede eliminar: producto sin identificador.' };
    }

    try {
      await firstValueFrom(this.productService.deleteProduct(product.uuid));
      const newList = this._products().filter((candidate) => candidate.uuid !== product.uuid);
      this._products.set(newList);
      this._selectedIndex.set(newList.length > 0 ? 0 : -1);
      this.syncFormFromIndex();

      return { ok: true, message: `Producto "${product.name}" eliminado.` };
    } catch (error) {
      return { ok: false, error: error instanceof Error ? error.message : 'No se pudo eliminar el producto.' };
    }
  }

  public async delete(uuid: string): Promise<OperationResult> {
    try {
      await firstValueFrom(this.productService.deleteProduct(uuid));
      const newList = this._products().filter((candidate) => candidate.uuid !== uuid);
      this._products.set(newList);
      const currentIndex = this._selectedIndex();
      this._selectedIndex.set(newList.length > 0 ? Math.min(currentIndex, newList.length - 1) : -1);
      this.syncFormFromIndex();

      return { ok: true, message: 'Producto eliminado.' };
    } catch (error) {
      return { ok: false, error: error instanceof Error ? error.message : 'No se pudo eliminar el producto.' };
    }
  }

  public async save(): Promise<OperationResult> {
    const form = this._formData();
    const name = form.name.trim();
    const family_id = form.family_id;
    const tax_id = form.tax_id;
    const price = Number(form.price);
    const stock = Number(form.stock);
    const active = form.active;
    const allergens = form.allergens ?? [];

    if (!name || !family_id || !tax_id || price <= 0 || !Number.isFinite(stock) || stock < 0) {
      return { ok: false, error: 'Revisa los datos del producto.' };
    }

    const selected = this.selectedProduct();

    this._isSaving.set(true);

    try {
      if (selected?.uuid) {
        const updated = await firstValueFrom(
          this.productService.updateProduct(selected.uuid, { name, family_id, tax_id, price, stock, active, allergens }),
        );

        const finalProduct = active
          ? updated
          : await firstValueFrom(this.productService.deactivateProduct(updated.id));

        this.replaceProduct(selected.uuid, {
          uuid: finalProduct.id,
          family_id: finalProduct.family_id,
          tax_id: finalProduct.tax_id,
          image_src: finalProduct.image_src ?? selected.image_src ?? null,
          name: finalProduct.name,
          price: finalProduct.price,
          stock: finalProduct.stock,
          active: finalProduct.active,
          allergens: finalProduct.allergens ?? [],
        });

        this.syncFormFromIndex();

        return { ok: true, message: 'Producto actualizado.' };
      }

      const created = await firstValueFrom(
        this.productService.createProduct({ name, family_id, tax_id, price, stock, active, allergens }),
      );
      const finalProduct = active ? created : await firstValueFrom(this.productService.deactivateProduct(created.id));

      const newRow: ProductRow = {
        uuid: finalProduct.id,
        family_id: finalProduct.family_id,
        tax_id: finalProduct.tax_id,
        image_src: finalProduct.image_src ?? null,
        name: finalProduct.name,
        price: finalProduct.price,
        stock: finalProduct.stock,
        active: finalProduct.active,
        allergens: finalProduct.allergens ?? [],
      };

      const newList = [...this._products(), newRow];
      this._products.set(newList);
      this._selectedIndex.set(newList.length - 1);
      this.syncFormFromIndex();

      return { ok: true, message: 'Producto creado.' };
    } catch (error) {
      return { ok: false, error: error instanceof Error ? error.message : 'No se pudo guardar el producto.' };
    } finally {
      this._isSaving.set(false);
    }
  }

  private replaceProduct(uuid: string, replacement: ProductRow): void {
    this._products.update((current) => current.map((product) => (product.uuid === uuid ? replacement : product)));
  }

  private syncFormFromIndex(): void {
    const product = this.selectedProduct();

    if (product) {
      this._formData.set({
        name: product.name,
        family_id: product.family_id,
        tax_id: product.tax_id,
        price: product.price,
        stock: product.stock,
        active: product.active,
        allergens: [...product.allergens],
      });
    } else {
      this._formData.set({ ...EMPTY_FORM });
    }
  }
}
