import { Component, computed, inject, input, output, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TpvService } from '../../../cash/services/tpv.service';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { Subject, switchMap } from 'rxjs';

interface QrTokenData {
  token: string;
  url: string;
  active_sessions_count?: number;
}

@Component({
  selector: 'app-qr-token-modal',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="qr-backdrop" (click)="close.emit()">
      <div class="qr-modal" (click)="$event.stopPropagation()">
        <div class="qr-header">
          <h2 class="qr-title">QR Autoservicio · {{ tableName() }}</h2>
          <button class="qr-close" (click)="close.emit()">✕</button>
        </div>

        @if (loading()) {
          <div class="qr-loading">Generando QR…</div>
        } @else if (tokenData()) {
          <div class="qr-body">
            <div class="qr-image-wrap">
              <img
                class="qr-img"
                [src]="qrImageUrl()"
                alt="Código QR autoservicio"
              />
            </div>

            <p class="qr-url">{{ tokenData()!.url }}</p>

            @if ((tokenData()!.active_sessions_count ?? 0) > 0) {
              <p class="qr-sessions">
                👥 {{ tokenData()!.active_sessions_count }} comensal{{ (tokenData()!.active_sessions_count ?? 0) !== 1 ? 'es' : '' }} conectado{{ (tokenData()!.active_sessions_count ?? 0) !== 1 ? 's' : '' }}
              </p>
            }

            <div class="qr-actions">
              <button class="qr-btn qr-btn--copy" (click)="copyUrl()">
                {{ copied() ? '✓ Copiado' : '📋 Copiar enlace' }}
              </button>
              <button class="qr-btn qr-btn--regen" (click)="regenerate()">
                🔄 Regenerar QR
              </button>
            </div>
          </div>
        } @else if (error()) {
          <p class="qr-error">{{ error() }}</p>
        }
      </div>
    </div>
  `,
  styles: [`
    .qr-backdrop {
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.5);
      backdrop-filter: blur(4px);
      z-index: 1000;
      display: flex; align-items: center; justify-content: center;
      padding: 16px;
    }
    .qr-modal {
      background: #fff;
      border-radius: 18px;
      width: 100%;
      max-width: 380px;
      box-shadow: 0 8px 40px rgba(0,0,0,0.25);
      overflow: hidden;
    }
    .qr-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 20px;
      border-bottom: 1px solid #f0f0f0;
    }
    .qr-title { font-size: 16px; font-weight: 700; color: #111; margin: 0; }
    .qr-close {
      background: #f5f5f5; border: none; width: 30px; height: 30px;
      border-radius: 50%; cursor: pointer; font-size: 14px;
    }
    .qr-loading, .qr-error {
      text-align: center; padding: 40px; color: #888; font-size: 14px;
    }
    .qr-error { color: #e53935; }
    .qr-body {
      padding: 20px;
      display: flex; flex-direction: column; gap: 14px; align-items: center;
    }
    .qr-image-wrap {
      background: #fff;
      border: 2px solid #f0f0f0;
      border-radius: 14px;
      padding: 12px;
      width: 220px; height: 220px;
      display: flex; align-items: center; justify-content: center;
    }
    .qr-img { width: 100%; height: 100%; object-fit: contain; }
    .qr-url {
      font-size: 12px; color: #888;
      word-break: break-all; text-align: center; margin: 0;
    }
    .qr-sessions {
      font-size: 13px; color: #555; margin: 0;
      background: #f5f5f5; border-radius: 20px; padding: 4px 12px;
    }
    .qr-actions { display: flex; gap: 8px; width: 100%; }
    .qr-btn {
      flex: 1; height: 40px; border-radius: 10px; border: none;
      font-size: 13px; font-weight: 600; cursor: pointer; transition: opacity 0.15s;
      &:hover { opacity: 0.85; }
    }
    .qr-btn--copy { background: #f0f0f0; color: #333; }
    .qr-btn--regen { background: #e3f2fd; color: #1565c0; }
  `],
})
export class QrTokenModalComponent {
  readonly tableId = input.required<string>();
  readonly tableName = input<string>('Mesa');
  readonly close = output<void>();

  private readonly tpvService = inject(TpvService);

  readonly loading = signal(true);
  readonly tokenData = signal<QrTokenData | null>(null);
  readonly error = signal<string | null>(null);
  readonly copied = signal(false);

  readonly qrImageUrl = computed(() => {
    const data = this.tokenData();
    if (!data) return '';
    const encoded = encodeURIComponent(data.url);
    return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encoded}`;
  });

  private readonly regenerate$ = new Subject<void>();

  constructor() {
    this.regenerate$
      .pipe(
        switchMap(() => this.tpvService.generateTableQrToken(this.tableId())),
        takeUntilDestroyed(),
      )
      .subscribe({
        next: (data) => {
          this.loading.set(false);
          this.tokenData.set(data as QrTokenData);
        },
        error: () => {
          this.loading.set(false);
          this.error.set('Error al generar el QR. Inténtalo de nuevo.');
        },
      });

    this.regenerate$.next();
  }

  copyUrl(): void {
    const url = this.tokenData()?.url;
    if (!url) return;
    navigator.clipboard.writeText(url).then(() => {
      this.copied.set(true);
      setTimeout(() => this.copied.set(false), 2000);
    });
  }

  regenerate(): void {
    this.loading.set(true);
    this.regenerate$.next();
  }
}
