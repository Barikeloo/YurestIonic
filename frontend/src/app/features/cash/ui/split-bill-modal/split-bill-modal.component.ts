import { Component, EventEmitter, Input, OnChanges, OnDestroy, Output, SimpleChanges, OnInit } from '@angular/core';

import { FormsModule } from '@angular/forms';
import { Subject } from 'rxjs';
import { debounceTime, takeUntil } from 'rxjs/operators';
import { BadgeComponent } from '../../../../shared/components/badge/badge.component';
import { BtnComponent } from '../../../../shared/components/btn/btn.component';
import { CardComponent } from '../../../../shared/components/card/card.component';
import { DinersStatusComponent } from '../../../../shared/components/diners-status/diners-status.component';
import { ChargeSessionService, ChargeSession } from '../../services/charge-session.service';

export interface BillLine {
  id?: string;
  name: string;
  price: number;
  diner?: number | null;
  variantName?: string | null;
  modifiers?: Array<{ name: string }> | null;
  /** Indica que esta línea ya fue cobrada (está en paid_order_line_ids). */
  isPaidLine?: boolean;
}

@Component({
  selector: 'app-split-bill-modal',
  templateUrl: './split-bill-modal.component.html',
  styleUrls: ['./split-bill-modal.component.scss'],
  imports: [FormsModule, CardComponent, BtnComponent, BadgeComponent, DinersStatusComponent],
  standalone: true,
})
export class SplitBillModalComponent implements OnChanges, OnInit, OnDestroy {
  @Input() isOpen = false;
  @Input() total = 0;
  @Input() tableLabel = '';
  @Input() lines: BillLine[] = [];
  @Input() diners = 2;
  @Input() paidDiners: number[] = [];
  @Input() orderId: string | null = null;
  @Input() userId: string | null = null;
  @Input() chargeSession: ChargeSession | null = null;
  @Input() remainingCents = 0;
  @Input() paidOrderLineIds: string[] = [];
  @Output() closeModal = new EventEmitter<void>();
  @Output() confirmSplit = new EventEmitter<{ selectedLines: BillLine[]; diner?: number; amount?: number; isEqualPart?: boolean; chargeSessionId?: string }>();
  @Output() sessionUpdated = new EventEmitter<any>();
  @Output() paymentRecorded = new EventEmitter<{ diner: number; amount: number }>();
  @Output() refundLine = new EventEmitter<{ orderLineId: string; lineName: string; price: number }>();

  public mode: 'equal' | 'lines' | 'diner' = 'equal';
  public parts = 2;
  public assignedLines: BillLine[] = [];
  public selectedLineId: string | null = null;
  private chargeSessionLoaded = false;
  public isLoading = false;
  public error: string | null = null;
  /** Cuando es true, el reparto equitativo divide entre TODOS los comensales (incluidos los ya pagados). */
  public includePaidInEqualSplit = false;

  /** Debounce de sincronización de asignaciones con el backend. */
  private readonly assignSync$ = new Subject<void>();
  private readonly destroy$ = new Subject<void>();

  // Paleta fija para distinguir comensales (rota por mod 8).
  private static readonly DINER_PALETTE: ReadonlyArray<string> = [
    '#e74c3c',
    '#3498db',
    '#27ae60',
    '#f39c12',
    '#9b59b6',
    '#1abc9c',
    '#e91e63',
    '#34495e',
  ];

  constructor(private chargeSessionService: ChargeSessionService) {}

  public ngOnInit(): void {
    this.rebuildAssignedLines();
    this.parts = this.diners;
    if (!this.chargeSession) {
      this.loadChargeSession();
    } else {
      this.chargeSessionLoaded = true;
    }

    this.assignSync$
      .pipe(debounceTime(350), takeUntil(this.destroy$))
      .subscribe(() => this.flushAssignments());
  }

  public ngOnChanges(changes: SimpleChanges): void {
    if (changes['lines'] || changes['diners'] || changes['paidDiners'] || changes['chargeSession']) {
      this.rebuildAssignedLines();
      this.parts = this.diners;
    }

    if (changes['chargeSession'] && this.chargeSession) {
      this.chargeSessionLoaded = true;
      // Si ya hay pagos y el método fue partes iguales, forzamos el modo equal
      // para evitar que el cajero intente cambiar a líneas/comensal.
      if (this.isEqualOnlyMode && this.mode !== 'equal') {
        this.mode = 'equal';
      }
    }

    if (changes['orderId'] && !changes['orderId'].firstChange) {
      this.chargeSession = null;
      this.loadChargeSession();
    }
  }

  public ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  /**
   * Reconstruye `assignedLines` cruzando lo que llega del padre con el estado
   * persistido en la charge session:
   *  - filtra las order_lines ya pagadas (en `paid_order_line_ids`),
   *  - hidrata `.diner` desde `line_assignments`,
   *  - mantiene el último orden de input.
   */
  private rebuildAssignedLines(): void {
    const paidIds = new Set<string>([
      ...(this.chargeSession?.paid_order_line_ids ?? []),
      ...this.paidOrderLineIds,
    ]);
    const assignmentByLine = new Map<string, number>();
    for (const a of this.chargeSession?.line_assignments ?? []) {
      assignmentByLine.set(a.order_line_id, a.diner_number);
    }

    console.log('rebuildAssignedLines - chargeSession:', this.chargeSession);
    console.log('rebuildAssignedLines - paidIds size:', paidIds.size, 'line count:', this.lines?.length);

    this.assignedLines = (this.lines ?? []).map((l) => ({
      ...l,
      diner: l.id && assignmentByLine.has(l.id)
        ? assignmentByLine.get(l.id) ?? null
        : (l.diner ?? null),
      isPaidLine: l.id ? paidIds.has(l.id) : false,
    }));

    console.log('rebuildAssignedLines - assignedLines sample:', this.assignedLines.slice(0, 2));

    // Si la línea seleccionada ya no existe (porque se ha pagado o cambió input),
    // limpiamos la selección para no quedar en un estado fantasma.
    if (this.selectedLineId && !this.assignedLines.some((l) => l.id === this.selectedLineId)) {
      this.selectedLineId = null;
    }
  }

  /** Vacía el debounce mandando el estado actual al backend (fire-and-forget). */
  private flushAssignments(): void {
    this.persistAssignmentsThen(null);
  }

  /**
   * Persiste las asignaciones actuales y, una vez confirmadas en backend,
   * ejecuta `then`. Si la sesión no es válida, llama a `then` directamente.
   * En caso de error sigue llamando a `then` para no bloquear al cajero.
   */
  private persistAssignmentsThen(then: (() => void) | null): void {
    if (!this.chargeSession || this.chargeSession.status !== 'active') {
      then?.();
      return;
    }

    const assignments = this.assignedLines
      .filter((l) => l.id != null && l.diner != null)
      .map((l) => ({ order_line_id: l.id as string, diner_number: l.diner as number }));

    this.chargeSessionService
      .updateLineAssignments(this.chargeSession.id, { assignments })
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (session) => {
          this.chargeSession = session;
          this.rebuildAssignedLines();
          this.sessionUpdated.emit(session);
          then?.();
        },
        error: (error) => {
          console.error('No se pudo persistir la asignación de líneas:', error);
          then?.();
        },
      });
  }

  /**
   * Verdadero si la sesión ya tiene pagos registrados.
   */
  public get hasRecordedPayments(): boolean {
    if (!this.chargeSession) return false;
    return this.chargeSession.paid_cents > 0 || this.chargeSession.paid_diner_numbers.length > 0;
  }

  /**
   * Verdadero si el método inicial fue "partes iguales" (no hay líneas pagadas).
   */
  public get isEqualOnlyMode(): boolean {
    if (!this.hasRecordedPayments) return false;
    return (this.chargeSession?.paid_order_line_ids ?? []).length === 0;
  }

  /**
   * Cambia de modo validando que no se salte de método una vez iniciados los pagos.
   */
  public onChangeMode(newMode: 'equal' | 'lines' | 'diner'): void {
    if (newMode === this.mode) return;

    if (this.isEqualOnlyMode) {
      // Si se empezó con partes iguales, no se permite cambiar a líneas/comensal.
      return;
    }

    this.mode = newMode;
  }

  public get remainingDiners(): number {
    if (this.chargeSession) {
      return this.chargeSession.diners_count - this.chargeSession.paid_diner_numbers.length;
    }
    return this.diners - this.paidDiners.length;
  }

  public get unpaidDinerNumbers(): number[] {
    if (this.chargeSession) {
      const allDiners = Array.from({ length: this.chargeSession.diners_count }, (_, i) => i + 1);
      const paidNumbers = this.chargeSession.paid_diner_numbers;
      return allDiners.filter((d) => !paidNumbers.includes(d));
    }
    const allDiners = Array.from({ length: this.diners }, (_, i) => i + 1);
    return allDiners.filter((d) => !this.paidDiners.includes(d));
  }

  public get activeDinersForEqualSplit(): number[] {
    if (this.includePaidInEqualSplit) {
      return this.allDinerNumbers;
    }
    return this.unpaidDinerNumbers;
  }

  public get equalPart(): number {
    const activeDiners = this.activeDinersForEqualSplit;
    if (this.chargeSession) {
      if (activeDiners.length > 0) {
        return Math.floor(this.chargeSession.remaining_cents / activeDiners.length);
      }
      return this.chargeSession.suggested_per_diner_cents;
    }
    if (activeDiners.length <= 0) return 0;
    return Math.floor(this.total / activeDiners.length);
  }

  public get remainder(): number {
    const activeDiners = this.activeDinersForEqualSplit;
    if (this.chargeSession) {
      return this.chargeSession.remaining_cents - (this.equalPart * activeDiners.length);
    }
    return this.total - this.equalPart * activeDiners.length;
  }

  public get remainingTotal(): number {
    if (this.chargeSession) {
      return this.chargeSession.remaining_cents;
    }
    return this.total;
  }

  public assignLine(id: string | undefined, diner: number): void {
    if (!id) return;
    this.assignedLines = this.assignedLines.map((l) =>
      l.id === id && !l.isPaidLine ? { ...l, diner: l.diner === diner ? null : diner } : l
    );
  }

  public getSubtotal(diner: number): number {
    return this.assignedLines
      .filter((l) => l.diner === diner && !l.isPaidLine)
      .reduce((sum, l) => sum + l.price, 0);
  }

  /** Importe ya cobrado de un comensal (líneas pagadas). */
  public getPaidSubtotal(diner: number): number {
    return this.assignedLines
      .filter((l) => l.diner === diner && l.isPaidLine)
      .reduce((sum, l) => sum + l.price, 0);
  }

  public getDinerLines(diner: number): BillLine[] {
    return this.assignedLines.filter((l) => l.diner === diner);
  }

  public getSubaccountLines(diner: number): BillLine[] {
    return this.assignedLines.filter((l) => l.diner === diner);
  }

  public getCommonLines(): BillLine[] {
    return this.assignedLines.filter((l) => !l.diner);
  }

  public formatLineModifiers(line: BillLine): string {
    return (line.modifiers ?? []).map((m) => '+ ' + m.name).join('  ');
  }

  // ── Tap & Place — modo `lines` ──────────────────────────────────────────────

  /** Líneas todavía no asignadas a ningún comensal (el pool). */
  public get unassignedLines(): BillLine[] {
    return this.assignedLines.filter((l) => l.diner == null);
  }

  /** Suma del pool de líneas sin asignar. */
  public get unassignedTotal(): number {
    return this.unassignedLines.reduce((sum, l) => sum + l.price, 0);
  }

  /** Importe ya cobrado en toda la sesión (líneas pagadas). */
  public get paidLinesTotal(): number {
    return this.assignedLines
      .filter((l) => l.isPaidLine)
      .reduce((sum, l) => sum + l.price, 0);
  }

  /** Suma de líneas ya asignadas a algún comensal (pagado o no). */
  public get assignedTotal(): number {
    return this.assignedLines
      .filter((l) => l.diner != null)
      .reduce((sum, l) => sum + l.price, 0);
  }

  /** Porcentaje del total ya asignado (0-100). */
  public get assignmentProgress(): number {
    const total = this.assignedTotal + this.unassignedTotal;
    if (total <= 0) return 0;
    return Math.round((this.assignedTotal / total) * 100);
  }

  /** Todos los comensales — incluye los ya pagados (se pintan en estado ✓). */
  public get allDinerNumbers(): number[] {
    const count = this.chargeSession?.diners_count ?? this.diners;
    return Array.from({ length: count }, (_, i) => i + 1);
  }

  /** Color hex para un comensal — fijo por número (mod paleta). */
  public dinerColor(n: number): string {
    const palette = SplitBillModalComponent.DINER_PALETTE;
    return palette[(n - 1) % palette.length];
  }

  /** Cuenta de líneas asignadas a un comensal. */
  public getDinerLineCount(diner: number): number {
    return this.assignedLines.filter((l) => l.diner === diner).length;
  }

  /** Toggle de selección del pool. Click sobre la misma línea la deselecciona. */
  public selectLine(id: string | undefined): void {
    if (!id) return;
    const line = this.assignedLines.find((l) => l.id === id);
    if (line?.isPaidLine) return;
    this.selectedLineId = this.selectedLineId === id ? null : id;
  }

  /**
   * Si hay línea seleccionada, la asigna a este comensal y limpia la selección.
   * Si no, no hace nada (la card del comensal no actúa como botón vacío).
   */
  public placeOnDiner(n: number): void {
    if (this.selectedLineId == null) return;
    const id = this.selectedLineId;
    const line = this.assignedLines.find((l) => l.id === id);
    if (line?.isPaidLine) return;
    this.assignedLines = this.assignedLines.map((l) =>
      l.id === id ? { ...l, diner: n } : l,
    );
    this.selectedLineId = null;
    this.assignSync$.next();
  }

  /** Devuelve una línea al pool (quita asignación). */
  public unassignLine(id: string | undefined): void {
    if (!id) return;
    const line = this.assignedLines.find((l) => l.id === id);
    if (line?.isPaidLine) return;
    this.assignedLines = this.assignedLines.map((l) =>
      l.id === id ? { ...l, diner: null } : l,
    );
    this.assignSync$.next();
  }

  /**
   * Pide confirmación al cajero y, si acepta, emite un evento de reembolso de
   * la línea. La operación real (cancelar la venta parcial en backend) la
   * ejecuta el padre, no este componente.
   */
  public requestRefund(line: BillLine, event?: Event): void {
    event?.stopPropagation();
    if (!line.id || !line.isPaidLine) return;

    const label = line.variantName ? `${line.name} (${line.variantName})` : line.name;
    const ok = confirm(`¿Reembolsar "${label}" por ${this.formatCents(line.price)} €?`);
    if (!ok) return;

    this.refundLine.emit({
      orderLineId: line.id,
      lineName: label,
      price: line.price,
    });
  }

  /** Limpia la selección actual sin asignar. */
  public clearSelection(): void {
    this.selectedLineId = null;
  }

  public decreaseParts(): void {
    this.parts = Math.max(2, this.parts - 1);
  }

  public increaseParts(): void {
    this.parts = Math.min(10, this.parts + 1);
  }

  public chargeDiner(diner: number): void {
    if (this.isLoading) return;

    const selectedLines = this.assignedLines.filter((l) => l.diner === diner && !l.isPaidLine);
    if (selectedLines.length === 0) return;

    // Antes de cerrar el modal, garantizamos que TODAS las asignaciones (incluso
    // las que estaban en debounce y aún no habían llegado al backend) quedan
    // persistidas. Si no, al cobrar a un comensal podríamos cerrar el modal con
    // asignaciones del resto sin guardar y perderlas.
    this.isLoading = true;
    this.persistAssignmentsThen(() => {
      this.isLoading = false;
      // Releemos las líneas del comensal a partir del estado más reciente por
      // si la persistencia ha refrescado `chargeSession` (paid_order_line_ids,
      // line_assignments…) — `assignedLines` se mantiene consistente porque
      // sólo lo reconstruimos en ngOnChanges.
      const finalLines = this.assignedLines.filter((l) => l.diner === diner && !l.isPaidLine);
      if (finalLines.length === 0) return;
      this.confirmSplit.emit({ selectedLines: finalLines, diner, chargeSessionId: this.chargeSession?.id });
      this.closeModal.emit();
    });
  }

  public get canUpdateDiners(): boolean {
    return this.chargeSession !== null;
  }

  public get minDinersCount(): number {
    if (!this.chargeSession) return 1;
    return Math.max(1, this.chargeSession.paid_diner_numbers.length);
  }

  public decreaseDinersCount(): void {
    if (!this.chargeSession || !this.canUpdateDiners) return;
    const newCount = this.chargeSession.diners_count - 1;
    if (newCount < this.minDinersCount) return;
    this.updateDinersCountOnBackend(newCount);
  }

  public increaseDinersCount(): void {
    if (!this.chargeSession || !this.canUpdateDiners) return;
    const newCount = this.chargeSession.diners_count + 1;
    if (newCount > 20) return;
    this.updateDinersCountOnBackend(newCount);
  }

  private updateDinersCountOnBackend(newCount: number): void {
    if (!this.chargeSession) return;
    this.isLoading = true;
    this.chargeSessionService.updateDiners(this.chargeSession.id, { diners_count: newCount }).subscribe({
      next: () => {
        this.loadChargeSession();
      },
      error: (error) => {
        console.error('Error updating diners count', error);
        this.error = 'No se pudo actualizar el número de comensales';
        this.isLoading = false;
      }
    });
  }

  public chargeEqualPart(dinerNum: number): void {
    if (this.isLoading) return;

    // Aunque el reparto equitativo no depende de `line_assignments`, el cajero
    // puede haber tocado el reparto por líneas en otra modalidad antes de
    // cambiar a "equal" y cobrar. Persistimos por seguridad antes de cerrar.
    this.isLoading = true;
    this.persistAssignmentsThen(() => {
      this.isLoading = false;
      const partAmount = this.getDinerAmount(dinerNum);
      this.confirmSplit.emit({
        selectedLines: this.paidDiners.length === 0 ? this.assignedLines : [],
        diner: dinerNum,
        amount: partAmount,
        isEqualPart: true,
        chargeSessionId: this.chargeSession?.id,
      });
      this.closeModal.emit();
    });
  }

  public onConfirm(): void {
    if (this.isLoading) return;

    this.isLoading = true;
    this.persistAssignmentsThen(() => {
      this.isLoading = false;
      this.confirmSplit.emit({ selectedLines: this.assignedLines, chargeSessionId: this.chargeSession?.id });
      this.closeModal.emit();
    });
  }

  public onClose(): void {
    const hasAssignedLines = this.mode === 'lines' && this.assignedLines.some((l) => l.diner != null);
    if (hasAssignedLines && !confirm('Hay líneas asignadas. ¿Cerrar sin cobrar?')) return;
    this.closeModal.emit();
  }

  public formatCents(cents: number): string {
    return (cents / 100).toFixed(2);
  }

  private formatCentsToEuros(cents: number): string {
    return (cents / 100).toFixed(2).replace('.', ',') + ' €';
  }

  /**
   * Cargar o crear la sesión de cobro desde el backend
   */
  public loadChargeSession(): void {
    if (!this.orderId || !this.userId) {
      console.log('No orderId or userId, skipping charge session load');
      return;
    }

    this.isLoading = true;
    this.error = null;

    this.chargeSessionService.getCurrentChargeSession(this.orderId).subscribe({
      next: (session) => {
        console.log('Charge session loaded:', session);
        this.chargeSession = session;
        this.rebuildAssignedLines();
        this.sessionUpdated.emit(session);
        this.syncPaidDinersFromSession();
        this.isLoading = false;
      },
      error: (error) => {
        if (error.status === 404) {
          this.createChargeSession();
        } else {
          console.error('Error loading charge session:', {
            status: error?.status,
            message: error?.message,
            error: error?.error,
            url: error?.url,
          });
          const detail = error?.error?.message || error?.message || `HTTP ${error?.status}`;
          this.error = `Error al cargar la sesión de cobro (${detail})`;
          this.isLoading = false;
        }
      },
    });
  }

  private createChargeSession(): void {
    if (!this.orderId || !this.userId) return;

    this.chargeSessionService.createChargeSession({
      order_id: this.orderId,
      opened_by_user_id: this.userId,
      diners_count: this.diners,
      remaining_cents: this.remainingCents > 0 ? this.remainingCents : undefined,
    }).subscribe({
      next: (session) => {
        console.log('Charge session created:', session);
        this.chargeSession = session;
        this.rebuildAssignedLines();
        this.sessionUpdated.emit(session);
        this.isLoading = false;
      },
      error: (error) => {
        console.error('Error creating charge session:', error);

        this.error = 'Error al crear la sesión de cobro';
        this.isLoading = false;
      },
    });
  }

  private syncPaidDinersFromSession(): void {
    if (!this.chargeSession) return;

    const paidDinerNumbers = this.chargeSession.paid_diner_numbers;
    console.log('Syncing paid diners from session:', paidDinerNumbers);
  }

  public isDinerPaid(dinerNum: number): boolean {
    if (!this.chargeSession) return this.paidDiners.includes(dinerNum);
    return this.chargeSession.paid_diner_numbers.includes(dinerNum);
  }

  public getDinerAmount(dinerNum: number): number {
    const activeDiners = this.activeDinersForEqualSplit;
    const index = activeDiners.indexOf(dinerNum);

    // Si el comensal no está en el grupo activo de reparto, le corresponde 0.
    if (index === -1) {
      return 0;
    }

    if (!this.chargeSession) {
      return this.equalPart + (index === activeDiners.length - 1 ? this.remainder : 0);
    }

    const isLast = activeDiners.length > 0 && activeDiners[activeDiners.length - 1] === dinerNum;

    if (isLast) {
      const othersAmount = this.equalPart * (activeDiners.length - 1);
      const lastAmount = this.chargeSession.remaining_cents - othersAmount;
      return lastAmount;
    }

    return this.equalPart;
  }

  public get isSessionCompleted(): boolean {
    if (!this.chargeSession) return false;
    // La sesión está completa SOLO cuando no queda nada por pagar.
    // `paid_diner_numbers.length === diners_count` no basta: puede haber
    // líneas sin asignar pendientes, o un comensal "pagado" cuya línea
    // se ha reembolsado dejando importe restante.
    return this.chargeSession.remaining_cents <= 0;
  }
}
