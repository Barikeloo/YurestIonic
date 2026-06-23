import { Component, input, output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { IdentityMode } from '../../models/guest-session.models';
import { GuestIconComponent, GuestIconName } from '../ui/guest-icon.component';

interface IdentityOption {
  mode: IdentityMode;
  icon: GuestIconName;
  title: string;
  subtitle: string;
}

const OPTIONS: IdentityOption[] = [
  { mode: 'registered', icon: 'star',   title: 'Con mi cuenta',    subtitle: 'Acumula puntos y accede a ofertas' },
  { mode: 'named',      icon: 'pencil', title: 'Solo mi nombre',   subtitle: 'Sin registro, directo a la carta' },
  { mode: 'anonymous',  icon: 'bolt',   title: 'Anónimo',          subtitle: 'Directo a la carta' },
];

@Component({
  selector: 'app-identity-selector',
  standalone: true,
  imports: [CommonModule, GuestIconComponent],
  template: `
    <div class="identity-list">
      <p class="identity-question">¿Cómo quieres continuar?</p>
      @for (opt of options; track opt.mode) {
        <button
          class="identity-card"
          [class.selected]="selected() === opt.mode"
          (click)="selectMode.emit(opt.mode)"
        >
          <span class="ic-icon-wrap" [class.ic-icon-wrap--active]="selected() === opt.mode">
            <app-guest-icon [name]="opt.icon" [size]="20" />
          </span>
          <span class="ic-text">
            <span class="ic-title">{{ opt.title }}</span>
            <span class="ic-sub">{{ opt.subtitle }}</span>
          </span>
          <span class="ic-arrow">
            <app-guest-icon name="chevron-right" [size]="16" />
          </span>
        </button>
      }
    </div>
  `,
  styles: [`
    .identity-question {
      font-size: 13px;
      color: #888;
      margin: 0 0 10px;
      text-align: center;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      font-weight: 600;
    }
    .identity-list {
      display: flex;
      flex-direction: column;
      gap: 8px;
      width: 100%;
    }
    .identity-card {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 13px 14px;
      border-radius: 14px;
      border: 1.5px solid #ebebeb;
      background: #fafafa;
      cursor: pointer;
      text-align: left;
      transition: border-color 0.15s, background 0.15s;
      width: 100%;

      &:hover { border-color: #ccc; background: #f5f5f5; }
      &.selected {
        border-color: var(--guest-primary, #ff4d4d);
        background: color-mix(in srgb, var(--guest-primary, #ff4d4d) 6%, white);
      }
    }
    .ic-icon-wrap {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      background: #f0f0f0;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #666;
      flex-shrink: 0;
      transition: background 0.15s, color 0.15s;

      &--active {
        background: var(--guest-primary, #ff4d4d);
        color: #fff;
      }
    }
    .ic-text {
      display: flex;
      flex-direction: column;
      gap: 1px;
      flex: 1;
    }
    .ic-title {
      font-size: 15px;
      font-weight: 700;
      color: #111;
    }
    .ic-sub {
      font-size: 12px;
      color: #999;
    }
    .ic-arrow {
      color: #ccc;
      flex-shrink: 0;
    }
  `],
})
export class IdentitySelectorComponent {
  readonly selected = input<IdentityMode | null>(null);
  readonly selectMode = output<IdentityMode>();
  readonly options = OPTIONS;
}
