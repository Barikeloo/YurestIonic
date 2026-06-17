import {
  Component,
  computed,
  effect,
  ElementRef,
  inject,
  Input,
  OnChanges,
  OnDestroy,
  Output,
  EventEmitter,
  signal,
  SimpleChanges,
  ViewChild,
} from '@angular/core';
import { firstValueFrom } from 'rxjs';
import QRCode from 'qrcode';
import { PhotoUploadTokenResponse, ProductService } from '../../../services/product.service';
import { EchoService } from '../../../core/services/echo.service';
import { ToastService } from '../../../core/services/toast.service';
import { PhotoUploadTokenCacheService } from '../../../core/services/photo-upload-token-cache.service';

export interface PhotoUploadedEvent {
  productId: string;
  imageSrc: string;
}

type ModalState = 'idle' | 'loading' | 'ready' | 'cropping' | 'uploading' | 'uploaded' | 'expired' | 'error';

const MIN_REUSE_SECONDS = 15;
const CROP_CANVAS_SIZE = 280;
const EXPORT_SIZE = 400;

@Component({
  selector: 'app-photo-upload-qr-modal',
  standalone: true,
  templateUrl: './photo-upload-qr-modal.component.html',
  styleUrls: ['./photo-upload-qr-modal.component.scss'],
})
export class PhotoUploadQrModalComponent implements OnChanges, OnDestroy {
  @Input() isOpen = false;
  @Input() productId: string | null = null;
  @Input() productName: string | null = null;

  @Output() closeModal = new EventEmitter<void>();
  @Output() photoUploaded = new EventEmitter<PhotoUploadedEvent>();

  @ViewChild('qrCanvas', { static: false }) qrCanvas?: ElementRef<HTMLCanvasElement>;
  @ViewChild('fileInput', { static: false }) fileInput?: ElementRef<HTMLInputElement>;
  @ViewChild('cropCanvas') set cropCanvasRef(ref: ElementRef<HTMLCanvasElement> | undefined) {
    this._cropCanvasEl.set(ref?.nativeElement ?? null);
  }

  private readonly productService = inject(ProductService);
  private readonly echoService = inject(EchoService);
  private readonly toastService = inject(ToastService);
  private readonly tokenCacheService = inject(PhotoUploadTokenCacheService);

  protected readonly state = signal<ModalState>('idle');
  protected readonly tokenData = signal<PhotoUploadTokenResponse | null>(null);
  protected readonly uploadedImageSrc = signal<string | null>(null);
  protected readonly secondsLeft = signal(0);
  protected readonly cropZoom = signal<number>(1);

  private readonly qrRenderTrigger = signal(0);
  private readonly _cropCanvasEl = signal<HTMLCanvasElement | null>(null);
  private readonly _cropOffsetX = signal<number>(0);
  private readonly _cropOffsetY = signal<number>(0);

  private unsubscribeEcho: (() => void) | null = null;
  private countdownInterval: ReturnType<typeof setInterval> | null = null;
  private cropImage: HTMLImageElement | null = null;
  private cropObjectUrl: string | null = null;
  private pendingFile: File | null = null;
  private isDragging = false;
  private dragStartX = 0;
  private dragStartY = 0;
  private dragOffsetStartX = 0;
  private dragOffsetStartY = 0;

  protected readonly isLoading = computed(() => this.state() === 'loading');
  protected readonly isReady = computed(() => this.state() === 'ready');
  protected readonly isCropping = computed(() => this.state() === 'cropping');
  protected readonly isUploading = computed(() => this.state() === 'uploading');
  protected readonly isUploaded = computed(() => this.state() === 'uploaded');
  protected readonly isExpired = computed(() => this.state() === 'expired');
  protected readonly hasError = computed(() => this.state() === 'error');

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['isOpen']) {
      if (this.isOpen && this.productId) {
        this.tryOpen();
      } else if (!this.isOpen) {
        this.pauseOnClose();
      }
    }
  }

  ngOnDestroy(): void {
    this.cleanup();
    this.cleanupCrop();
    this.revokeUploadedPreview();
  }

  protected onClose(): void {
    this.closeModal.emit();
  }

  protected async regenerate(): Promise<void> {
    this.hardReset();
    if (this.productId) {
      await this.open();
    }
  }

  // ── File input ────────────────────────────────────────────

  protected triggerFileInput(): void {
    this.fileInput?.nativeElement.click();
  }

  protected onFileSelected(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file || !this.productId) return;

    if (this.fileInput) {
      this.fileInput.nativeElement.value = '';
    }

    this.pendingFile = file;
    const url = URL.createObjectURL(file);
    this.cropObjectUrl = url;

    const img = new Image();
    img.onload = () => {
      this.cropImage = img;
      this.cropZoom.set(1);
      this._cropOffsetX.set(0);
      this._cropOffsetY.set(0);
      this.state.set('cropping');
      // _cropEffect fires reactively when Angular sets the ViewChild via cropCanvasRef setter
    };
    img.onerror = () => {
      this.cleanupCrop();
      this.toastService.presentError('No se pudo leer la imagen. Prueba con otro archivo.');
    };
    img.src = url;
  }

  // ── Crop / zoom ───────────────────────────────────────────

  protected onZoomChange(event: Event): void {
    this.cropZoom.set(Number((event.target as HTMLInputElement).value));
  }

  protected onCropMouseDown(event: MouseEvent): void {
    this.isDragging = true;
    this.dragStartX = event.clientX;
    this.dragStartY = event.clientY;
    this.dragOffsetStartX = this._cropOffsetX();
    this.dragOffsetStartY = this._cropOffsetY();
    event.preventDefault();
  }

  protected onCropMouseMove(event: MouseEvent): void {
    if (!this.isDragging) return;
    this._cropOffsetX.set(this.dragOffsetStartX + (event.clientX - this.dragStartX));
    this._cropOffsetY.set(this.dragOffsetStartY + (event.clientY - this.dragStartY));
  }

  protected onCropMouseUp(): void {
    this.isDragging = false;
  }

  protected async confirmCrop(): Promise<void> {
    if (!this.productId) return;

    const fileToUpload = await this.buildCroppedFile();
    if (!fileToUpload) return;
    await this.uploadDirectFile(fileToUpload);
  }

  private async buildCroppedFile(): Promise<File | null> {
    const img = this.cropImage;
    if (!img || img.naturalWidth === 0 || img.naturalHeight === 0) {
      return this.pendingFile;
    }

    try {
      const exportCanvas = document.createElement('canvas');
      exportCanvas.width = EXPORT_SIZE;
      exportCanvas.height = EXPORT_SIZE;
      const ctx = exportCanvas.getContext('2d');
      if (!ctx) return this.pendingFile;

      const baseScale = CROP_CANVAS_SIZE / Math.max(img.naturalWidth, img.naturalHeight);
      const scale = baseScale * this.cropZoom();
      const w = img.naturalWidth * scale;
      const h = img.naturalHeight * scale;
      const ox = this._cropOffsetX();
      const oy = this._cropOffsetY();
      const ratio = EXPORT_SIZE / CROP_CANVAS_SIZE;

      const dx = (ox + (CROP_CANVAS_SIZE - w) / 2) * ratio;
      const dy = (oy + (CROP_CANVAS_SIZE - h) / 2) * ratio;
      const dw = w * ratio;
      const dh = h * ratio;

      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, EXPORT_SIZE, EXPORT_SIZE);
      ctx.drawImage(img, dx, dy, dw, dh);

      const blob = await new Promise<Blob | null>((resolve) =>
        exportCanvas.toBlob(resolve, 'image/jpeg', 0.92),
      );

      if (!blob || blob.size < 2000) {
        return this.pendingFile;
      }

      return new File([blob], 'product-photo.jpg', { type: 'image/jpeg' });
    } catch {
      return this.pendingFile;
    }
  }

  protected cancelCrop(): void {
    this.cleanupCrop();
    this.state.set('ready');
  }

  private async uploadDirectFile(file: File): Promise<void> {
    if (!this.productId) return;
    this.state.set('uploading');

    const previewUrl = URL.createObjectURL(file);

    try {
      await firstValueFrom(this.productService.uploadPhotoDirect(this.productId, file));
      this.uploadedImageSrc.set(previewUrl);
      this.state.set('uploaded');
      this.photoUploaded.emit({ productId: this.productId, imageSrc: previewUrl });
      this.toastService.presentSuccess(
        `Foto de "${this.productName ?? 'producto'}" actualizada correctamente.`,
      );
    } catch {
      URL.revokeObjectURL(previewUrl);
      this.toastService.presentError('No se pudo subir la foto. Comprueba el formato o el tamaño (máx. 20 MB).');
      this.state.set('ready');
    } finally {
      this.cleanupCrop();
    }
  }

  private revokeUploadedPreview(): void {
    const src = this.uploadedImageSrc();
    if (src?.startsWith('blob:')) {
      URL.revokeObjectURL(src);
    }
  }

  private cleanupCrop(): void {
    if (this.cropObjectUrl) {
      URL.revokeObjectURL(this.cropObjectUrl);
      this.cropObjectUrl = null;
    }
    this.cropImage = null;
    this.pendingFile = null;
    this.isDragging = false;
  }

  // ── QR flow ───────────────────────────────────────────────

  private async tryOpen(): Promise<void> {
    const cached = this.productId ? this.tokenCacheService.get(this.productId) : undefined;

    if (cached && this.canReuse(cached)) {
      this.tokenData.set(cached);
      this.state.set('ready');
      this.startCountdown(cached.expires_at);
      if (!this.unsubscribeEcho) {
        this.subscribeToChannel(cached.token);
      }
      this.scheduleQrRender();
      return;
    }

    this.hardReset();
    await this.open();
  }

  private async open(): Promise<void> {
    this.state.set('loading');

    try {
      const token = await firstValueFrom(this.productService.generatePhotoUploadToken(this.productId!));
      this.tokenData.set(token);
      this.tokenCacheService.set(this.productId!, token);
      this.state.set('ready');
      this.startCountdown(token.expires_at);
      this.subscribeToChannel(token.token);
      this.scheduleQrRender();
    } catch {
      this.state.set('error');
    }
  }

  private scheduleQrRender(): void {
    this.qrRenderTrigger.update((v) => v + 1);
  }

  private canReuse(token: PhotoUploadTokenResponse): boolean {
    const secsLeft = Math.floor((new Date(token.expires_at).getTime() - Date.now()) / 1000);
    return secsLeft > MIN_REUSE_SECONDS;
  }

  private pauseOnClose(): void {
    this.stopCountdown();
  }

  private hardReset(): void {
    this.cleanup();
    this.cleanupCrop();
    this.state.set('idle');
    this.tokenData.set(null);
    this.uploadedImageSrc.set(null);
    this.secondsLeft.set(0);
    if (this.productId) {
      this.tokenCacheService.clear(this.productId);
    }
  }

  private subscribeToChannel(token: string): void {
    this.unsubscribeEcho = this.echoService.listenOnce<{ product_uuid: string; image_src: string }>(
      `photo-upload.${token}`,
      'photo.uploaded',
      (data) => {
        const path = (() => { try { return new URL(data.image_src).pathname; } catch { return data.image_src; } })();
        const src = `${path}?v=${Date.now()}`;
        this.uploadedImageSrc.set(src);
        this.state.set('uploaded');
        this.photoUploaded.emit({ productId: data.product_uuid, imageSrc: src });
        this.toastService.presentSuccess(
          `Foto de "${this.productName ?? 'producto'}" actualizada correctamente.`,
        );
      },
    );
  }

  private startCountdown(expiresAt: string): void {
    const tick = (): void => {
      const diff = Math.max(0, Math.floor((new Date(expiresAt).getTime() - Date.now()) / 1000));
      this.secondsLeft.set(diff);
      if (diff === 0) {
        this.state.set('expired');
        this.cleanup();
      }
    };
    tick();
    this.countdownInterval = setInterval(tick, 1000);
  }

  private stopCountdown(): void {
    if (this.countdownInterval !== null) {
      clearInterval(this.countdownInterval);
      this.countdownInterval = null;
    }
  }

  private cleanup(): void {
    this.unsubscribeEcho?.();
    this.unsubscribeEcho = null;
    this.stopCountdown();
  }

  // ── Effects ───────────────────────────────────────────────

  readonly _qrEffect = effect(() => {
    const trigger = this.qrRenderTrigger();
    const token = this.tokenData();
    if (trigger === 0 || !token) return;

    Promise.resolve().then(() => {
      const canvas = this.qrCanvas?.nativeElement;
      if (!canvas) return;
      QRCode.toCanvas(canvas, token.upload_url, {
        width: 240,
        margin: 2,
        color: { dark: '#0d0d0d', light: '#ffffff' },
      });
    });
  });

  readonly _cropEffect = effect(() => {
    const canvas = this._cropCanvasEl();
    const zoom = this.cropZoom();
    const ox = this._cropOffsetX();
    const oy = this._cropOffsetY();

    if (!canvas || !this.cropImage) return;

    const img = this.cropImage;
    const size = CROP_CANVAS_SIZE;
    const baseScale = size / Math.max(img.naturalWidth, img.naturalHeight);
    const scale = baseScale * zoom;
    const w = img.naturalWidth * scale;
    const h = img.naturalHeight * scale;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    ctx.clearRect(0, 0, size, size);
    ctx.fillStyle = '#1a1a1a';
    ctx.fillRect(0, 0, size, size);
    ctx.drawImage(img, ox + (size - w) / 2, oy + (size - h) / 2, w, h);
  });

  protected formatCountdown(secs: number): string {
    const m = Math.floor(secs / 60);
    const s = secs % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
  }
}
