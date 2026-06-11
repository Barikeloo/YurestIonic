import { Component, computed, inject, signal } from '@angular/core';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { FinanzasFacade } from '../facades/finanzas.facade';
import { IconComponent, IconName } from '../../../../shared/components/icon/icon.component';

interface ReportTemplate {
  id: string;
  iconName: IconName;
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

@Component({
  selector: 'app-finanzas-informes-tab',
  templateUrl: './informes-tab.component.html',
  styleUrls: ['./informes-tab.component.scss'],
  standalone: true,
  imports: [IconComponent],
})
export class InformesTabComponent {
  protected readonly facade = inject(FinanzasFacade);
  private readonly sanitizer = inject(DomSanitizer);

  protected readonly previewSafeUrl = computed<SafeResourceUrl | null>(() => {
    const p = this.facade.preview();
    return p ? this.sanitizer.bypassSecurityTrustResourceUrl(p.url) : null;
  });

  protected readonly activeSection = signal<'predefinidos' | 'integraciones' | 'programados'>('predefinidos');
  protected readonly lastSelected = signal<string | null>(null);

  protected readonly templates: ReportTemplate[] = [
    {
      id: 'daily', iconName: 'calendar', title: 'Resumen diario',
      sub: 'Ventas, tickets y caja de un día concreto',
      color: '#ff4d4d', formats: ['PDF', 'CSV'],
      preview: ['Ingresos del día', 'N° tickets / comensales', 'Desglose por método', 'Estado de caja'],
    },
    {
      id: 'products', iconName: 'utensils', title: 'Ventas por producto',
      sub: 'Ranking completo, ingresos y unidades',
      color: '#d18a1c', formats: ['PDF', 'CSV'],
      preview: ['Producto', 'Familia', 'Unidades', 'Ingresos', 'Stock actual'],
    },
    {
      id: 'families', iconName: 'bar-chart', title: 'Ventas por familia',
      sub: 'Distribución por categoría',
      color: '#0077cc', formats: ['CSV', 'PDF'],
      preview: ['Bebidas, Tapas, Raciones', '% sobre total', 'Comparativa periodo anterior'],
    },
    {
      id: 'cash', iconName: 'wallet', title: 'Movimientos de caja',
      sub: 'Entradas, salidas y arqueos',
      color: '#1a9e5a', formats: ['CSV', 'PDF'],
      preview: ['Movimientos manuales', 'Sesiones cerradas', 'Descuadres con motivo'],
    },
    {
      id: 'taxes', iconName: 'receipt', title: 'Desglose de impuestos',
      sub: 'Para el gestor · listo Modelo 303',
      color: '#7857d6', formats: ['PDF', 'CSV'],
      preview: ['Base por tramo', 'IVA repercutido', 'Casillas Modelo 303'],
    },
    {
      id: 'tips', iconName: 'coins', title: 'Propinas declaradas',
      sub: 'Por empleado y método',
      color: '#1a9e5a', formats: ['PDF', 'CSV'],
      preview: ['Propina por empleado', 'Tickets y ventas', '% sobre ventas'],
    },
  ];

  protected formatSize(bytes: number): string {
    if (bytes >= 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    if (bytes >= 1024) return Math.round(bytes / 1024) + ' KB';
    return bytes + ' B';
  }

  protected formatDate(iso: string): string {
    const d = new Date(iso);
    if (isNaN(d.getTime())) return iso;
    return d.toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
  }

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

  protected download(r: ReportTemplate, format: string): void {
    this.facade.downloadReport(r.id, format);
  }

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
