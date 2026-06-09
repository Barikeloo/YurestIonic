import { Component, inject, OnInit, signal } from '@angular/core';
import { Location } from '@angular/common';
import { DecimalPipe } from '@angular/common';
import { FinanzasFacade } from './facades/finanzas.facade';
import { ResumenTabComponent } from './tabs/resumen-tab.component';
import { VentasTabComponent } from './tabs/ventas-tab.component';
import { ProductosTabComponent } from './tabs/productos-tab.component';
import { EmpleadosTabComponent } from './tabs/empleados-tab.component';
import { CajaTabComponent } from './tabs/caja-tab.component';
import { ImpuestosTabComponent } from './tabs/impuestos-tab.component';
import { InformesTabComponent } from './tabs/informes-tab.component';
import type { FinanzasTab, FinanzasPeriod } from './models/finanzas.models';

@Component({
  selector: 'app-finanzas',
  templateUrl: './finanzas.page.html',
  styleUrls: ['./finanzas.page.scss'],
  standalone: true,
  providers: [FinanzasFacade],
  imports: [
    DecimalPipe,
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
