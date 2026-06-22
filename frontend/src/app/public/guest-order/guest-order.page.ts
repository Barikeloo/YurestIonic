import { Component, effect, inject, OnDestroy, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { GuestOrderFacade } from './facades/guest-order.facade';
import { GuestCartService } from './services/guest-cart.service';
import { TableStatusComponent } from './components/table-status/table-status.component';
import { CatalogComponent } from './components/catalog/catalog.component';
import { ProductDetailComponent } from './components/product-detail/product-detail.component';

@Component({
  selector: 'app-guest-order',
  templateUrl: './guest-order.page.html',
  styleUrls: ['./guest-order.page.scss'],
  standalone: true,
  providers: [GuestOrderFacade, GuestCartService],
  imports: [CommonModule, TableStatusComponent, CatalogComponent, ProductDetailComponent],
})
export class GuestOrderPage implements OnInit, OnDestroy {
  protected readonly facade = inject(GuestOrderFacade);
  private readonly route = inject(ActivatedRoute);

  constructor() {
    effect(() => {
      const color = this.facade.tableStatus()?.restaurant.primary_color;
      if (color) {
        document.documentElement.style.setProperty('--guest-primary', color);
      }
    });
  }

  ngOnInit(): void {
    const token = this.route.snapshot.paramMap.get('token') ?? '';
    this.facade.init(token);
  }

  ngOnDestroy(): void {
    this.facade.ngOnDestroy();
  }
}
