import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { BaseApiService } from '../core/services/api/base-api.service';

export type ModifierType = 'extra' | 'accompaniment';
export type ModifierSelectionType = 'single' | 'multi';

export interface ProductModifierItem {
  id: string;
  product_id: string;
  name: string;
  type: ModifierType;
  is_required: boolean;
  selection_type: ModifierSelectionType;
  price: number;
  active: boolean;
  sort_order: number;
  created_at: string;
  updated_at: string;
}

interface CreateModifierPayload {
  name: string;
  type: ModifierType;
  is_required?: boolean;
  selection_type?: ModifierSelectionType;
  price: number;
  active?: boolean;
  sort_order?: number;
}

interface UpdateModifierPayload {
  name: string;
  type: ModifierType;
  is_required: boolean;
  selection_type: ModifierSelectionType;
  price: number;
  active: boolean;
  sort_order?: number;
}

@Injectable({
  providedIn: 'root',
})
export class ProductModifierService extends BaseApiService {
  protected override readonly defaultErrorMessage = 'No se pudo completar la peticion de modificadores.';

  public listModifiers(productId: string): Observable<{ modifiers: ProductModifierItem[] }> {
    return this.get<{ modifiers: ProductModifierItem[] }>(`/admin/products/${productId}/modifiers`);
  }

  public createModifier(productId: string, payload: CreateModifierPayload): Observable<ProductModifierItem> {
    return this.post<ProductModifierItem>(`/admin/products/${productId}/modifiers`, payload);
  }

  public updateModifier(productId: string, modifierId: string, payload: UpdateModifierPayload): Observable<ProductModifierItem> {
    return this.put<ProductModifierItem>(`/admin/products/${productId}/modifiers/${modifierId}`, payload);
  }

  public deleteModifier(productId: string, modifierId: string): Observable<void> {
    return this.delete<void>(`/admin/products/${productId}/modifiers/${modifierId}`);
  }

  public reorderModifiers(productId: string, items: Array<{ id: string; sort_order: number }>): Observable<void> {
    return this.put<void>(`/admin/products/${productId}/modifiers/reorder`, { items });
  }
}
