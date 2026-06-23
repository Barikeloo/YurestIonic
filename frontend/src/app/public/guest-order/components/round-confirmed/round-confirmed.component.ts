import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { GuestOrderFacade } from '../../facades/guest-order.facade';
import { GuestIconComponent } from '../ui/guest-icon.component';

@Component({
  selector: 'app-round-confirmed',
  standalone: true,
  imports: [CommonModule, GuestIconComponent],
  template: `
    <div class="rc-host">
      <div class="rc-card">
        <div class="rc-icon">
          <app-guest-icon name="check-circle" [size]="64" />
        </div>
        <h1 class="rc-title">¡Tu pedido está en camino!</h1>

        @if (facade.lastRound(); as round) {
          <p class="rc-sub">
            Hemos enviado
            @if (round.label) { "{{ round.label }}" · }
            {{ round.line_count }} producto{{ round.line_count !== 1 ? 's' : '' }}
          </p>
        }

        @if (facade.cart.pendingLines().length > 0) {
          <div class="rc-pending">
            <p class="rc-pending-title">Aún en tu carrito ({{ facade.cart.pendingLines().length }} ítems)</p>
            @for (line of facade.cart.pendingLines(); track line.localId) {
              <p class="rc-pending-item">· {{ line.name }} × {{ line.quantity }}</p>
            }
            <button class="rc-send-pending" (click)="facade.goToCart()">
              Enviar cuando estés listo →
            </button>
          </div>
        }

        <div class="rc-actions">
          <button class="rc-catalog-btn" (click)="facade.goToCatalog()">
            Seguir añadiendo productos
          </button>
          <button class="rc-history-btn" (click)="facade.goToHistory()">
            Ver todo lo que he pedido
          </button>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .rc-host {
      min-height: 100dvh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      background: #f7f7f7;
      font-family: system-ui, -apple-system, sans-serif;
    }
    .rc-card {
      background: #fff;
      border-radius: 24px;
      padding: 36px 24px;
      max-width: 420px;
      width: 100%;
      box-shadow: 0 4px 32px rgba(0,0,0,0.08);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 16px;
      text-align: center;
    }
    .rc-icon {
      color: #22c55e;
      display: flex;
      align-items: center;
      justify-content: center;
      animation: check-pop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
    }
    @keyframes check-pop {
      0%   { transform: scale(0);   opacity: 0; }
      60%  { transform: scale(1.2); opacity: 1; }
      100% { transform: scale(1);   opacity: 1; }
    }
    .rc-title { font-size: 22px; font-weight: 800; color: #111111; margin: 0; }
    .rc-sub { font-size: 15px; color: #777777; margin: 0; }

    .rc-pending {
      width: 100%;
      background: #f7f7f7;
      border-radius: 14px;
      padding: 14px 16px;
      text-align: left;
      display: flex;
      flex-direction: column;
      gap: 6px;
      border: 1px solid #efefef;
    }
    .rc-pending-title { font-size: 12px; font-weight: 700; color: #aaaaaa; margin: 0; text-transform: uppercase; letter-spacing: 0.05em; }
    .rc-pending-item { font-size: 14px; color: #555555; margin: 0; }
    .rc-send-pending {
      background: none;
      border: 1.5px solid var(--guest-primary, #ff4d4d);
      color: var(--guest-primary, #ff4d4d);
      border-radius: 10px;
      padding: 9px 16px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      margin-top: 4px;
      align-self: flex-start;
      font-family: inherit;
      min-height: 40px;
    }

    .rc-actions {
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .rc-catalog-btn {
      width: 100%;
      height: 52px;
      background: var(--guest-primary, #ff4d4d);
      color: #fff;
      border: none;
      border-radius: 14px;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      font-family: inherit;
      transition: opacity 0.15s;
      &:hover { opacity: 0.9; }
    }
    .rc-history-btn {
      width: 100%;
      height: 48px;
      background: #f0f0f0;
      color: #555555;
      border: none;
      border-radius: 14px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      font-family: inherit;
      transition: background 0.15s;
      &:hover { background: #e5e5e5; }
    }
  `],
})
export class RoundConfirmedComponent {
  protected readonly facade = inject(GuestOrderFacade);
}
