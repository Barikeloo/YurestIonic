import { Injectable, computed, signal } from '@angular/core';
import {
  MOCK_ALERTS,
  MOCK_BY_DAY,
  MOCK_BY_FAMILY,
  MOCK_BY_HOUR,
  MOCK_BY_HOUR_PREV,
  MOCK_BY_HOUR_LAST_WEEK,
  MOCK_BY_METHOD,
  MOCK_CANCELLATIONS,
  MOCK_CANNIBALS,
  MOCK_CASH_HISTORY,
  MOCK_CASH_SESSION,
  MOCK_CROSS_SELL,
  MOCK_DEAD_STOCK,
  MOCK_EMPLOYEES,
  MOCK_FORECAST,
  MOCK_HEATMAP,
  MOCK_HARDWARE,
  MOCK_LOCATIONS,
  MOCK_META,
  MOCK_OPEN_TABLES,
  MOCK_ORDERS,
  MOCK_PENDING_PAYMENTS,
  MOCK_PRE_CLOSE,
  MOCK_PRODUCT_RANKING,
  MOCK_PRODUCT_TRENDS,
  MOCK_QUARTERLY,
  MOCK_TAX_BREAKDOWN,
  MOCK_SPARKS,
  MOCK_SUMMARY,
  MOCK_TICKET_DETAIL,
  MOCK_TOP_PRODUCTS,
  MOCK_ZONES_LAYOUT,
} from '../mock/finanzas.mock';
import type { FinanzasPeriod, FinanzasTab, ResumenVariant } from '../models/finanzas.models';

@Injectable()
export class FinanzasFacade {

  private readonly _activeTab = signal<FinanzasTab>('resumen');
  private readonly _period    = signal<FinanzasPeriod>('today');
  private readonly _resumenVariant = signal<ResumenVariant>('A');
  private readonly _showCompare = signal(true);

  public readonly activeTab     = computed(() => this._activeTab());
  public readonly period        = computed(() => this._period());
  public readonly resumenVariant = computed(() => this._resumenVariant());
  public readonly showCompare   = computed(() => this._showCompare());
  public readonly periodLabel   = computed(() => {
    const map: Record<FinanzasPeriod, string> = {
      today:     'Hoy · jueves 14 mayo 2026',
      yesterday: 'Ayer · miércoles 13 mayo 2026',
      week:      'Esta semana · 11 — 14 mayo 2026',
      month:     'Este mes · mayo 2026',
    };
    return map[this._period()];
  });

  public setTab(tab: FinanzasTab): void     { this._activeTab.set(tab); }
  public setPeriod(p: FinanzasPeriod): void { this._period.set(p); }
  public setResumenVariant(v: ResumenVariant): void { this._resumenVariant.set(v); }
  public setShowCompare(v: boolean): void   { this._showCompare.set(v); }

  public readonly meta          = MOCK_META;
  public readonly summary       = MOCK_SUMMARY;
  public readonly sparks        = MOCK_SPARKS;
  public readonly byHour        = MOCK_BY_HOUR;
  public readonly byHourPrev    = MOCK_BY_HOUR_PREV;
  public readonly byHourLastWeek = MOCK_BY_HOUR_LAST_WEEK;
  public readonly byDay         = MOCK_BY_DAY;
  public readonly heatmap       = MOCK_HEATMAP;
  public readonly topProducts   = MOCK_TOP_PRODUCTS;
  public readonly deadStock     = MOCK_DEAD_STOCK;
  public readonly byFamily      = MOCK_BY_FAMILY;
  public readonly byMethod      = MOCK_BY_METHOD;
  public readonly orders        = MOCK_ORDERS;
  public readonly alerts        = MOCK_ALERTS;
  public readonly employees     = MOCK_EMPLOYEES;
  public readonly crossSell     = MOCK_CROSS_SELL;
  public readonly productTrends = MOCK_PRODUCT_TRENDS;
  public readonly openTables    = MOCK_OPEN_TABLES;
  public readonly pendingPayments = MOCK_PENDING_PAYMENTS;
  public readonly cancellations = MOCK_CANCELLATIONS;
  public readonly preClose      = MOCK_PRE_CLOSE;
  public readonly cashSession   = MOCK_CASH_SESSION;
  public readonly cashHistory   = MOCK_CASH_HISTORY;
  public readonly forecast      = MOCK_FORECAST;
  public readonly quarterly     = MOCK_QUARTERLY;
  public readonly taxBreakdown  = MOCK_TAX_BREAKDOWN;
  public readonly hardware       = MOCK_HARDWARE;
  public readonly locations     = MOCK_LOCATIONS;
  public readonly ticketDetail   = MOCK_TICKET_DETAIL;
  public readonly productRanking = MOCK_PRODUCT_RANKING;
  public readonly zonesLayout    = MOCK_ZONES_LAYOUT;
  public readonly cannibals      = MOCK_CANNIBALS;

  public readonly totalRevenue  = computed(() => this.summary.revenue.v);
  public readonly cashTheoretical = computed(() =>
    this.cashSession.initial + this.byMethod.cash.v + this.cashSession.cashIn - this.cashSession.cashOut
  );
  public readonly alertCount = computed(() => this.alerts.filter(a => a.type === 'critical').length);
  public readonly unreadAlerts = computed(() => this.alerts.length);

  public fmt(cents: number): string {
    return (cents / 100).toLocaleString('es-ES', { style: 'currency', currency: 'EUR', minimumFractionDigits: 2 });
  }

  public fmtNum(cents: number): string {
    return (cents / 100).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  public fmtInt(n: number): string {
    return n.toLocaleString('es-ES');
  }

  public fmtPct(n: number, decimals = 1): string {
    return `${n >= 0 ? '+' : ''}${n.toFixed(decimals)}%`;
  }

  public sparklinePath(data: number[], W = 100, H = 24, pad = 2): string {
    if (!data.length) return '';
    const max = Math.max(...data, 1);
    const min = Math.min(...data, 0);
    const range = max - min || 1;
    return data
      .map((v, i) => {
        const x = pad + (i / (data.length - 1)) * (W - 2 * pad);
        const y = pad + (1 - (v - min) / range) * (H - 2 * pad);
        return `${i === 0 ? 'M' : 'L'} ${x.toFixed(1)} ${y.toFixed(1)}`;
      })
      .join(' ');
  }

  public sparklineArea(data: number[], W = 100, H = 24, pad = 2): string {
    const line = this.sparklinePath(data, W, H, pad);
    if (!line) return '';
    const lastX = (W - pad).toFixed(1);
    const firstX = pad.toFixed(1);
    return `${line} L ${lastX} ${H} L ${firstX} ${H} Z`;
  }

  public barPct(v: number, max: number): number {
    return max > 0 ? Math.max((v / max) * 95, v > 0 ? 2 : 0) : 0;
  }

  /** Returns background color with opacity based on heatmap intensity */
  public heatBg(v: number, max: number, color = '#ff4d4d'): string {
    const t = v / (max || 1);
    if (t < 0.04) return 'rgba(250,250,250,1)';
    const opacity = Math.round((0.08 + t * 0.92) * 255).toString(16).padStart(2, '0');
    return `${color}${opacity}`;
  }

  public heatText(v: number, max: number): string {
    return v / (max || 1) > 0.55 ? '#fff' : '#0d0d0d';
  }

  public donutSegments(data: { label: string; v: number; color: string }[], size: number, thickness: number): Array<{
    color: string; label: string; v: number; frac: number; dashArray: string; dashOffset: string;
  }> {
    const r = (size - thickness) / 2;
    const circ = 2 * Math.PI * r;
    const total = data.reduce((s, d) => s + d.v, 0) || 1;
    const gap = 1.5;
    let offset = 0;
    return data.map(d => {
      const frac = d.v / total;
      const len = Math.max(circ * frac - gap, 1);
      const seg = {
        color: d.color,
        label: d.label,
        v: d.v,
        frac,
        dashArray: `${len} ${circ}`,
        dashOffset: `${-offset}`,
      };
      offset += circ * frac;
      return seg;
    });
  }

  public maxVal(data: { v: number }[]): number {
    return Math.max(...data.map(d => d.v), 1);
  }

  public tabLabel(tab: FinanzasTab): string {
    const labels: Record<FinanzasTab, string> = {
      resumen: 'Resumen', ventas: 'Ventas', productos: 'Productos',
      empleados: 'Empleados', caja: 'Caja', impuestos: 'Impuestos', informes: 'Informes',
    };
    return labels[tab];
  }
}
