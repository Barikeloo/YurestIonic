import { computed, inject, Injectable, signal } from '@angular/core';
import { HttpEventType } from '@angular/common/http';
import { Subject, takeUntil } from 'rxjs';
import {
  PublicPhotoUploadService,
  PhotoUploadContextResponse,
} from '../../../../services/public-photo-upload.service';

export type UploadState =
  | 'validating'
  | 'ready'
  | 'camera'
  | 'crop'
  | 'preview'
  | 'uploading'
  | 'success'
  | 'error'
  | 'expired'
  | 'used';

export type { PhotoUploadContextResponse as UploadContext };

@Injectable()
export class PhotoUploadFacade {
  private readonly uploadService = inject(PublicPhotoUploadService);

  private readonly _state = signal<UploadState>('validating');
  private readonly _context = signal<PhotoUploadContextResponse | null>(null);
  private readonly _secondsLeft = signal(0);
  private readonly _uploadedSrc = signal<string | null>(null);
  private readonly _errorMessage = signal('');
  private readonly _uploadProgress = signal(0);

  public readonly state = this._state.asReadonly();
  public readonly context = this._context.asReadonly();
  public readonly secondsLeft = this._secondsLeft.asReadonly();
  public readonly uploadedSrc = this._uploadedSrc.asReadonly();
  public readonly errorMessage = this._errorMessage.asReadonly();
  public readonly uploadProgress = this._uploadProgress.asReadonly();

  public readonly isWarn = computed(() => this._secondsLeft() > 0 && this._secondsLeft() <= 60);
  public readonly formattedTime = computed(() => {
    const s = this._secondsLeft();
    return `${Math.floor(s / 60)}:${(s % 60).toString().padStart(2, '0')}`;
  });

  private timerId: ReturnType<typeof setInterval> | null = null;
  private readonly uploadCancel$ = new Subject<void>();
  private token = '';

  public async loadContext(token: string): Promise<void> {
    this.token = token;
    this._state.set('validating');

    try {
      const ctx = await new Promise<PhotoUploadContextResponse>((resolve, reject) => {
        this.uploadService.getContext(token).subscribe({ next: resolve, error: reject });
      });
      this._context.set(this.rewriteLocalhost(ctx));
      const secs = Math.max(0, Math.floor((new Date(ctx.expires_at).getTime() - Date.now()) / 1000));
      this._secondsLeft.set(secs);
      this._state.set('ready');
      this.startTimer();
    } catch (err: unknown) {
      const status = (err as { status?: number })?.status ?? 0;
      if (status === 410) {
        this._state.set('expired');
      } else if (status === 409) {
        this._state.set('used');
      } else {
        this._errorMessage.set(
          (err as { error?: { message?: string } })?.error?.message ?? 'No se pudo verificar el enlace.',
        );
        this._state.set('error');
      }
    }
  }

  public setState(state: UploadState): void {
    this._state.set(state);
    if (state === 'success' || state === 'uploading') {
      this.pauseTimer();
    } else if (state === 'ready') {
      this.resumeTimer();
    }
  }

  public upload(blob: Blob): Promise<void> {
    this._state.set('uploading');
    this._uploadProgress.set(0);
    this.uploadCancel$.next();

    return new Promise((resolve, reject) => {
      this.uploadService
        .uploadPhoto(this.token, blob)
        .pipe(takeUntil(this.uploadCancel$))
        .subscribe({
          next: (event) => {
            if (event.type === HttpEventType.UploadProgress && event.total) {
              this._uploadProgress.set(Math.round((100 * event.loaded) / event.total));
            }
            if (event.type === HttpEventType.Response) {
              this._uploadProgress.set(100);
              const body = event.body as { image_src: string };
              setTimeout(() => {
                const src = body.image_src?.replace(/http:\/\/localhost(:\d+)?/, window.location.origin) ?? body.image_src;
                this._uploadedSrc.set(src ? `${src}?v=${Date.now()}` : src);
                this._state.set('success');
                resolve();
              }, 360);
            }
          },
          error: (err: unknown) => {
            const status = (err as { status?: number })?.status ?? 0;
            if (status === 410) {
              this._state.set('expired');
            } else if (status === 409) {
              this._state.set('used');
            } else {
              this._errorMessage.set(
                (err as { error?: { message?: string } })?.error?.message ?? 'No se pudo subir la foto.',
              );
              this._state.set('error');
            }
            reject(err);
          },
        });
    });
  }

  private rewriteLocalhost(ctx: PhotoUploadContextResponse): PhotoUploadContextResponse {
    if (ctx.image_src?.includes('localhost')) {
      return {
        ...ctx,
        image_src: ctx.image_src.replace(/http:\/\/localhost(:\d+)?/, window.location.origin),
      };
    }
    return ctx;
  }

  public cancelUpload(): void {
    this.uploadCancel$.next();
  }

  public destroy(): void {
    this.pauseTimer();
    this.uploadCancel$.next();
    this.uploadCancel$.complete();
  }

  private startTimer(): void {
    this.pauseTimer();
    this.timerId = setInterval(() => {
      const s = this._secondsLeft();
      if (s <= 0) {
        this._secondsLeft.set(0);
        this.pauseTimer();
        const cur = this._state();
        if (cur === 'ready' || cur === 'camera' || cur === 'crop') {
          this._state.set('expired');
        }
        return;
      }
      this._secondsLeft.update((v) => v - 1);
    }, 1000);
  }

  private pauseTimer(): void {
    if (this.timerId !== null) {
      clearInterval(this.timerId);
      this.timerId = null;
    }
  }

  private resumeTimer(): void {
    if (this.timerId === null && this._secondsLeft() > 0) {
      this.startTimer();
    }
  }
}
