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
        <div class="rc-icon"><app-guest-icon name="check-circle" [size]="56" /></div>
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
              <p class="rc-pending-item">• {{ line.name }} × {{ line.quantity }}</p>
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
      background: #f8f8f8;
    }
    .rc-card {
      background: #fff;
      border-radius: 20px;
      padding: 32px 24px;
      max-width: 420px;
      width: 100%;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 16px;
      text-align: center;
    }
    .rc-icon { font-size: 56px; }
    .rc-title { font-size: 22px; font-weight: 800; color: #111; margin: 0; }
    .rc-sub { font-size: 15px; color: #666; margin: 0; }

    .rc-pending {
      width: 100%;
      background: #f8f8f8;
      border-radius: 12px;
      padding: 14px;
      text-align: left;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .rc-pending-title { font-size: 13px; font-weight: 700; color: #888; margin: 0; }
    .rc-pending-item { font-size: 14px; color: #555; margin: 0; }
    .rc-send-pending {
      background: none;
      border: 1.5px solid var(--guest-primary, #ff4d4d);
      color: var(--guest-primary, #ff4d4d);
      border-radius: 8px;
      padding: 8px 14px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      margin-top: 4px;
      align-self: flex-start;
    }

    .rc-actions {
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .rc-catalog-btn {
      width: 100%;
      height: 50px;
      background: var(--guest-primary, #ff4d4d);
      color: #fff;
      border: none;
      border-radius: 12px;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
    }
    .rc-history-btn {
      width: 100%;
      height: 46px;
      background: #f0f0f0;
      color: #555;
      border: none;
      border-radius: 12px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
    }
  `],
})
export class RoundConfirmedComponent {
  protected readonly facade = inject(GuestOrderFacade);
}
