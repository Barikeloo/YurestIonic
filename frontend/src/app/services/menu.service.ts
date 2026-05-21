import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { BaseApiService } from '../core/services/api/base-api.service';

export interface MenuItemDto {
  id: string;
  product_id: string;
  variant_id: string | null;
  extra_price: number;
  position: number;
}

export interface MenuSectionDto {
  id: string;
  name: string;
  position: number;
  min_choices: number;
  max_choices: number;
  items: MenuItemDto[];
}

export interface MenuDto {
  id: string;
  tax_id: string;
  name: string;
  description: string | null;
  price: number;
  active: boolean;
  archived: boolean;
  validity_from: string | null;
  validity_to: string | null;
  /** ISO weekday bitmask (bit 0 = Monday ... bit 6 = Sunday). */
  available_days: number;
  available_from_time: string | null;
  available_to_time: string | null;
  sections: MenuSectionDto[];
  created_at: string;
  updated_at: string;
  archived_at: string | null;
}

export interface MenuItemPayload {
  product_id: string;
  variant_id?: string | null;
  extra_price?: number;
  position?: number;
}

export interface MenuSectionPayload {
  name: string;
  position?: number;
  min_choices: number;
  max_choices: number;
  items: MenuItemPayload[];
}

export interface CreateMenuPayload {
  tax_id: string;
  name: string;
  description?: string | null;
  price: number;
  validity_from?: string | null;
  validity_to?: string | null;
  /** ISO weekday list (1=Monday ... 7=Sunday). */
  available_days: number[];
  available_from_time?: string | null;
  available_to_time?: string | null;
  active?: boolean;
  sections: MenuSectionPayload[];
}

export interface UpdateMenuPayload extends CreateMenuPayload {
  active: boolean;
}

export interface ListMenusFilters {
  active?: boolean;
  archived?: boolean;
  search?: string;
}

/**
 * Subset mínimo de un producto necesario para el editor y selector de menús.
 * Permite alimentar el componente sin acoplarlo al DTO completo de la Carta.
 */
export interface MenuProductOption {
  id: string;
  name: string;
  price: number;
  active: boolean;
}

interface ListMenusResponse {
  data: MenuDto[];
}

@Injectable({
  providedIn: 'root',
})
export class MenuService extends BaseApiService {
  protected override readonly defaultErrorMessage = 'No se pudo completar la peticion de menus.';

  public listMenus(filters: ListMenusFilters = {}): Observable<ListMenusResponse> {
    const params: Record<string, string | boolean> = {};
    if (filters.active !== undefined) params['active'] = filters.active;
    if (filters.archived !== undefined) params['archived'] = filters.archived;
    if (filters.search) params['search'] = filters.search;

    return this.get<ListMenusResponse>('/admin/menus', params as Record<string, string | number | boolean>);
  }

  public getMenu(id: string): Observable<MenuDto> {
    return this.get<MenuDto>(`/admin/menus/${id}`);
  }

  public createMenu(payload: CreateMenuPayload): Observable<MenuDto> {
    return this.post<MenuDto>('/admin/menus', payload);
  }

  public updateMenu(id: string, payload: UpdateMenuPayload): Observable<MenuDto> {
    return this.put<MenuDto>(`/admin/menus/${id}`, payload);
  }

  public archiveMenu(id: string): Observable<void> {
    return this.delete<void>(`/admin/menus/${id}`);
  }

  public activateMenu(id: string): Observable<MenuDto> {
    return this.patch<MenuDto>(`/admin/menus/${id}/activate`);
  }

  public deactivateMenu(id: string): Observable<MenuDto> {
    return this.patch<MenuDto>(`/admin/menus/${id}/deactivate`);
  }
}
