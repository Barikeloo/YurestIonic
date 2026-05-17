import { Component, inject, signal, computed } from '@angular/core';
import { FinanzasFacade } from '../facades/finanzas.facade';
import type { Order } from '../models/finanzas.models';

type SortKey = 'id' | 'total' | 'tip' | 'time';

@Component({
  selector: 'app-finanzas-ventas-tab',
  templateUrl: './ventas-tab.component.html',
  styleUrls: ['./ventas-tab.component.scss'],
  standalone: true,
  imports: [],
})
export class VentasTabComponent {
  protected readonly facade = inject(FinanzasFacade);

  protected readonly chartMode    = signal<'line' | 'heatmap'>('line');
  protected readonly filterStatus = signal<'all' | 'paid' | 'cancelled'>('all');
  protected readonly filterMethod = signal<string>('all');
  protected readonly filterZone   = signal<string>('all');
  protected readonly searchQuery  = signal('');
  protected readonly minAmount    = signal('');
  protected readonly maxAmount    = signal('');
  protected readonly sortKey      = signal<SortKey>('time');
  protected readonly sortDir      = signal<'asc' | 'desc'>('desc');
  protected readonly page         = signal(1);
  protected readonly selectedTicket = signal<Order | null>(null);

  protected readonly filteredOrders = computed(() => {
    const q   = this.searchQuery().toLowerCase();
    const min = this.minAmount() ? parseFloat(this.minAmount()) * 100 : null;
    const max = this.maxAmount() ? parseFloat(this.maxAmount()) * 100 : null;
    return this.facade.orders.filter(o => {
      if (this.filterStatus() !== 'all' && o.status !== this.filterStatus()) return false;
      if (this.filterMethod() !== 'all' && o.method !== this.filterMethod()) return false;
      if (this.filterZone()   !== 'all' && !o.zone.startsWith(this.filterZone())) return false;
      if (q && !o.id.toLowerCase().includes(q) && !o.zone.toLowerCase().includes(q)) return false;
      if (min !== null && o.total < min) return false;
      if (max !== null && o.total > max) return false;
      return true;
    });
  });

  protected readonly sortedOrders = computed(() => {
    const key = this.sortKey();
    const dir = this.sortDir() === 'desc' ? -1 : 1;
    return [...this.filteredOrders()].sort((a, b) => {
      if (key === 'total') return (a.total - b.total) * dir;
      if (key === 'tip')   return (a.tip   - b.tip)   * dir;
      return (a.id > b.id ? 1 : -1) * dir;
    });
  });

  protected readonly totalFiltered = computed(() =>
    this.filteredOrders().reduce((s, o) => s + o.total, 0)
  );
  protected readonly cashFiltered = computed(() =>
    this.filteredOrders().filter(o => o.method === 'cash').reduce((s, o) => s + o.total, 0)
  );
  protected readonly cardFiltered = computed(() =>
    this.filteredOrders().filter(o => o.method === 'card').reduce((s, o) => s + o.total, 0)
  );
  protected readonly tipsFiltered = computed(() =>
    this.filteredOrders().reduce((s, o) => s + (o.tip ?? 0), 0)
  );
  protected readonly hasFilters = computed(() =>
    this.filterStatus() !== 'all' || this.filterMethod() !== 'all' ||
    this.filterZone() !== 'all'   || !!this.searchQuery() ||
    !!this.minAmount() || !!this.maxAmount()
  );

  protected readonly heatMax = Math.max(
    ...([] as number[]).concat(...(this.facade.heatmap.map(r => r.hours.map(h => h.v)) as number[][])), 1
  );

  protected linePathD = computed(() => {
    const data = this.facade.byDay;
    const W = 100, H = 60, pad = 3;
    const max   = Math.max(...data.map(d => d.v), 1);
    const min   = Math.min(...data.map(d => d.v), 0);
    const range = max - min || 1;
    return data.map((d, i) => {
      const x = pad + (i / (data.length - 1)) * (W - 2 * pad);
      const y = pad + (1 - (d.v - min) / range) * (H - 2 * pad);
      return `${i === 0 ? 'M' : 'L'} ${x.toFixed(1)} ${y.toFixed(1)}`;
    }).join(' ');
  });

  protected lineAreaD = computed(() => {
    const d = this.linePathD();
    return d ? `${d} L 97 60 L 3 60 Z` : '';
  });

  protected fmt(v: number):    string { return this.facade.fmt(v); }
  protected fmtNum(v: number): string { return this.facade.fmtNum(v); }

  protected statusLabel(s: string): string {
    return ({ paid: 'Pagado', cancelled: 'Anulado', open: 'Abierto' } as Record<string,string>)[s] ?? s;
  }
  protected statusColor(s: string): string {
    return ({ paid: '#1a9e5a', cancelled: '#ff4d4d', open: '#0077cc' } as Record<string,string>)[s] ?? '#a0a0a0';
  }
  protected statusBg(s: string): string {
    return ({ paid: '#e8f7ef', cancelled: '#ffecec', open: '#e8f0fb' } as Record<string,string>)[s] ?? '#f4f4f4';
  }
  protected methodLabel(m: string): string {
    return ({ cash: 'Efectivo', card: 'Tarjeta', bizum: 'Bizum', mixed: 'Mixto', voucher: 'Bono', invitation: 'Invitación' } as Record<string,string>)[m] ?? m;
  }
  protected methodColor(m: string): string {
    return ({ cash: '#1a9e5a', card: '#0077cc', bizum: '#7857d6', mixed: '#d18a1c', voucher: '#a0a0a0', invitation: '#ff4d4d' } as Record<string,string>)[m] ?? '#a0a0a0';
  }
  protected methodBg(m: string): string {
    return ({ cash: '#e8f7ef', card: '#ebf2fe', bizum: '#f0ecfc', mixed: '#fdf3e2', voucher: '#f4f4f4', invitation: '#ffecec' } as Record<string,string>)[m] ?? '#f4f4f4';
  }
  protected taxColor(rate: number): string {
    return ({ 4: '#1a9e5a', 10: '#0077cc', 21: '#ff4d4d' } as Record<number,string>)[rate] ?? '#a0a0a0';
  }
  protected heatBg(v: number):   string { return this.facade.heatBg(v, this.heatMax, '#ff4d4d'); }
  protected heatText(v: number): string { return this.facade.heatText(v, this.heatMax); }

  protected setSort(key: SortKey): void {
    if (this.sortKey() === key) {
      this.sortDir.set(this.sortDir() === 'desc' ? 'asc' : 'desc');
    } else {
      this.sortKey.set(key);
      this.sortDir.set('desc');
    }
  }
  protected sortIcon(key: SortKey): string {
    if (this.sortKey() !== key) return '↕';
    return this.sortDir() === 'desc' ? '↓' : '↑';
  }

  protected clearFilters(): void {
    this.filterStatus.set('all');
    this.filterMethod.set('all');
    this.filterZone.set('all');
    this.searchQuery.set('');
    this.minAmount.set('');
    this.maxAmount.set('');
  }

  protected openTicket(order: Order): void { this.selectedTicket.set(order); }
  protected closeTicket(): void            { this.selectedTicket.set(null); }

  protected readonly pages = [1, 2, 3, 4, 5];

  protected readonly statuses = [
    { v: 'all'       as const, l: 'Todos'   },
    { v: 'paid'      as const, l: 'Pagado'  },
    { v: 'cancelled' as const, l: 'Anulado' },
  ];
  protected readonly methods = [
    { v: 'all',    l: 'Todos pagos' },
    { v: 'cash',   l: 'Efectivo'   },
    { v: 'card',   l: 'Tarjeta'    },
    { v: 'bizum',  l: 'Bizum'      },
    { v: 'mixed',  l: 'Mixto'      },
  ];
}
