import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { environment } from '../../environments/environment';

export interface ProductItem {
  id: string;
  family_id: string;
  tax_id: string;
  image_src: string | null;
  name: string;
  price: number;
  stock: number;
  active: boolean;
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
}

interface UpdateProductPayload {
  family_id: string;
  tax_id: string;
  image_src?: string | null;
  name: string;
  price: number;
  stock: number;
  active: boolean;
}

@Injectable({
  providedIn: 'root',
})
export class ProductService {
  private readonly baseUrl: string = environment.apiUrl;

  constructor(private readonly http: HttpClient) {}

  public listProducts(): Observable<ProductItem[]> {
    return this.http
      .get<ProductItem[]>(`${this.baseUrl}/admin/products`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public createProduct(payload: CreateProductPayload): Observable<ProductItem> {
    return this.http
      .post<ProductItem>(`${this.baseUrl}/admin/products`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public updateProduct(id: string, payload: UpdateProductPayload): Observable<ProductItem> {
    return this.http
      .put<ProductItem>(`${this.baseUrl}/admin/products/${id}`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public deleteProduct(id: string): Observable<unknown> {
    return this.http
      .delete(`${this.baseUrl}/admin/products/${id}`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public activateProduct(id: string): Observable<ProductItem> {
    return this.http
      .patch<ProductItem>(`${this.baseUrl}/admin/products/${id}/activate`, {}, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public deactivateProduct(id: string): Observable<ProductItem> {
    return this.http
      .patch<ProductItem>(`${this.baseUrl}/admin/products/${id}/deactivate`, {}, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  private extractErrorMessage(error: HttpErrorResponse): string {
    const payload: unknown = error.error;

    if (payload && typeof payload === 'object') {
      const data = payload as { message?: unknown };
      if (typeof data.message === 'string' && data.message.trim() !== '') {
        return data.message;
      }
    }

    return 'No se pudo completar la peticion de productos.';
  }
}
