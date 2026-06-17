import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { BaseApiService } from '../core/services/api/base-api.service';

export type AllergenCode =
  | 'gluten'
  | 'crustaceans'
  | 'eggs'
  | 'fish'
  | 'peanuts'
  | 'soy'
  | 'dairy'
  | 'nuts'
  | 'celery'
  | 'mustard'
  | 'sesame'
  | 'sulphites'
  | 'lupin'
  | 'molluscs';

export interface ProductItem {
  id: string;
  family_id: string;
  tax_id: string;
  image_src: string | null;
  name: string;
  price: number;
  stock: number;
  active: boolean;
  allergens: AllergenCode[];
  created_at: string;
  updated_at: string;
}

interface CreateProductPayload {
  family_id: string;
  tax_id: string;
  image_src?: string | null;
  name: string;
  price: number;
  stock: number;
  active?: boolean;
  allergens?: AllergenCode[];
}

interface UpdateProductPayload {
  family_id: string;
  tax_id: string;
  image_src?: string | null;
  name: string;
  price: number;
  stock: number;
  active: boolean;
  allergens?: AllergenCode[];
}

export interface PhotoUploadTokenResponse {
  token: string;
  upload_url: string;
  expires_at: string;
}

@Injectable({
  providedIn: 'root',
})
export class ProductService extends BaseApiService {
  protected override readonly defaultErrorMessage = 'No se pudo completar la peticion de productos.';

  public listProducts(): Observable<ProductItem[]> {
    return this.get<ProductItem[]>('/admin/products');
  }

  public createProduct(payload: CreateProductPayload): Observable<ProductItem> {
    return this.post<ProductItem>('/admin/products', payload);
  }

  public updateProduct(id: string, payload: UpdateProductPayload): Observable<ProductItem> {
    return this.put<ProductItem>(`/admin/products/${id}`, payload);
  }

  public deleteProduct(id: string): Observable<void> {
    return this.delete<void>(`/admin/products/${id}`);
  }

  public activateProduct(id: string): Observable<ProductItem> {
    return this.patch<ProductItem>(`/admin/products/${id}/activate`);
  }

  public deactivateProduct(id: string): Observable<ProductItem> {
    return this.patch<ProductItem>(`/admin/products/${id}/deactivate`);
  }

  public generatePhotoUploadToken(id: string): Observable<PhotoUploadTokenResponse> {
    return this.post<PhotoUploadTokenResponse>(`/admin/products/${id}/photo-upload-token`);
  }

  public uploadPhotoDirect(id: string, file: File): Observable<{ product_name: string; image_src: string }> {
    const body = new FormData();
    body.append('photo', file);
    return this.post<{ product_name: string; image_src: string }>(`/admin/products/${id}/photo`, body);
  }
}

