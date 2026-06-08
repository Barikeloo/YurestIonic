import { Injectable, OnDestroy } from '@angular/core';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { environment } from '../../../environments/environment';

declare global {
  interface Window {
    Pusher: typeof Pusher;
  }
}

@Injectable({ providedIn: 'root' })
export class EchoService implements OnDestroy {
  private echo: Echo<'reverb'> | null = null;

  private getEcho(): Echo<'reverb'> {
    if (!this.echo) {
      window.Pusher = Pusher;
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

  /**
   * Subscribe to a public channel and listen to a named event.
   * Returns an unsubscribe callback — call it when done to avoid leaks.
   */
  listenOnce<T>(
    channelName: string,
    eventName: string,
    handler: (data: T) => void,
  ): () => void {
    const echo = this.getEcho();
    echo.channel(channelName).listen(`.${eventName}`, handler);

    return () => {
      echo.leave(channelName);
    };
  }

  ngOnDestroy(): void {
    this.echo?.disconnect();
    this.echo = null;
  }
}
