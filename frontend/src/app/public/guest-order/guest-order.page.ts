import { Component, inject, OnDestroy, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { GuestOrderFacade } from './facades/guest-order.facade';
import { GuestCartService } from './services/guest-cart.service';

@Component({
  selector: 'app-guest-order',
  templateUrl: './guest-order.page.html',
  styleUrls: ['./guest-order.page.scss'],
  standalone: true,
  providers: [GuestOrderFacade, GuestCartService],
  imports: [],
})
export class GuestOrderPage implements OnInit, OnDestroy {
  protected readonly facade = inject(GuestOrderFacade);
  private readonly route = inject(ActivatedRoute);

  ngOnInit(): void {
    const token = this.route.snapshot.paramMap.get('token') ?? '';
    this.facade.init(token);
  }

  ngOnDestroy(): void {
    this.facade.ngOnDestroy();
  }
}
