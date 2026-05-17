import { Component, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
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
export class FinanzasPage {
  protected readonly facade = inject(FinanzasFacade);
  private readonly router = inject(Router);

  protected readonly alertsOpen = signal(false);
  protected readonly hardwareOpen = signal(false);
  protected readonly locationOpen = signal(false);

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

  protected readonly hwColors: Record<string, string> = {
    ok: '#1a9e5a', warning: '#d18a1c', error: '#ff4d4d',
  };
  protected readonly hwBgs: Record<string, string> = {
    ok: 'transparent', warning: '#fbf2dc55', error: '#ffecec',
  };
  protected readonly alertTypeIcons: Record<string, string> = {
    warning: '⚠', critical: '◉', info: '○',
  };
  protected readonly alertTypeColors: Record<string, string> = {
    warning: '#d18a1c', critical: '#ff4d4d', info: '#0077cc',
  };

  protected goBack(): void {
    this.router.navigateByUrl('/app/gestion');
  }

  protected toggleAlerts(): void {
    this.alertsOpen.update(v => !v);
    this.hardwareOpen.set(false);
    this.locationOpen.set(false);
  }

  protected toggleHardware(): void {
    this.hardwareOpen.update(v => !v);
    this.alertsOpen.set(false);
    this.locationOpen.set(false);
  }

  protected toggleLocation(): void {
    this.locationOpen.update(v => !v);
    this.alertsOpen.set(false);
    this.hardwareOpen.set(false);
  }

  protected closeAllDropdowns(): void {
    this.alertsOpen.set(false);
    this.hardwareOpen.set(false);
    this.locationOpen.set(false);
  }

  protected get hwOverall(): 'ok' | 'warning' | 'error' {
    const hw = this.facade.hardware;
    if (hw.some(h => h.status === 'error')) return 'error';
    if (hw.some(h => h.status === 'warning')) return 'warning';
    return 'ok';
  }

  protected get hwIssueCount(): number {
    return this.facade.hardware.filter(h => h.status !== 'ok').length;
  }

  protected get hwWarningCount(): number {
    return this.facade.hardware.filter(h => h.status === 'warning').length;
  }

  protected get hwErrorCount(): number {
    return this.facade.hardware.filter(h => h.status === 'error').length;
  }

  protected get currentLocation() {
    return this.facade.locations.find(l => l.isCurrent) ?? this.facade.locations[0];
  }

  protected selectLocation(id: string): void {
    this.locationOpen.set(false);
  }

  protected markAlertsRead(): void {
    this.alertsOpen.set(false);
  }

  protected navigateFromAlert(tab: FinanzasTab): void {
    this.facade.setTab(tab);
    this.alertsOpen.set(false);
  }
}
