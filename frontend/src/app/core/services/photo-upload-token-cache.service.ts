import { Injectable } from '@angular/core';
import { PhotoUploadTokenResponse } from '../../services/product.service';

@Injectable({ providedIn: 'root' })
export class PhotoUploadTokenCacheService {
  private readonly cache = new Map<string, PhotoUploadTokenResponse>();

  get(productId: string): PhotoUploadTokenResponse | undefined {
    return this.cache.get(productId);
  }

  set(productId: string, token: PhotoUploadTokenResponse): void {
    this.cache.set(productId, token);
  }

  clear(productId: string): void {
    this.cache.delete(productId);
  }
}
