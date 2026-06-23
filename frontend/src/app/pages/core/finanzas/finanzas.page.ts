import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { Location } from '@angular/common';
import { DecimalPipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { FinanzasFacade } from './facades/finanzas.facade';
import { ResumenTabComponent } from './tabs/resumen-tab.component';
import { VentasTabComponent } from './tabs/ventas-tab.component';
import { ProductosTabComponent } from './tabs/productos-tab.component';
import { EmpleadosTabComponent } from './tabs/empleados-tab.component';
import { CajaTabComponent } from './tabs/caja-tab.component';
import { ImpuestosTabComponent } from './tabs/impuestos-tab.component';
import { InformesTabComponent } from './tabs/informes-tab.component';
import type { FinanzasTab, FinanzasPeriod } from './models/finanzas.models';

interface SearchResult {
  id: string;
  label: string;
  sub: string;
  tab: FinanzasTab;
  icon: string;
}

@Component({
  selector: 'app-finanzas',
  templateUrl: './finanzas.page.html',
  styleUrls: ['./finanzas.page.scss'],
  standalone: true,
  providers: [FinanzasFacade],
  imports: [
    DecimalPipe,
    FormsModule,
    ResumenTabComponent,
    VentasTabComponent,
    ProductosTabComponent,
    EmpleadosTabComponent,
    CajaTabComponent,
    ImpuestosTabComponent,
    InformesTabComponent,
  ],
})
export class FinanzasPage implements OnInit {
  protected readonly facade = inject(FinanzasFacade);
  private readonly location = inject(Location);

  protected readonly alertsOpen = signal(false);
  protected readonly searchOpen = signal(false);
  protected readonly searchQuery = signal('');
  protected readonly searchHighlight = signal(0);

  protected readonly searchResults = computed((): SearchResult[] => {
    const q = this.searchQuery().toLowerCase().trim();
    if (!q) return [];

    const results: SearchResult[] = [];

    for (const t of this.tabs) {
      if (t.label.toLowerCase().includes(q)) {
        results.push({
          id: `tab-${t.key}`, label: t.label, sub: `Ir a ${t.label}`,
          tab: t.key, icon: t.icon,
        });
      }
    }

    for (const p of this.facade.topProducts()) {
      if (p.name.toLowerCase().includes(q)) {
        results.push({
          id: `prod-${p.name}`, label: p.name,
          sub: `${p.family} · ${p.units} uds`,
          tab: 'productos', icon: '☷',
        });
        if (results.length >= 20) break;
      }
    }

    const employees = this.facade.employeesReport();
    if (employees) {
      for (const e of employees.items) {
        if (e.name.toLowerCase().includes(q)) {
          results.push({
            id: `emp-${e.name}`, label: e.name,
            sub: `${e.role} · ${e.tickets} tickets`,
            tab: 'empleados', icon: '◓',
          });
          if (results.length >= 20) break;
        }
      }
    }

    return results.slice(0, 20);
  });

  protected readonly tabs: Array<{ key: FinanzasTab; label: string; icon: string }> = [
    { key: 'resumen',    label: 'Resumen',    icon: '◴' },
    { key: 'ventas',     label: 'Ventas',     icon: '⌗' },
    { key: 'productos',  label: 'Productos',  icon: '☷' },
    { key: 'empleados',  label: 'Empleados',  icon: '◓' },
    { key: 'caja',       label: 'Caja',       icon: '◰' },
    { key: 'impuestos',  label: 'Impuestos',  icon: '⌫' },
    { key: 'informes',   label: 'Informes',   icon: '⤓' },
  ];

  protected readonly periods: Array<{ key: FinanzasPeriod; label: string }> = [
    { key: 'today',     label: 'Hoy' },
    { key: 'yesterday', label: 'Ayer' },
    { key: 'week',      label: 'Esta semana' },
    { key: 'month',     label: 'Este mes' },
  ];

  protected readonly alertTypeIcons: Record<string, string> = {
    warning: '⚠', critical: '◉', info: '○',
  };
  protected readonly alertTypeColors: Record<string, string> = {
    warning: '#d18a1c', critical: '#ff4d4d', info: '#0077cc',
  };

  ngOnInit(): void {
    this.facade.init();

    if (typeof window !== 'undefined') {
      window.addEventListener('keydown', (e: KeyboardEvent) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
          e.preventDefault();
          this.searchOpen.update(v => !v);
          if (this.searchOpen()) {
            this.searchQuery.set('');
            this.searchHighlight.set(0);
            setTimeout(() => {
              const el = document.getElementById('global-search-input');
              el?.focus();
            });
          }
        }
        if (e.key === 'Escape' && this.searchOpen()) {
          this.closeSearch();
        }
      });
    }
  }

  protected toggleSearch(): void {
    this.searchOpen.update(v => !v);
    if (this.searchOpen()) {
      this.searchQuery.set('');
      this.searchHighlight.set(0);
      setTimeout(() => {
        const el = document.getElementById('global-search-input');
        el?.focus();
      });
    }
  }

  protected closeSearch(): void {
    this.searchOpen.set(false);
    this.searchQuery.set('');
    this.searchHighlight.set(0);
  }

  protected onSearchKeydown(e: KeyboardEvent): void {
    const results = this.searchResults();
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      this.searchHighlight.update(v => Math.min(v + 1, results.length - 1));
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      this.searchHighlight.update(v => Math.max(v - 1, 0));
    } else if (e.key === 'Enter') {
      e.preventDefault();
      const idx = this.searchHighlight();
      if (results[idx]) {
        this.selectSearchResult(results[idx]);
      }
    }
  }

  protected selectSearchResult(r: SearchResult): void {
    this.facade.pendingSearchFilter.set({tab: r.tab, term: r.label});
    this.facade.setTab(r.tab);
    this.closeSearch();
  }

  protected goBack(): void {
    this.location.back();
  }

  protected toggleAlerts(): void {
    this.alertsOpen.update(v => !v);
  }

  protected closeAllDropdowns(): void {
    this.alertsOpen.set(false);
  }

  protected markAlertsRead(): void {
    this.facade.markAlertsRead();
    this.alertsOpen.set(false);
  }

  protected navigateFromAlert(tab: FinanzasTab): void {
    this.facade.setTab(tab);
    this.alertsOpen.set(false);
  }
}
