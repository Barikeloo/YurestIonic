import { inject, Injectable } from '@angular/core';
import { HttpClient, HttpEvent, HttpRequest } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface PhotoUploadContextResponse {
  product_name: string;
  image_src: string | null;
  expires_at: string;
}

export interface PhotoUploadResponse {
  product_name: string;
  image_src: string;
}

/**
 * Calls the two public photo-upload endpoints using relative URLs so the Angular dev-server
 * proxy forwards them transparently.  This makes the flow work from any device on the LAN
 * (mobile scanning the QR) without CORS issues or "localhost" resolution problems.
 *
 * Desktop:  /api/...  →  proxy  →  localhost:8000/api/...
 * Mobile:   192.168.x.x:4200/api/...  →  same proxy  →  localhost:8000/api/...
 */
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
