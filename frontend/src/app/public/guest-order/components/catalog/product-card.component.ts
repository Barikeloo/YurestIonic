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
      class="pc-card"
      [class.pc-unavailable]="!product().available"
      (click)="select.emit(product())"
    >
      <div class="pc-photo">
        @if (product().photo_url) {
          <img [src]="product().photo_url" [alt]="product().name" loading="lazy" />
        } @else {
          <div class="pc-photo-placeholder">🍽️</div>
        }
        @if (!product().available) {
          <div class="pc-agotado">Agotado</div>
        }
      </div>

      <div class="pc-body">
        <span class="pc-name">{{ product().name }}</span>
        <span class="pc-price">{{ product().price_cents / 100 | number:'1.2-2' }}€</span>

        @if (product().allergens.length > 0) {
          <div class="pc-allergens">
            @for (a of product().allergens; track a) {
              <span class="pc-allergen" [title]="a | allergenName">{{ a | allergenIcon }}</span>
            }
          </div>
        }
      </div>

      <div class="pc-add" [class.pc-add--disabled]="!product().available">+</div>
    </button>
  `,
  styles: [`
    .pc-card {
      display: flex;
      flex-direction: column;
      background: #fff;
      border-radius: 14px;
      border: 1.5px solid #f0f0f0;
      overflow: hidden;
      cursor: pointer;
      transition: box-shadow 0.15s, transform 0.1s;
      text-align: left;
      padding: 0;
      width: 100%;

      &:hover:not(.pc-unavailable) {
        box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        transform: translateY(-1px);
      }

      &.pc-unavailable {
        opacity: 0.55;
        cursor: default;
      }
    }

    .pc-photo {
      position: relative;
      width: 100%;
      aspect-ratio: 4/3;
      background: #f5f5f5;
      overflow: hidden;

      img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
    }

    .pc-photo-placeholder {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
    }

    .pc-agotado {
      position: absolute;
      inset: 0;
      background: rgba(0,0,0,0.45);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    .pc-body {
      display: flex;
      flex-direction: column;
      gap: 3px;
      padding: 10px 10px 4px;
      flex: 1;
    }

    .pc-name {
      font-size: 14px;
      font-weight: 600;
      color: #111;
      line-height: 1.3;
    }

    .pc-price {
      font-size: 14px;
      font-weight: 700;
      color: var(--guest-primary, #ff4d4d);
    }

    .pc-allergens {
      display: flex;
      flex-wrap: wrap;
      gap: 2px;
      margin-top: 2px;
    }

    .pc-allergen {
      font-size: 13px;
      cursor: help;
    }

    .pc-add {
      margin: 6px 10px 10px;
      height: 36px;
      background: var(--guest-primary, #ff4d4d);
      color: #fff;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      font-weight: 300;
      transition: opacity 0.15s;

      &--disabled {
        opacity: 0.35;
        background: #aaa;
      }
    }
  `],
})
export class ProductCardComponent {
  readonly product = input.required<ProductCatalogItem>();
  readonly select = output<ProductCatalogItem>();
}
