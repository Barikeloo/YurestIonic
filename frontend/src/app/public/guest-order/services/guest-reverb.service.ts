import { Injectable, OnDestroy } from '@angular/core';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { environment } from '../../../../environments/environment';

@Injectable()
export class GuestReverbService implements OnDestroy {
  private echo: Echo<'reverb'> | null = null;
  private subscribedChannel: string | null = null;

  private getEcho(): Echo<'reverb'> {
    if (!this.echo) {
      (window as any).Pusher = Pusher;
      this.echo = new Echo({
        broadcaster: 'reverb',
        key: environment.reverb.key,
        wsHost: environment.reverb.host,
        wsPort: environment.reverb.port,
        wssPort: environment.reverb.port,
        forceTLS: environment.reverb.scheme === 'https',
        enabledTransports: ['ws', 'wss'],
      });
    }
    return this.echo;
  }

  subscribeToOrder(orderId: string, handler: (data: { event_type: string; guest_name: string | null; round_number: number | null }) => void): void {
    const channelName = `guest-order.${orderId}`;
    if (this.subscribedChannel === channelName) return;

    if (this.subscribedChannel) {
      this.echo?.leave(this.subscribedChannel);
    }

    this.subscribedChannel = channelName;
    this.getEcho().channel(channelName).listen('.guest.order_activity', handler);
  }

  unsubscribe(): void {
    if (this.subscribedChannel) {
      this.echo?.leave(this.subscribedChannel);
      this.subscribedChannel = null;
    }
  }

  ngOnDestroy(): void {
    this.unsubscribe();
    this.echo?.disconnect();
  }
}
