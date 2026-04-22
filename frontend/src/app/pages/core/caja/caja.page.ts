import { CommonModule } from '@angular/common';
import { Component, OnInit, OnDestroy } from '@angular/core';
import { TpvService, TpvCashSession } from '../../../services/tpv.service';
import { Observable } from 'rxjs';
import { OpenCashModalComponent } from '../../../components/open-cash-modal/open-cash-modal.component';
import { CashMovementModalComponent } from '../../../components/cash-movement-modal/cash-movement-modal.component';
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
  total_sales_cents: number;
  total_cash_cents: number;
  total_card_cents: number;
  total_other_cents: number;
  cash_in_cents: number;
  cash_out_cents: number;
  tips_cents: number;
  sales_count: number;
  cancelled_sales_count: number;
}

@Component({
  selector: 'app-caja',
  templateUrl: './caja.page.html',
  styleUrls: ['./caja.page.scss'],
  imports: [CommonModule, OpenCashModalComponent, CashMovementModalComponent, CardComponent, BadgeComponent, BtnComponent, KpiCardComponent, SegmentComponent],
  standalone: true,
})
export class CajaPage implements OnInit, OnDestroy {
  public state: CajaState = 'pre-apertura';
  public activeSession$: Observable<TpvCashSession | null>;
  public activeSession: TpvCashSession | null = null;
  public loading = true;
  public lastClosed: LastClosedData | null = null;
  public orphanSession: OrphanSessionData | null = null;
  public showOpenModal = false;
  public availableUsers: Array<{ id: string; name: string; initials: string }> = [];
  public sessionSummary: CashSessionSummary | null = null;
  public showMovementModal = false;
  public currentTime = '';
  private refreshInterval: any;
  private clockInterval: any;

  constructor(private readonly tpvService: TpvService) {
    this.activeSession$ = this.tpvService.getActiveCashSession();
  }

  public ngOnInit(): void {
    this.updateClock();
    this.clockInterval = setInterval(() => {
      this.updateClock();
    }, 1000);

    this.activeSession$.subscribe({
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
              this.loadSessionSummary();
              this.stopRefreshInterval();
              break;
            case 'closed':
            case 'abandoned':
              this.state = 'historico';
              this.stopRefreshInterval();
              break;
          }
        }
      },
      error: (error) => {
        console.error('Error loading cash session:', error);
        this.loading = false;
        this.state = 'pre-apertura';
        this.loadLastClosedData();
        this.stopRefreshInterval();
      },
    });
  }

  public ngOnDestroy(): void {
    this.stopRefreshInterval();
    if (this.clockInterval) {
      clearInterval(this.clockInterval);
    }
  }

  private updateClock(): void {
    this.currentTime = new Date().toLocaleTimeString('es-ES', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    });
  }

  private loadSessionSummary(): void {
    if (this.activeSession) {
      this.tpvService.getCashSessionSummary(this.activeSession.uuid).subscribe({
        next: (summary) => {
          this.sessionSummary = summary;
        },
        error: (error) => {
          console.error('Error loading session summary:', error);
        },
      });
    }
  }

  private startRefreshInterval(): void {
    this.stopRefreshInterval();
    this.refreshInterval = setInterval(() => {
      this.loadSessionSummary();
    }, 30000); // Actualizar cada 30 segundos
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
    // TODO: Obtener device_id y restaurant_uuid desde contexto
    const deviceId = 'device-uuid'; // Placeholder
    this.tpvService.listUsers(deviceId).subscribe({
      next: (response) => {
        this.availableUsers = response.users;
      },
      error: (error) => {
        console.error('Error loading users:', error);
      },
    });
  }

  public onOpenCash(data: { userId: string; initialAmountCents: number; notes?: string }): void {
    // TODO: Obtener device_id desde contexto
    const deviceId = 'device-uuid'; // Placeholder

    this.tpvService.openCashSession({
      device_id: deviceId,
      opened_by_user_id: data.userId,
      initial_amount_cents: data.initialAmountCents,
      notes: data.notes,
    }).subscribe({
      next: (session) => {
        this.showOpenModal = false;
        this.activeSession = session;
        this.state = 'activa';
      },
      error: (error) => {
        console.error('Error opening cash session:', error);
        alert('Error al abrir la caja: ' + error.message);
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
    if (this.activeSession) {
      // TODO: Obtener user_id desde contexto
      const userId = 'user-uuid'; // Placeholder

      this.tpvService.registerCashMovement({
        cash_session_id: this.activeSession.uuid,
        type: data.type,
        reason_code: data.reasonCode,
        amount_cents: data.amountCents,
        user_id: userId,
        description: data.description,
      }).subscribe({
        next: () => {
          this.showMovementModal = false;
          this.loadSessionSummary(); // Recargar el resumen después de registrar movimiento
        },
        error: (error) => {
          console.error('Error registering movement:', error);
          alert('Error al registrar el movimiento: ' + error.message);
        },
      });
    }
  }

  public onStartClosing(): void {
    if (this.activeSession) {
      this.tpvService.startClosingCashSession({
        cash_session_id: this.activeSession.uuid,
      }).subscribe({
        next: () => {
          this.state = 'arqueo';
          this.loadSessionSummary();
        },
        error: (error) => {
          console.error('Error starting closing:', error);
          alert('Error al iniciar el cierre: ' + error.message);
        },
      });
    }
  }

  public onCancelClosing(): void {
    if (this.activeSession) {
      this.tpvService.cancelClosingCashSession({
        cash_session_id: this.activeSession.uuid,
      }).subscribe({
        next: () => {
          this.state = 'activa';
        },
        error: (error) => {
          console.error('Error cancelling closing:', error);
          alert('Error al cancelar el cierre: ' + error.message);
        },
      });
    }
  }

  private loadLastClosedData(): void {
    this.tpvService.getLastClosedCashSession().subscribe({
      next: (data) => {
        this.lastClosed = data.last_closed;
        this.orphanSession = data.orphan_session;
        this.loading = false;
      },
      error: (error) => {
        console.error('Error loading last closed session:', error);
        this.loading = false;
      },
    });
  }

  public formatCents(cents: number): string {
    return (cents / 100).toFixed(2);
  }

  public formatDate(dateString: string | null | undefined): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('es-ES');
  }
}
