import { Component, input, output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ProductCatalogItem } from '../../models/guest-catalog.models';
import { AllergenIconPipe, AllergenNamePipe } from '../../pipes/allergen-icon.pipe';

@Component({
  selector: 'app-product-card',
  standalone: true,
  imports: [CommonModule, AllergenIconPipe, AllergenNamePipe],
  template: `
    <button
      class="pc"
      [class.pc--unavailable]="!product().available"
      (click)="select.emit(product())"
    >
      <div class="pc-info">
        <p class="pc-name">{{ product().name }}</p>

        @if (product().allergens.length > 0) {
          <p class="pc-allergens">
            @for (a of product().allergens; track a) {
              <span [title]="a | allergenName">{{ a | allergenIcon }}</span>
            }
          </p>
        }

        <p class="pc-price">
          {{ product().price_cents / 100 | number:'1.2-2' }}€
          @if (product().variants.length > 0) {
            <span class="pc-from">desde</span>
          }
        </p>

        @if (!product().available) {
          <span class="pc-badge-unavail">Agotado</span>
        } @else if (product().variants.length > 0 || product().modifiers.length > 0) {
          <span class="pc-badge-custom">Personalizable</span>
        }
      </div>

      <div class="pc-photo">
        @if (product().photo_url) {
          <img [src]="product().photo_url" [alt]="product().name" loading="lazy" />
        } @else {
          <div class="pc-no-photo">🍽️</div>
        }
        @if (product().available) {
          <div class="pc-add-badge">+</div>
        }
      </div>
    </button>
  `,
  styles: [`
    .pc {
      display: flex;
      align-items: center;
      gap: 12px;
      width: 100%;
      background: #fff;
      border: none;
      border-bottom: 1px solid #f2f2f2;
      padding: 14px 16px;
      cursor: pointer;
      text-align: left;
      transition: background 0.12s;

      &:hover:not(.pc--unavailable) { background: #fafafa; }
      &--unavailable { opacity: 0.55; cursor: default; }
      &:active:not(.pc--unavailable) { background: #f5f5f5; }
    }

    .pc-info {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 3px;
      min-width: 0;
    }

    .pc-name {
      font-size: 15px;
      font-weight: 600;
      color: #111;
      margin: 0;
      line-height: 1.35;
    }

    .pc-allergens {
      font-size: 14px;
      margin: 0;
      display: flex;
      gap: 3px;
      flex-wrap: wrap;
    }

    .pc-price {
      font-size: 15px;
      font-weight: 700;
      color: #111;
      margin: 4px 0 0;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .pc-from {
      font-size: 12px;
      font-weight: 400;
      color: #aaa;
    }

    .pc-badge-custom, .pc-badge-unavail {
      display: inline-block;
      font-size: 11px;
      font-weight: 700;
      border-radius: 20px;
      padding: 2px 8px;
      width: fit-content;
      margin-top: 2px;
    }

    .pc-badge-custom {
      background: #fff3e0;
      color: #e65100;
    }

    .pc-badge-unavail {
      background: #f5f5f5;
      color: #999;
    }

    .pc-photo {
      position: relative;
      width: 90px;
      height: 90px;
      border-radius: 12px;
      overflow: hidden;
      flex-shrink: 0;
      background: #f5f5f5;

      img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
    }

    .pc-no-photo {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
    }

    .pc-add-badge {
      position: absolute;
      bottom: 6px;
      right: 6px;
      width: 28px;
      height: 28px;
      background: var(--guest-primary, #ff4d4d);
      color: #fff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      font-weight: 300;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    }
  `],
})
export class ProductCardComponent {
  readonly product = input.required<ProductCatalogItem>();
  readonly select = output<ProductCatalogItem>();
}
