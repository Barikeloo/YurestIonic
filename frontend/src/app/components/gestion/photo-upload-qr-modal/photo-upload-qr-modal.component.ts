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

export interface PhotoUploadedEvent {
  productId: string;
  imageSrc: string;
}

type ModalState = 'idle' | 'loading' | 'ready' | 'uploaded' | 'expired' | 'error';

const MIN_REUSE_SECONDS = 15;

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

  private readonly productService = inject(ProductService);
  private readonly echoService = inject(EchoService);
  private readonly toastService = inject(ToastService);

  protected readonly state = signal<ModalState>('idle');
  protected readonly tokenData = signal<PhotoUploadTokenResponse | null>(null);
  protected readonly uploadedImageSrc = signal<string | null>(null);
  protected readonly secondsLeft = signal(0);

  private readonly qrRenderTrigger = signal(0);

  private unsubscribeEcho: (() => void) | null = null;
  private countdownInterval: ReturnType<typeof setInterval> | null = null;

  private cachedForProductId: string | null = null;

  protected readonly isLoading = computed(() => this.state() === 'loading');
  protected readonly isReady = computed(() => this.state() === 'ready');
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

  private async tryOpen(): Promise<void> {
    const cached = this.tokenData();

    if (cached && this.canReuse(cached)) {

      this.state.set('ready');
      this.startCountdown(cached.expires_at);
      this.subscribeToChannel(cached.token);
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
      this.cachedForProductId = this.productId;
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

    if (this.state() === 'uploaded') return false;
    if (this.cachedForProductId !== this.productId) return false;

    const secsLeft = Math.floor((new Date(token.expires_at).getTime() - Date.now()) / 1000);
    return secsLeft > MIN_REUSE_SECONDS;
  }

  // ── Close / cleanup ───────────────────────────────────────

  /**
   * Called when the modal closes. Stops the live subscriptions but keeps the
   * token in memory so it can be reused if the modal reopens before expiry.
   */
  private pauseOnClose(): void {
    this.cleanup();

  }

  private hardReset(): void {
    this.cleanup();
    this.state.set('idle');
    this.tokenData.set(null);
    this.uploadedImageSrc.set(null);
    this.secondsLeft.set(0);
    this.cachedForProductId = null;
  }

  private subscribeToChannel(token: string): void {
    this.unsubscribeEcho = this.echoService.listenOnce<{ product_uuid: string; image_src: string }>(
      `photo-upload.${token}`,
      'photo.uploaded',
      (data) => {
        this.uploadedImageSrc.set(data.image_src);
        this.state.set('uploaded');
        this.cleanup();
        this.photoUploaded.emit({ productId: data.product_uuid, imageSrc: data.image_src });
        this.toastService.presentSuccess('Foto actualizada correctamente.');
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

  private cleanup(): void {
    this.unsubscribeEcho?.();
    this.unsubscribeEcho = null;
    if (this.countdownInterval !== null) {
      clearInterval(this.countdownInterval);
      this.countdownInterval = null;
    }
  }

  // ── QR render ─────────────────────────────────────────────

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

  protected formatCountdown(secs: number): string {
    const m = Math.floor(secs / 60);
    const s = secs % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
  }
}
