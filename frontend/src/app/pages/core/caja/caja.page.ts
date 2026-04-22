import { CommonModule } from '@angular/common';
import { Component, OnInit, OnDestroy } from '@angular/core';
import { AuthService } from '../../../services/auth.service';
import { TpvService, TpvCashSession, TpvCashSessionListItem } from '../../../services/tpv.service';
import { OpenCashModalComponent } from '../../../components/open-cash-modal/open-cash-modal.component';
import { CashMovementModalComponent } from '../../../components/cash-movement-modal/cash-movement-modal.component';
import { ClosingWizardComponent, ZReportData } from '../../../components/closing-wizard/closing-wizard.component';
import { CardComponent } from '../../../components/card/card.component';
import { BadgeComponent } from '../../../components/badge/badge.component';
import { BtnComponent } from '../../../components/btn/btn.component';
import { KpiCardComponent } from '../../../components/kpi-card/kpi-card.component';
import { SegmentComponent } from '../../../components/segment/segment.component';

type CajaState = 'pre-apertura' | 'activa' | 'arqueo' | 'historico';

interface LastClosedData {
  id: string;
  opened_by_user_id: string;
  closed_by_user_id: string | null;
  closed_at: string | null;
  final_amount_cents: number | null;
  discrepancy_cents: number | null;
  discrepancy_reason: string | null;
}

interface OrphanSessionData {
  id: string;
  opened_by_user_id: string;
  opened_at: string;
  device_id: string;
}

interface CashSessionSummary {
  initial_amount_cents: number;
  total_sales: number;
  total_cash_payments: number;
  total_card_payments: number;
  total_bizum_payments: number;
  total_other_payments: number;
  total_in_movements: number;
  total_out_movements: number;
  expected_amount: number;
  movements_count: number;
  payments_count: number;
}

@Component({
  selector: 'app-caja',
  templateUrl: './caja.page.html',
  styleUrls: ['./caja.page.scss'],
  imports: [
    CommonModule,
    OpenCashModalComponent,
    CashMovementModalComponent,
    ClosingWizardComponent,
    CardComponent,
    BadgeComponent,
    BtnComponent,
    KpiCardComponent,
    SegmentComponent,
  ],
  standalone: true,
})
export class CajaPage implements OnInit, OnDestroy {
  public state: CajaState = 'pre-apertura';
  public activeSession: TpvCashSession | null = null;
  public loading = true;
  public lastClosed: LastClosedData | null = null;
  public orphanSession: OrphanSessionData | null = null;
  public showOpenModal = false;
  public availableUsers: Array<{ id: string; name: string; initials: string }> = [];
  public sessionSummary: CashSessionSummary | null = null;
  public showMovementModal = false;
  public showWizard = false;
  public currentTime = '';
  public closedSessions: TpvCashSessionListItem[] = [];
  public openCashError: string | null = null;

  private refreshInterval: any;
  private clockInterval: any;
  public readonly deviceId: string;

  constructor(
    private readonly tpvService: TpvService,
    private readonly authService: AuthService,
  ) {
    this.deviceId = this.authService.getDeviceId();
  }

  public ngOnInit(): void {
    this.updateClock();
    this.clockInterval = setInterval(() => this.updateClock(), 1000);
    this.loadActiveSession();
  }

  public ngOnDestroy(): void {
    this.stopRefreshInterval();
    if (this.clockInterval) clearInterval(this.clockInterval);
  }

  private loadActiveSession(): void {
    this.tpvService.getActiveCashSession(this.deviceId).subscribe({
      next: (session) => {
        this.activeSession = session;
        if (session === null) {
          this.state = 'pre-apertura';
          this.loadLastClosedData();
          this.stopRefreshInterval();
        } else {
          this.loading = false;
          switch (session.status) {
            case 'open':
              this.state = 'activa';
              this.loadSessionSummary();
              this.startRefreshInterval();
              break;
            case 'closing':
              this.state = 'arqueo';
              this.showWizard = true;
              this.loadSessionSummary();
              this.stopRefreshInterval();
              break;
            case 'closed':
            case 'abandoned':
              this.state = 'historico';
              this.loadClosedSessions();
              this.stopRefreshInterval();
              break;
          }
        }
      },
      error: () => {
        this.loading = false;
        this.state = 'pre-apertura';
        this.loadLastClosedData();
      },
    });
  }

  private updateClock(): void {
    this.currentTime = new Date().toLocaleTimeString('es-ES', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    });
  }

  private loadSessionSummary(): void {
    if (!this.activeSession) return;
    this.tpvService.getCashSessionSummary(this.activeSession.uuid).subscribe({
      next: (summary) => { this.sessionSummary = summary as unknown as CashSessionSummary; },
      error: (error) => { console.error('Error loading session summary:', error); },
    });
  }

  private startRefreshInterval(): void {
    this.stopRefreshInterval();
    this.refreshInterval = setInterval(() => this.loadSessionSummary(), 30000);
  }

  private stopRefreshInterval(): void {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
      this.refreshInterval = null;
    }
  }

  public onOpenModal(): void {
    this.loadAvailableUsers();
    this.showOpenModal = true;
  }

  private loadAvailableUsers(): void {
    this.tpvService.listUsers(this.deviceId).subscribe({
      next: (response) => {
        this.availableUsers = response.users.map((u: any) => ({
          id: u.user_uuid,
          name: u.name,
          initials: u.name
            .split(' ')
            .slice(0, 2)
            .map((w: string) => w[0].toUpperCase())
            .join(''),
        }));
      },
      error: (error) => { console.error('Error loading users:', error); },
    });
  }

  public onOpenCash(data: { userId: string; initialAmountCents: number; notes?: string }): void {
    this.openCashError = null;
    this.tpvService.openCashSession({
      device_id: this.deviceId,
      opened_by_user_id: data.userId,
      initial_amount_cents: data.initialAmountCents,
      notes: data.notes,
    }).subscribe({
      next: (session) => {
        this.showOpenModal = false;
        this.activeSession = session;
        this.state = 'activa';
        this.loadSessionSummary();
        this.startRefreshInterval();
      },
      error: (error) => {
        this.openCashError = error.message;
        this.showOpenModal = false;
      },
    });
  }

  public onOpenMovementModal(): void {
    this.showMovementModal = true;
  }

  public onRegisterMovement(data: {
    type: string;
    reasonCode: string;
    amountCents: number;
    description?: string;
  }): void {
    if (!this.activeSession) return;
    this.tpvService.registerCashMovement({
      cash_session_id: this.activeSession.uuid,
      type: data.type,
      reason_code: data.reasonCode,
      amount_cents: data.amountCents,
      user_id: this.activeSession.opened_by_user_id,
      description: data.description,
    }).subscribe({
      next: () => {
        this.showMovementModal = false;
        this.loadSessionSummary();
      },
      error: (error) => { alert('Error al registrar el movimiento: ' + error.message); },
    });
  }

  public onStartClosing(): void {
    if (!this.activeSession) return;
    this.tpvService.startClosingCashSession({ cash_session_id: this.activeSession.uuid }).subscribe({
      next: () => {
        this.state = 'arqueo';
        this.showWizard = true;
        this.loadSessionSummary();
        this.stopRefreshInterval();
      },
      error: (error) => { alert('Error al iniciar el cierre: ' + error.message); },
    });
  }

  public onCancelClosing(): void {
    if (!this.activeSession) return;
    this.tpvService.cancelClosingCashSession({ cash_session_id: this.activeSession.uuid }).subscribe({
      next: () => {
        this.state = 'activa';
        this.showWizard = false;
        this.startRefreshInterval();
      },
      error: (error) => { alert('Error al cancelar el cierre: ' + error.message); },
    });
  }

  public onCompleteClosing(data: { countedAmount: number; discrepancyReason?: string }): void {
    if (!this.activeSession) return;
    this.tpvService.closeCashSession({
      cash_session_id: this.activeSession.uuid,
      closed_by_user_id: this.activeSession.opened_by_user_id,
      final_amount_cents: data.countedAmount,
      discrepancy_reason: data.discrepancyReason,
    }).subscribe({
      next: () => {
        this.showWizard = false;
        this.activeSession = null;
        this.state = 'historico';
        this.loadClosedSessions();
      },
      error: (error) => { alert('Error al cerrar la caja: ' + error.message); },
    });
  }

  public get wizardExpectedAmount(): number {
    return this.sessionSummary?.expected_amount ?? 0;
  }

  public get wizardZData(): ZReportData | null {
    if (!this.sessionSummary) return null;
    return {
      tickets: this.sessionSummary.payments_count,
      diners: 0,
      gross: this.sessionSummary.total_sales,
      discounts: 0,
      net: this.sessionSummary.total_sales,
      cash: this.sessionSummary.total_cash_payments,
      card: this.sessionSummary.total_card_payments,
      bizum: this.sessionSummary.total_bizum_payments,
      invitation: this.sessionSummary.total_other_payments,
      invitations: 0,
      invValue: 0,
      cancellations: 0,
      tipsCard: 0,
      initial: this.sessionSummary.initial_amount_cents,
      movIn: this.sessionSummary.total_in_movements,
      movOut: this.sessionSummary.total_out_movements,
    };
  }

  private loadLastClosedData(): void {
    this.tpvService.getLastClosedCashSession().subscribe({
      next: (data) => {
        this.lastClosed = data.last_closed;
        this.orphanSession = data.orphan_session;
        this.loading = false;
      },
      error: () => { this.loading = false; },
    });
  }

  private loadClosedSessions(): void {
    this.tpvService.listCashSessions().subscribe({
      next: (data) => { this.closedSessions = data.sessions; },
      error: (error) => { console.error('Error loading sessions:', error); },
    });
  }

  public formatCents(cents: number | null | undefined): string {
    if (cents == null) return '-';
    return (cents / 100).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  public formatDate(dateString: string | null | undefined): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('es-ES');
  }
}
