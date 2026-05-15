import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { BaseApiService } from '../core/services/api/base-api.service';

export interface ProductVariantItem {
  id: string;
  product_id: string;
  name: string;
  price: number;
  stock: number;
  active: boolean;
  sort_order: number;
  created_at: string;
  updated_at: string;
}

interface CreateVariantPayload {
  name: string;
  price: number;
  stock: number;
  active?: boolean;
  sort_order?: number;
}

interface UpdateVariantPayload {
  name: string;
  price: number;
  stock: number;
  active: boolean;
  sort_order?: number;
}

@Injectable({
  providedIn: 'root',
})
export class ProductVariantService extends BaseApiService {
  protected override readonly defaultErrorMessage = 'No se pudo completar la peticion de variantes.';

  public listVariants(productId: string): Observable<{ variants: ProductVariantItem[] }> {
    return this.get<{ variants: ProductVariantItem[] }>(`/admin/products/${productId}/variants`);
  }

  public createVariant(productId: string, payload: CreateVariantPayload): Observable<ProductVariantItem> {
    return this.post<ProductVariantItem>(`/admin/products/${productId}/variants`, payload);
  }

  public updateVariant(productId: string, variantId: string, payload: UpdateVariantPayload): Observable<ProductVariantItem> {
    return this.put<ProductVariantItem>(`/admin/products/${productId}/variants/${variantId}`, payload);
  }

  public deleteVariant(productId: string, variantId: string): Observable<void> {
    return this.delete<void>(`/admin/products/${productId}/variants/${variantId}`);
  }
}
