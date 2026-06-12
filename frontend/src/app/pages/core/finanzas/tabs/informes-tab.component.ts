import { Component, computed, inject, signal } from '@angular/core';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { FinanzasFacade } from '../facades/finanzas.facade';
import { IconComponent, IconName } from '../../../../shared/components/icon/icon.component';
import type { ScheduledReport, CreateScheduledReportPayload, UpdateScheduledReportPayload } from '../models/finanzas.models';

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

type ModalMode = 'create' | 'edit' | null;

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
  protected readonly modalMode = signal<ModalMode>(null);
  protected readonly editingReport = signal<ScheduledReport | null>(null);

  // ── Modal form fields ──────────────────────────────────────────────────────
  protected form = {
    name: signal(''),
    reportType: signal('daily'),
    format: signal('PDF'),
    frequency: signal('daily'),
    time: signal('02:00'),
    weekday: signal<number>(1),
    dayOfMonth: signal<number>(1),
    recipients: signal<string[]>(['']),
    active: signal(true),
  };

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
      color: '#0077cc', formats: ['PDF', 'CSV'],
      preview: ['Bebidas, Tapas, Raciones', '% sobre total', 'Comparativa periodo anterior'],
    },
    {
      id: 'cash', iconName: 'wallet', title: 'Movimientos de caja',
      sub: 'Entradas, salidas y arqueos',
      color: '#1a9e5a', formats: ['PDF', 'CSV'],
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

  protected readonly activeCount = computed(() => this.facade.scheduledReports().filter(s => s.active).length);

  protected readonly frequencyLabel = (s: ScheduledReport): string => {
    const freq: Record<string, string> = { daily: 'Diario', weekly: 'Semanal', monthly: 'Mensual', quarterly: 'Trimestral' };
    const f = freq[s.frequency] ?? s.frequency;
    if (s.frequency === 'weekly') return `${f} · ${this.weekdayLabel(s.weekday!)} ${s.time}`;
    if (s.frequency === 'monthly') return `${f} · Día ${s.day_of_month} ${s.time}`;
    return `${f} · ${s.time}`;
  };

  protected readonly recipientsLabel = (s: ScheduledReport): string => s.recipients.join(', ');

  protected readonly nextRunLabel = (s: ScheduledReport): string => {
    if (!s.active) return '—';
    return this.formatDate(s.next_run_at);
  };

  protected readonly typeLabel = (type: string): string => {
    const labels: Record<string, string> = { daily: 'Resumen diario', products: 'Ventas por producto', families: 'Ventas por familia', cash: 'Movimientos de caja', tips: 'Propinas', taxes: 'Modelo 303' };
    return labels[type] ?? type;
  };

  protected openCreateModal(): void {
    this.modalMode.set('create');
    this.editingReport.set(null);
    this.form.name.set('');
    this.form.reportType.set('daily');
    this.form.format.set('PDF');
    this.form.frequency.set('daily');
    this.form.time.set('02:00');
    this.form.weekday.set(1);
    this.form.dayOfMonth.set(1);
    this.form.recipients.set(['']);
    this.form.active.set(true);
  }

  protected openEditModal(s: ScheduledReport): void {
    this.modalMode.set('edit');
    this.editingReport.set(s);
    this.form.name.set(s.name);
    this.form.reportType.set(s.report_type);
    this.form.format.set(s.format);
    this.form.frequency.set(s.frequency);
    this.form.time.set(s.time);
    this.form.weekday.set(s.weekday ?? 1);
    this.form.dayOfMonth.set(s.day_of_month ?? 1);
    this.form.recipients.set(s.recipients.length ? s.recipients : ['']);
    this.form.active.set(s.active);
  }

  protected closeModal(): void {
    this.modalMode.set(null);
    this.editingReport.set(null);
  }

  protected updateRecipient(index: number, event: Event): void {
    const value = (event.target as HTMLInputElement).value;
    this.form.recipients.update(r => r.map((e, i) => i === index ? value : e));
  }

  protected addRecipient(): void {
    this.form.recipients.update(r => [...r, '']);
  }

  protected removeRecipient(i: number): void {
    this.form.recipients.update(r => r.filter((_, idx) => idx !== i));
  }

  protected trackRecipient(_i: number): number { return _i; }

  protected toggleActive(): void {
    this.form.active.update(a => !a);
  }

  protected submitModal(): void {
    const recipients = this.form.recipients().filter(r => r.trim() !== '');
    if (!recipients.length) return;

    const base = {
      report_type: this.form.reportType(),
      format: this.form.format(),
      frequency: this.form.frequency(),
      time: this.form.time(),
      weekday: this.form.frequency() === 'weekly' ? this.form.weekday() : null,
      day_of_month: this.form.frequency() === 'monthly' ? this.form.dayOfMonth() : null,
      recipients,
      name: this.form.name() || this.typeLabel(this.form.reportType()),
      active: this.form.active(),
    };

    if (this.modalMode() === 'create') {
      this.facade.createScheduledReport(base as CreateScheduledReportPayload);
    } else {
      const uuid = this.editingReport()!.uuid;
      this.facade.updateScheduledReport(uuid, base as UpdateScheduledReportPayload);
    }

    this.closeModal();
  }

  protected deleteScheduled(uuid: string): void {
    if (confirm('¿Eliminar esta programación?')) {
      this.facade.deleteScheduledReport(uuid);
    }
  }

  protected sendNow(uuid: string): void {
    this.facade.sendScheduledReportNow(uuid).subscribe();
  }

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

  protected selectReport(id: string): void { this.lastSelected.set(id); }

  protected download(r: ReportTemplate, format: string): void {
    this.facade.downloadReport(r.id, format);
  }

  protected connectIntegration(id: string): void {
    const intg = this.integrations.find(i => i.id === id);
    if (intg) intg.connected = true;
  }

  protected formatBadgeColor(format: string): string {
    return format === 'PDF' ? '#ff4d4d' : '#1a9e5a';
  }

  private weekdayLabel(d: number): string {
    return ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'][d - 1] ?? '';
  }
}
