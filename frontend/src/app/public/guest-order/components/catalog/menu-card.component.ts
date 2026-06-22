import { Component, input, output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MenuCatalogItem } from '../../models/guest-catalog.models';

@Component({
  selector: 'app-menu-card',
  standalone: true,
  imports: [CommonModule],
  template: `
    <button class="mc-card" (click)="select.emit(menu())">
      <div class="mc-icon">🍽️</div>
      <div class="mc-body">
        <span class="mc-name">{{ menu().name }}</span>
        @if (menu().description) {
          <span class="mc-desc">{{ menu().description }}</span>
        }
        <span class="mc-sections">{{ menu().sections.length }} secciones</span>
      </div>
      <span class="mc-price">{{ menu().price_cents / 100 | number:'1.2-2' }}€</span>
    </button>
  `,
  styles: [`
    .mc-card {
      display: flex;
      align-items: center;
      gap: 12px;
      width: 100%;
      background: #fff;
      border: 1.5px solid #f0f0f0;
      border-radius: 14px;
      padding: 14px;
      cursor: pointer;
      text-align: left;
      transition: box-shadow 0.15s;

      &:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      }
    }

    .mc-icon { font-size: 28px; flex-shrink: 0; }

    .mc-body {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 2px;
      min-width: 0;
    }

    .mc-name {
      font-size: 15px;
      font-weight: 700;
      color: #111;
    }

    .mc-desc {
      font-size: 13px;
      color: #888;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .mc-sections {
      font-size: 12px;
      color: #aaa;
    }

    .mc-price {
      font-size: 16px;
      font-weight: 700;
      color: var(--guest-primary, #ff4d4d);
      flex-shrink: 0;
    }
  `],
})
export class MenuCardComponent {
  readonly menu = input.required<MenuCatalogItem>();
  readonly select = output<MenuCatalogItem>();
}
