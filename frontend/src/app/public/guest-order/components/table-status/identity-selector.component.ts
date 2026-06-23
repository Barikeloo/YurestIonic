import { Component, input, output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { IdentityMode } from '../../models/guest-session.models';

interface IdentityOption {
  mode: IdentityMode;
  icon: string;
  title: string;
  subtitle: string;
}

const OPTIONS: IdentityOption[] = [
  { mode: 'registered', icon: '👤', title: 'Entrar con mi cuenta', subtitle: 'Acumula puntos y accede a ofertas' },
  { mode: 'named',      icon: '✏️', title: 'Poner solo mi nombre',  subtitle: 'Sin registro, directo a la carta' },
  { mode: 'anonymous',  icon: '🚀', title: 'Entrar como anónimo',   subtitle: 'Directo a la carta' },
];

@Component({
  selector: 'app-identity-selector',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="identity-list">
      <p class="identity-question">¿Cómo quieres continuar?</p>
      @for (opt of options; track opt.mode) {
        <button
          class="identity-card"
          [class.selected]="selected() === opt.mode"
          (click)="selectMode.emit(opt.mode)"
        >
          <span class="ic-icon">{{ opt.icon }}</span>
          <span class="ic-text">
            <span class="ic-title">{{ opt.title }}</span>
            <span class="ic-sub">{{ opt.subtitle }}</span>
          </span>
        </button>
      }
    </div>
  `,
  styles: [`
    .identity-question {
      font-size: 15px;
      color: #555;
      margin: 0 0 12px;
      text-align: center;
    }
    .identity-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
      width: 100%;
    }
    .identity-card {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      border-radius: 12px;
      border: 1.5px solid #e0e0e0;
      background: #fff;
      cursor: pointer;
      text-align: left;
      transition: border-color 0.15s, background 0.15s;
      width: 100%;
      &:hover { border-color: var(--guest-primary, #ff4d4d); background: #fff9f9; }
      &.selected {
        border-color: var(--guest-primary, #ff4d4d);
        background: #fff5f5;
      }
    }
    .ic-icon { font-size: 22px; flex-shrink: 0; }
    .ic-text {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    .ic-title {
      font-size: 15px;
      font-weight: 600;
      color: #111;
    }
    .ic-sub {
      font-size: 13px;
      color: #888;
    }
  `],
})
export class IdentitySelectorComponent {
  readonly selected = input<IdentityMode | null>(null);
  readonly selectMode = output<IdentityMode>();
  readonly options = OPTIONS;
}
