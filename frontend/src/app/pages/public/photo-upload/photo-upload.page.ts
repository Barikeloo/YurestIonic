import {
  Component,
  effect,
  ElementRef,
  inject,
  OnDestroy,
  OnInit,
  signal,
  ViewChild,
} from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { PhotoUploadFacade } from './facades/photo-upload.facade';

@Component({
  selector: 'app-photo-upload',
  standalone: true,
  providers: [PhotoUploadFacade],
  templateUrl: './photo-upload.page.html',
  styleUrls: ['./photo-upload.page.scss'],
})
export class PhotoUploadPage implements OnInit, OnDestroy {
  protected readonly facade = inject(PhotoUploadFacade);
  private readonly route = inject(ActivatedRoute);

  @ViewChild('camVideo') camVideo?: ElementRef<HTMLVideoElement>;
  @ViewChild('camFlash') camFlash?: ElementRef<HTMLDivElement>;
  @ViewChild('cropWindow') cropWindow?: ElementRef<HTMLDivElement>;
  @ViewChild('cropImg') cropImg?: ElementRef<HTMLImageElement>;
  @ViewChild('camFileInput') camFileInput?: ElementRef<HTMLInputElement>;
  @ViewChild('sheetFileInput') sheetFileInput?: ElementRef<HTMLInputElement>;

  private stream: MediaStream | null = null;

  private cr = { natW: 1, natH: 1, base: 1, scale: 1, tx: 0, ty: 0, win: 320, dragging: false, sx: 0, sy: 0 };
  protected capturedSrc: string | null = null;
  protected finalImageSrc = signal<string | null>(null);

  protected readonly sheetOpen = signal(false);
  protected readonly isSuccessPulse = signal(false);

  readonly ringLen = 2 * Math.PI * 70;

  private token = '';
  private audioCtx: AudioContext | null = null;

  constructor() {
    effect(() => {
      const state = this.facade.state();
      if (state !== 'camera') {
        this.stopCamera();
      }
      if (state === 'success') {
        this.isSuccessPulse.set(false);
        setTimeout(() => {
          this.isSuccessPulse.set(true);
          this.buzz();
          this.chime();
        }, 60);
      }
    });
  }

  ngOnInit(): void {
    this.token = this.route.snapshot.paramMap.get('token') ?? '';
    this.facade.loadContext(this.token);
  }

  ngOnDestroy(): void {
    this.stopCamera();
    this.facade.destroy();
    this.audioCtx?.close();
    this.token = '';
    URL.revokeObjectURL(this.capturedSrc ?? '');
    URL.revokeObjectURL(this.finalImageSrc() ?? '');
  }

  protected async goCamera(): Promise<void> {
    this.facade.setState('camera');
    await this.startCamera();
  }

  private async startCamera(): Promise<void> {
    const video = this.camVideo?.nativeElement;
    if (!video) return;
    if (!navigator.mediaDevices?.getUserMedia) {
      return this.cameraFallback();
    }
    try {
      this.stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 } },
        audio: false,
      });
      video.srcObject = this.stream;
      await video.play().catch(() => {});
    } catch {
      this.cameraFallback();
    }
  }

  private cameraFallback(): void {
    this.camFileInput?.nativeElement.click();
    this.facade.setState('ready');
  }

  private stopCamera(): void {
    this.stream?.getTracks().forEach((t) => t.stop());
    this.stream = null;
    const v = this.camVideo?.nativeElement;
    if (v) v.srcObject = null;
  }

  protected shoot(): void {
    const video = this.camVideo?.nativeElement;
    const flash = this.camFlash?.nativeElement;
    if (flash) {
      flash.classList.remove('go');
      void flash.offsetWidth;
      flash.classList.add('go');
    }
    if (!this.stream || !video?.videoWidth) return;
    navigator.vibrate?.([12]);
    const c = document.createElement('canvas');
    c.width = video.videoWidth;
    c.height = video.videoHeight;
    c.getContext('2d')!.drawImage(video, 0, 0);
    const src = c.toDataURL('image/jpeg', 0.92);
    setTimeout(() => this.enterCrop(src), 240);
  }

  protected camClose(): void { this.facade.setState('ready'); }
  protected camGallery(): void { this.facade.setState('ready'); setTimeout(() => this.sheetOpen.set(true), 120); }

  private readonly CROP_SKIP_RATIO = 1.15;

  protected enterCrop(src: string): void {
    this.capturedSrc = src;
    this.cr.scale = 1; this.cr.tx = 0; this.cr.ty = 0;

    requestAnimationFrame(() => {
      const imgEl = this.cropImg?.nativeElement;
      if (!imgEl) return;
      imgEl.onload = () => {
        this.cr.natW = imgEl.naturalWidth || 1200;
        this.cr.natH = imgEl.naturalHeight || 1200;
        if (this.isNearSquare()) {
          this.cropUse();
        } else {
          this.facade.setState('crop');
          this.layoutCrop();
        }
      };
      imgEl.src = src;
      if (imgEl.complete && imgEl.naturalWidth) {
        this.cr.natW = imgEl.naturalWidth; this.cr.natH = imgEl.naturalHeight;
        if (this.isNearSquare()) {
          this.cropUse();
        } else {
          this.facade.setState('crop');
          this.layoutCrop();
        }
      }
    });
  }

  private isNearSquare(): boolean {
    const ratio = Math.max(this.cr.natW, this.cr.natH) / Math.min(this.cr.natW, this.cr.natH);
    return ratio <= this.CROP_SKIP_RATIO;
  }

  private layoutCrop(): void {
    const w = this.cropWindow?.nativeElement?.clientWidth ?? 320;
    this.cr.win = w;
    this.cr.base = Math.max(w / this.cr.natW, w / this.cr.natH);
    this.clampCrop(); this.applyCrop();
  }

  private clampCrop(): void {
    const dw = this.cr.natW * this.cr.base * this.cr.scale;
    const dh = this.cr.natH * this.cr.base * this.cr.scale;
    const mx = Math.max(0, (dw - this.cr.win) / 2);
    const my = Math.max(0, (dh - this.cr.win) / 2);
    this.cr.tx = Math.max(-mx, Math.min(mx, this.cr.tx));
    this.cr.ty = Math.max(-my, Math.min(my, this.cr.ty));
  }

  private applyCrop(): void {
    const img = this.cropImg?.nativeElement;
    if (!img) return;
    img.style.width = `${this.cr.natW * this.cr.base}px`;
    img.style.height = `${this.cr.natH * this.cr.base}px`;
    img.style.transform = `translate(-50%,-50%) translate(${this.cr.tx}px,${this.cr.ty}px) scale(${this.cr.scale})`;
  }

  protected onCropPointerDown(e: PointerEvent): void {
    (e.currentTarget as Element).setPointerCapture(e.pointerId);
    this.cr.dragging = true; this.cr.sx = e.clientX; this.cr.sy = e.clientY;
  }

  protected onCropPointerMove(e: PointerEvent): void {
    if (!this.cr.dragging) return;
    this.cr.tx += e.clientX - this.cr.sx; this.cr.ty += e.clientY - this.cr.sy;
    this.cr.sx = e.clientX; this.cr.sy = e.clientY;
    this.clampCrop(); this.applyCrop();
  }

  protected onCropPointerUp(): void { this.cr.dragging = false; }

  protected onZoomChange(e: Event): void {
    this.cr.scale = parseFloat((e.target as HTMLInputElement).value);
    this.clampCrop(); this.applyCrop();
  }

  protected cropBack(): void { this.facade.setState('ready'); }
  protected cropRetry(): void { this.goCamera(); }

  protected async cropUse(): Promise<void> {
    navigator.vibrate?.([12]);
    const blob = await this.renderCrop();
    if (!blob) return;
    const previewUrl = URL.createObjectURL(blob);
    this.finalImageSrc.set(previewUrl);
    this.facade.upload(blob);
  }

  private renderCrop(): Promise<Blob | null> {
    const OUT = 1080;
    const c = document.createElement('canvas');
    c.width = OUT; c.height = OUT;
    const ctx = c.getContext('2d');
    if (!ctx) return Promise.resolve(null);
    ctx.fillStyle = '#000'; ctx.fillRect(0, 0, OUT, OUT);
    try {
      const img = this.cropImg?.nativeElement;
      if (!img) return Promise.resolve(null);
      const f = this.cr.base * this.cr.scale;
      const dl = this.cr.win / 2 - (this.cr.natW * f) / 2 + this.cr.tx;
      const dt = this.cr.win / 2 - (this.cr.natH * f) / 2 + this.cr.ty;
      ctx.drawImage(img, (0 - dl) / f, (0 - dt) / f, this.cr.win / f, this.cr.win / f, 0, 0, OUT, OUT);
    } catch { return Promise.resolve(null); }
    return new Promise((resolve) => c.toBlob((b) => resolve(b), 'image/jpeg', 0.9));
  }

  // ── Gallery sheet ────────────────────────────────────────
  protected goGallery(): void { this.sheetOpen.set(true); }
  protected closeSheet(): void { this.sheetOpen.set(false); }

  protected async onFileChange(e: Event): Promise<void> {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    this.sheetOpen.set(false);
    (e.target as HTMLInputElement).value = '';
    const url = await this.normalizeExifOrientation(file);
    this.enterCrop(url);
  }

  private normalizeExifOrientation(file: File): Promise<string> {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => {
        const dataUrl = reader.result as string;
        const img = new Image();
        img.onload = () => {
          const c = document.createElement('canvas');
          c.width = img.naturalWidth;
          c.height = img.naturalHeight;
          c.getContext('2d')!.drawImage(img, 0, 0);
          resolve(c.toDataURL('image/jpeg', 0.92));
        };
        img.onerror = () => resolve(dataUrl);
        img.src = dataUrl;
      };
      reader.onerror = () => reject();
      reader.readAsDataURL(file);
    });
  }

  // ── Upload actions ───────────────────────────────────────
  protected uploadCancel(): void { this.facade.cancelUpload(); this.facade.setState('crop'); }

  // ── Success / Error / Expired actions ───────────────────
  protected okAgain(): void { this.isSuccessPulse.set(false); this.facade.setState('ready'); }
  protected async errRetry(): Promise<void> { const b = await this.renderCrop(); if (b) this.facade.upload(b); }
  protected errRestart(): void { this.facade.setState('ready'); }
  protected expRetry(): void { this.facade.setState('ready'); }
  protected usedRetry(): void { this.facade.setState('ready'); }

  // ── Feedback ─────────────────────────────────────────────
  private buzz(): void { navigator.vibrate?.([24, 40, 24]); }

  private chime(): void {
    try {
      this.audioCtx ??= new AudioContext();
      const ctx = this.audioCtx;
      const now = ctx.currentTime;
      ([
        [660, 0], [880, 0.1], [1175, 0.2],
      ] as [number, number][]).forEach(([f, t]) => {
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        o.type = 'sine'; o.frequency.value = f;
        g.gain.setValueAtTime(0.0001, now + t);
        g.gain.exponentialRampToValueAtTime(0.16, now + t + 0.02);
        g.gain.exponentialRampToValueAtTime(0.0001, now + t + 0.26);
        o.connect(g).connect(ctx.destination);
        o.start(now + t); o.stop(now + t + 0.3);
      });
    } catch { /* audio not available */ }
  }

  protected onImgError(e: Event): void {
    (e.target as HTMLElement).classList.add('img-failed');
  }
}
