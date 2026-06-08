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

  private unsubscribeEcho: (() => void) | null = null;
  private countdownInterval: ReturnType<typeof setInterval> | null = null;
  private qrRendered = false;

  protected readonly isLoading = computed(() => this.state() === 'loading');
  protected readonly isReady = computed(() => this.state() === 'ready');
  protected readonly isUploaded = computed(() => this.state() === 'uploaded');
  protected readonly isExpired = computed(() => this.state() === 'expired');
  protected readonly hasError = computed(() => this.state() === 'error');

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['isOpen']) {
      if (this.isOpen && this.productId) {
        this.open();
      } else if (!this.isOpen) {
        this.reset();
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
    this.reset();
    if (this.productId) {
      await this.open();
    }
  }

  private async open(): Promise<void> {
    this.state.set('loading');
    this.qrRendered = false;

    try {
      const token = await firstValueFrom(this.productService.generatePhotoUploadToken(this.productId!));
      this.tokenData.set(token);
      this.state.set('ready');
      this.startCountdown(token.expires_at);
      this.subscribeToChannel(token.token);
      // QR rendered via effect below once the canvas is in the DOM
    } catch {
      this.state.set('error');
    }
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

  private reset(): void {
    this.cleanup();
    this.state.set('idle');
    this.tokenData.set(null);
    this.uploadedImageSrc.set(null);
    this.secondsLeft.set(0);
    this.qrRendered = false;
  }

  // Render the QR once the canvas appears in the DOM (state=ready)
  readonly _qrEffect = effect(() => {
    const ready = this.isReady();
    const token = this.tokenData();
    if (!ready || !token || this.qrRendered) return;

    // Defer to next microtask to let Angular render the canvas
    Promise.resolve().then(() => {
      const canvas = this.qrCanvas?.nativeElement;
      if (!canvas) return;
      QRCode.toCanvas(canvas, token.upload_url, {
        width: 240,
        margin: 2,
        color: { dark: '#0d0d0d', light: '#ffffff' },
      }).then(() => {
        this.qrRendered = true;
      });
    });
  });

  protected formatCountdown(secs: number): string {
    const m = Math.floor(secs / 60);
    const s = secs % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
  }
}
