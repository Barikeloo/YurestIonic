import { inject, Injectable } from '@angular/core';
import { HttpClient, HttpEvent, HttpRequest } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface PhotoUploadContextResponse {
  product_name: string;
  image_src: string | null;
  expires_at: string;
  restaurant_name: string;
}

export interface PhotoUploadResponse {
  product_name: string;
  image_src: string;
}

@Injectable({ providedIn: 'root' })
export class PublicPhotoUploadService {
  private readonly http = inject(HttpClient);

  getContext(token: string): Observable<PhotoUploadContextResponse> {
    return this.http.get<PhotoUploadContextResponse>(`/api/public/photo-upload/${token}`);
  }

  uploadPhoto(token: string, blob: Blob): Observable<HttpEvent<PhotoUploadResponse>> {
    const form = new FormData();
    form.append('photo', blob, 'photo.jpg');
    return this.http.request<PhotoUploadResponse>(
      new HttpRequest('POST', `/api/public/photo-upload/${token}`, form, {
        reportProgress: true,
      }),
    );
  }
}
