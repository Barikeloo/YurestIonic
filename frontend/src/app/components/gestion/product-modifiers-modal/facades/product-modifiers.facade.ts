import { computed, inject, Injectable, OnDestroy, Signal, signal } from '@angular/core';
import { Observable, Subject, throwError } from 'rxjs';
import { catchError, takeUntil, tap } from 'rxjs/operators';
import { AllergenCode, ProductItem, ProductService } from '../../../../services/product.service';
import { ProductVariantItem, ProductVariantService } from '../../../../services/product-variant.service';
import { ProductModifierItem, ProductModifierService } from '../../../../services/product-modifier.service';
import { ProductRow } from '../../../../pages/core/gestion/facades/gestion-products.facade';

@Injectable()
export class ProductModifiersFacade implements OnDestroy {
  private readonly productService = inject(ProductService);
  private readonly variantService = inject(ProductVariantService);
  private readonly modifierService = inject(ProductModifierService);
  private readonly destroy$ = new Subject<void>();

  // Signals privados — estado
  private readonly _product = signal<ProductRow | null>(null);
  private readonly _selectedAllergens = signal<AllergenCode[]>([]);
  private readonly _variants = signal<ProductVariantItem[]>([]);
  private readonly _variantsLoading = signal<boolean>(false);
  private readonly _modifiers = signal<ProductModifierItem[]>([]);
  private readonly _modifiersLoading = signal<boolean>(false);
  private readonly _isSaving = signal<boolean>(false);
  private readonly _error = signal<string | null>(null);

  // Signals públicos — solo lectura
  public readonly product: Signal<ProductRow | null> = this._product.asReadonly();
  public readonly selectedAllergens: Signal<AllergenCode[]> = this._selectedAllergens.asReadonly();
  public readonly variants: Signal<ProductVariantItem[]> = this._variants.asReadonly();
  public readonly variantsLoading: Signal<boolean> = this._variantsLoading.asReadonly();
  public readonly modifiers: Signal<ProductModifierItem[]> = this._modifiers.asReadonly();
  public readonly modifiersLoading: Signal<boolean> = this._modifiersLoading.asReadonly();
  public readonly isSaving: Signal<boolean> = this._isSaving.asReadonly();
  public readonly error: Signal<string | null> = this._error.asReadonly();

  public readonly hasChanges: Signal<boolean> = computed(() => {
    const product = this._product();
    if (!product) {
      return false;
    }

    const current = this._selectedAllergens();
    const original = product.allergens;

    if (current.length !== original.length) {
      return true;
    }

    return !current.every((code) => original.includes(code));
  });

  // Setters explícitos
  public setProduct(product: ProductRow | null): void {
    this._product.set(product);
    this._selectedAllergens.set(product ? [...product.allergens] : []);
    this._variants.set([]);
    this._modifiers.set([]);
    this._error.set(null);
  }

  public setAllergens(codes: AllergenCode[]): void {
    this._selectedAllergens.set([...codes]);
  }

  public setError(value: string | null): void {
    this._error.set(value);
  }

  // Métodos de UI
  public toggleAllergen(code: AllergenCode): void {
    this._selectedAllergens.update((current) =>
      current.includes(code) ? current.filter((c) => c !== code) : [...current, code],
    );
  }

  public isAllergenSelected(code: AllergenCode): boolean {
    return this._selectedAllergens().includes(code);
  }

  public reset(): void {
    this._product.set(null);
    this._selectedAllergens.set([]);
    this._variants.set([]);
    this._variantsLoading.set(false);
    this._modifiers.set([]);
    this._modifiersLoading.set(false);
    this._isSaving.set(false);
    this._error.set(null);
  }

  // Métodos de negocio
  public save(): Observable<ProductItem> {
    const product = this._product();

    if (!product?.uuid) {
      return throwError(() => new Error('Producto no válido para guardar modificadores.'));
    }

    this._isSaving.set(true);
    this._error.set(null);

    return this.productService
      .updateProduct(product.uuid, {
        name: product.name,
        family_id: product.family_id,
        tax_id: product.tax_id,
        price: product.price,
        stock: product.stock,
        active: product.active,
        allergens: this._selectedAllergens(),
      })
      .pipe(
        tap(() => this._isSaving.set(false)),
        catchError((err) => {
          this._isSaving.set(false);
          const message = err instanceof Error ? err.message : 'No se pudo guardar los modificadores.';
          this._error.set(message);

          return throwError(() => new Error(message));
        }),
        takeUntil(this.destroy$),
      );
  }

  // --- Variants ---
  public loadVariants(): Observable<{ variants: ProductVariantItem[] }> {
    const product = this._product();
    if (!product?.uuid) {
      return throwError(() => new Error('Producto no válido para cargar variantes.'));
    }

    this._variantsLoading.set(true);
    this._error.set(null);

    return this.variantService.listVariants(product.uuid).pipe(
      tap((response) => {
        this._variants.set(response.variants);
        this._variantsLoading.set(false);
      }),
      catchError((err) => {
        this._variantsLoading.set(false);
        const message = err instanceof Error ? err.message : 'No se pudieron cargar las variantes.';
        this._error.set(message);
        return throwError(() => new Error(message));
      }),
      takeUntil(this.destroy$),
    );
  }

  public addVariant(payload: Omit<ProductVariantItem, 'id' | 'product_id' | 'created_at' | 'updated_at'>): Observable<ProductVariantItem> {
    const product = this._product();
    if (!product?.uuid) {
      return throwError(() => new Error('Producto no válido.'));
    }

    this._isSaving.set(true);
    this._error.set(null);

    return this.variantService.createVariant(product.uuid, payload).pipe(
      tap((variant) => {
        this._variants.update((current) => [...current, variant]);
        this._isSaving.set(false);
      }),
      catchError((err) => {
        this._isSaving.set(false);
        const message = err instanceof Error ? err.message : 'No se pudo crear la variante.';
        this._error.set(message);
        return throwError(() => new Error(message));
      }),
      takeUntil(this.destroy$),
    );
  }

  public updateVariant(variantId: string, payload: Omit<ProductVariantItem, 'id' | 'product_id' | 'created_at' | 'updated_at'>): Observable<ProductVariantItem> {
    const product = this._product();
    if (!product?.uuid) {
      return throwError(() => new Error('Producto no válido.'));
    }

    this._isSaving.set(true);
    this._error.set(null);

    return this.variantService.updateVariant(product.uuid, variantId, payload).pipe(
      tap((updated) => {
        this._variants.update((current) =>
          current.map((v) => (v.id === variantId ? updated : v)),
        );
        this._isSaving.set(false);
      }),
      catchError((err) => {
        this._isSaving.set(false);
        const message = err instanceof Error ? err.message : 'No se pudo actualizar la variante.';
        this._error.set(message);
        return throwError(() => new Error(message));
      }),
      takeUntil(this.destroy$),
    );
  }

  public removeVariant(variantId: string): Observable<void> {
    const product = this._product();
    if (!product?.uuid) {
      return throwError(() => new Error('Producto no válido.'));
    }

    this._isSaving.set(true);
    this._error.set(null);

    return this.variantService.deleteVariant(product.uuid, variantId).pipe(
      tap(() => {
        this._variants.update((current) => current.filter((v) => v.id !== variantId));
        this._isSaving.set(false);
      }),
      catchError((err) => {
        this._isSaving.set(false);
        const message = err instanceof Error ? err.message : 'No se pudo eliminar la variante.';
        this._error.set(message);
        return throwError(() => new Error(message));
      }),
      takeUntil(this.destroy$),
    );
  }

  // --- Modifiers (extras & accompaniments) ---
  public loadModifiers(): Observable<{ modifiers: ProductModifierItem[] }> {
    const product = this._product();
    if (!product?.uuid) {
      return throwError(() => new Error('Producto no válido para cargar modificadores.'));
    }

    this._modifiersLoading.set(true);
    this._error.set(null);

    return this.modifierService.listModifiers(product.uuid).pipe(
      tap((response) => {
        this._modifiers.set(response.modifiers);
        this._modifiersLoading.set(false);
      }),
      catchError((err) => {
        this._modifiersLoading.set(false);
        const message = err instanceof Error ? err.message : 'No se pudieron cargar los modificadores.';
        this._error.set(message);
        return throwError(() => new Error(message));
      }),
      takeUntil(this.destroy$),
    );
  }

  public addModifier(payload: Omit<ProductModifierItem, 'id' | 'product_id' | 'created_at' | 'updated_at'>): Observable<ProductModifierItem> {
    const product = this._product();
    if (!product?.uuid) {
      return throwError(() => new Error('Producto no válido.'));
    }

    this._isSaving.set(true);
    this._error.set(null);

    return this.modifierService.createModifier(product.uuid, payload).pipe(
      tap((modifier) => {
        this._modifiers.update((current) => [...current, modifier]);
        this._isSaving.set(false);
      }),
      catchError((err) => {
        this._isSaving.set(false);
        const message = err instanceof Error ? err.message : 'No se pudo crear el modificador.';
        this._error.set(message);
        return throwError(() => new Error(message));
      }),
      takeUntil(this.destroy$),
    );
  }

  public updateModifier(modifierId: string, payload: Omit<ProductModifierItem, 'id' | 'product_id' | 'created_at' | 'updated_at'>): Observable<ProductModifierItem> {
    const product = this._product();
    if (!product?.uuid) {
      return throwError(() => new Error('Producto no válido.'));
    }

    this._isSaving.set(true);
    this._error.set(null);

    return this.modifierService.updateModifier(product.uuid, modifierId, payload).pipe(
      tap((updated) => {
        this._modifiers.update((current) =>
          current.map((m) => (m.id === modifierId ? updated : m)),
        );
        this._isSaving.set(false);
      }),
      catchError((err) => {
        this._isSaving.set(false);
        const message = err instanceof Error ? err.message : 'No se pudo actualizar el modificador.';
        this._error.set(message);
        return throwError(() => new Error(message));
      }),
      takeUntil(this.destroy$),
    );
  }

  public removeModifier(modifierId: string): Observable<void> {
    const product = this._product();
    if (!product?.uuid) {
      return throwError(() => new Error('Producto no válido.'));
    }

    this._isSaving.set(true);
    this._error.set(null);

    return this.modifierService.deleteModifier(product.uuid, modifierId).pipe(
      tap(() => {
        this._modifiers.update((current) => current.filter((m) => m.id !== modifierId));
        this._isSaving.set(false);
      }),
      catchError((err) => {
        this._isSaving.set(false);
        const message = err instanceof Error ? err.message : 'No se pudo eliminar el modificador.';
        this._error.set(message);
        return throwError(() => new Error(message));
      }),
      takeUntil(this.destroy$),
    );
  }

  public ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }
}
