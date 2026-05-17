import { Component, inject, signal } from '@angular/core';
import { FinanzasFacade } from '../facades/finanzas.facade';

interface ReportTemplate {
  id: string;
  icon: string;
  title: string;
  sub: string;
  color: string;
  formats: string[];
  preview: string[];
}

interface Integration {
  id: string;
  logo: string;
  name: string;
  desc: string;
  color: string;
  features: string[];
  plan?: string;
  connected: boolean;
}

interface ScheduledReport {
  id: number;
  name: string;
  when: string;
  to: string;
  format: string;
  active: boolean;
  next: string;
}

interface ExportHistory {
  name: string;
  format: string;
  size: string;
  date: string;
  user: string;
}

@Component({
  selector: 'app-finanzas-informes-tab',
  templateUrl: './informes-tab.component.html',
  styleUrls: ['./informes-tab.component.scss'],
  standalone: true,
  imports: [],
})
export class InformesTabComponent {
  protected readonly facade = inject(FinanzasFacade);

  protected readonly activeSection = signal<'predefinidos' | 'integraciones' | 'programados'>('predefinidos');
  protected readonly lastSelected = signal<string | null>(null);

  protected readonly templates: ReportTemplate[] = [
    {
      id: 'daily', icon: '📅', title: 'Resumen diario',
      sub: 'Ventas, tickets y caja de un día concreto',
      color: '#ff4d4d', formats: ['PDF', 'CSV'],
      preview: ['Ingresos del día', 'N° tickets / comensales', 'Desglose por método', 'Estado de caja'],
    },
    {
      id: 'products', icon: '🍴', title: 'Ventas por producto',
      sub: 'Ranking completo, ingresos y unidades',
      color: '#d18a1c', formats: ['CSV', 'Excel'],
      preview: ['Producto', 'Familia', 'Unidades', 'Ingresos', 'Stock actual'],
    },
    {
      id: 'families', icon: '📊', title: 'Ventas por familia',
      sub: 'Distribución por categoría',
      color: '#0077cc', formats: ['CSV', 'PDF'],
      preview: ['Bebidas, Tapas, Raciones', '% sobre total', 'Comparativa periodo anterior'],
    },
    {
      id: 'cash', icon: '💼', title: 'Movimientos de caja',
      sub: 'Entradas, salidas y arqueos',
      color: '#1a9e5a', formats: ['CSV', 'PDF'],
      preview: ['Movimientos manuales', 'Sesiones cerradas', 'Descuadres con motivo'],
    },
    {
      id: 'taxes', icon: '🧾', title: 'Desglose de impuestos',
      sub: 'Para el gestor · listo Modelo 303',
      color: '#7857d6', formats: ['PDF', 'CSV'],
      preview: ['Base por tramo', 'IVA repercutido', 'Casillas Modelo 303'],
    },
    {
      id: 'tips', icon: '💚', title: 'Propinas declaradas',
      sub: 'Por empleado y método',
      color: '#1a9e5a', formats: ['CSV'],
      preview: ['Propina por turno', 'Por camarero', 'Solo tarjeta'],
    },
  ];

  protected readonly history: ExportHistory[] = [
    { name: 'Resumen diario · 15/05/2026',       format: 'PDF', size: '184 KB', date: 'Hoy 09:12',    user: 'María G.' },
    { name: 'Ventas por producto · semana 19',    format: 'CSV', size: '42 KB',  date: 'Ayer 18:30',   user: 'Juan P.' },
    { name: 'Modelo 303 · T1 2026',               format: 'PDF', size: '276 KB', date: '12/05 14:15',  user: 'María G.' },
    { name: 'Movimientos caja · abril',           format: 'CSV', size: '128 KB', date: '03/05 11:48',  user: 'Juan P.' },
  ];

  protected readonly integrations: Integration[] = [
    { id: 'holded',   logo: 'H',  name: 'Holded',               desc: 'ERP contable y facturación',      color: '#0077cc', features: ['Sincroniza ventas y tickets', 'Crea facturas automáticamente', 'IVA repercutido al cierre'],      plan: 'Plus',    connected: true  },
    { id: 'quipu',    logo: 'Q',  name: 'Quipu',                desc: 'Software contable autónomos',     color: '#1a9e5a', features: ['Importación de tickets', 'Conciliación bancaria', 'Modelo 303 automático'],                       plan: 'Pro',     connected: false },
    { id: 'a3',       logo: 'A3', name: 'A3 / Wolters',         desc: 'Solución para gestorías',         color: '#d18a1c', features: ['Exportación a A3CON', 'Asientos automáticos', 'Soporte de gestoría'],                            plan: 'Empresa', connected: false },
    { id: 'contasol', logo: 'CS', name: 'Contasol Sage',        desc: 'Contabilidad Sage',               color: '#7857d6', features: ['Plantilla Sage 50/200', 'Periodificación', 'Multi-empresa'],                                                       connected: false },
    { id: 'sii',      logo: 'AE', name: 'SII Hacienda',         desc: 'Suministro Inmediato AEAT',       color: '#ff4d4d', features: ['Envío automático a AEAT', 'Solo facturación > 6M€/año', 'Validación en tiempo real'],                              connected: false },
    { id: 'banco',    logo: '€',  name: 'Conciliación bancaria',desc: 'Conecta tu banco',                color: '#0d0d0d', features: ['BBVA, Santander, CaixaBank', 'Cuadre automático tarjeta', 'Detección Bizum'],                                       connected: false },
  ];

  protected scheduledReports: ScheduledReport[] = [
    { id: 1, name: 'Resumen diario',         when: 'Diario · 02:00',       to: 'contable@latasca.es',     format: 'PDF', active: true,  next: 'Mañana 02:00'     },
    { id: 2, name: 'Ventas por familia',     when: 'Semanal · Lun 08:00',  to: 'miguel@latasca.es',       format: 'CSV', active: true,  next: 'Lun 20/05 08:00'  },
    { id: 3, name: 'Modelo 303 trimestral', when: 'Trimestral',            to: 'gestoria@bvasesores.es',  format: 'PDF', active: false, next: '20/07 09:00'       },
  ];

  protected get activeCount(): number { return this.scheduledReports.filter(s => s.active).length; }

  protected selectReport(id: string): void { this.lastSelected.set(id); }

  protected toggleScheduled(id: number): void {
    this.scheduledReports = this.scheduledReports.map(s =>
      s.id === id ? { ...s, active: !s.active } : s
    );
  }

  protected connectIntegration(id: string): void {
    const intg = this.integrations.find(i => i.id === id);
    if (intg) intg.connected = true;
  }

  protected formatBadgeColor(format: string): string {
    return format === 'PDF' ? '#ff4d4d' : '#1a9e5a';
  }
}
