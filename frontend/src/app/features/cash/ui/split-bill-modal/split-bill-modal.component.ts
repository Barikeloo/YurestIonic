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
  /**
   * Comensales que ya pagaron su parte en la ronda equal actual de esta orden.
   * Cuando el toggle "Incluir comensales ya pagados" está ON, el divisor del
   * reparto es `allDiners − equalRoundPaidDiners` (no se les vuelve a cobrar).
   * Lo persiste el padre porque el modal se destruye en cada cobro.
   */
  @Input() equalRoundPaidDiners: number[] = [];
  /**
   * Cuota fija de la ronda equal, propiedad del padre. El modal la usa como
   * seed al abrirse y notifica los cambios con `equalRoundFixedPartChanged`.
   * Persistirla fuera del modal es lo que permite que la cuota sea estable
   * entre cobros (el modal se destruye en cada cobro).
   */
  @Input() equalRoundFixedPartCents: number | null = null;
  @Output() closeModal = new EventEmitter<void>();
  @Output() confirmSplit = new EventEmitter<{ selectedLines: BillLine[]; diner?: number; amount?: number; isEqualPart?: boolean; chargeSessionId?: string }>();
  @Output() sessionUpdated = new EventEmitter<any>();
  @Output() paymentRecorded = new EventEmitter<{ diner: number; amount: number }>();
  @Output() refundLine = new EventEmitter<{ orderLineId: string; lineName: string; price: number }>();
  @Output() equalRoundFixedPartChanged = new EventEmitter<number | null>();

  public mode: 'equal' | 'lines' | 'diner' = 'equal';
  public parts = 2;
  public assignedLines: BillLine[] = [];
  public selectedLineId: string | null = null;
  /** Comensal seleccionado como destino de las próximas asignaciones en modo `diner`. */
  public activeDinerNumber: number | null = null;
  private chargeSessionLoaded = false;
  public isLoading = false;
  public error: string | null = null;
  /**
   * Cuando es true, el grid del reparto muestra TODOS los comensales (con los
   * ya pagados en estado read-only). El cálculo de la cuota NO depende de este
   * flag: siempre se reparte entre los pendientes.
   */
  public includePaidInEqualSplit = false;
  /**
   * Cuota fija del reparto equal, calculada UNA VEZ cuando se inicia el modo
   * sobre `splittingDiners` (los pendientes). Se mantiene tras cada pago equal:
   * si son 4 y la cuota fija es 19,14, cada uno paga 19,14 y el último cubre el
   * resto por redondeo. Se invalida al salir del modo, al cambiar `diners_count`
   * o al reembolsar una línea ya cobrada (remaining_cents sube).
   */
  private fixedEqualPartCents: number | null = null;

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
    // Seed: si el padre tiene una cuota fija activa de la ronda, la adoptamos.
    if (this.equalRoundFixedPartCents !== null) {
      this.fixedEqualPartCents = this.equalRoundFixedPartCents;
    }
    if (!this.chargeSession) {
      this.loadChargeSession();
    } else {
      this.chargeSessionLoaded = true;
      this.ensureFixedEqualPart();
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

    if (changes['chargeSession']) {
      const prev = changes['chargeSession'].previousValue as ChargeSession | null | undefined;
      const curr = changes['chargeSession'].currentValue as ChargeSession | null | undefined;
      if (prev && curr) {
        // Reembolso de línea ya cobrada o cambio de comensales: la cuota fija
        // anterior ya no representa el reparto real.
        const remainingIncreased = curr.remaining_cents > prev.remaining_cents;
        const dinersCountChanged = curr.diners_count !== prev.diners_count;
        if (remainingIncreased || dinersCountChanged) {
          this.resetFixedEqualPart();
        }
      }
      if (curr && this.activeDinerNumber !== null) {
        const outOfRange = this.activeDinerNumber > curr.diners_count;
        const justPaid = curr.paid_diner_numbers.includes(this.activeDinerNumber);
        if (outOfRange || justPaid) {
          this.activeDinerNumber = null;
        }
      }
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
      this.resetFixedEqualPart();
      this.chargeSession = null;
      this.loadChargeSession();
    }

    if (changes['chargeSession'] && this.chargeSession && this.mode === 'equal') {
      this.ensureFixedEqualPart();
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

    this.assignedLines = (this.lines ?? []).map((l) => ({
      ...l,
      diner: l.id && assignmentByLine.has(l.id)
        ? assignmentByLine.get(l.id) ?? null
        : (l.diner ?? null),
      isPaidLine: l.id ? paidIds.has(l.id) : false,
    }));

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

    if (newMode === 'equal') {
      this.ensureFixedEqualPart();
    }
    if (newMode !== 'diner') {
      this.activeDinerNumber = null;
    }
    this.mode = newMode;
  }

  /**
   * Activa un comensal como destino de las próximas asignaciones en modo `diner`.
   * Tocar el ya activo lo desactiva. Comensales con su cobro cerrado se ignoran.
   */
  public selectActiveDiner(n: number): void {
    if (this.isDinerPaid(n) && this.getSubtotal(n) === 0) return;
    this.activeDinerNumber = this.activeDinerNumber === n ? null : n;
  }

  /**
   * Toggle de asignación de una línea contra el comensal activo:
   *  - si la línea ya pertenecía al activo, vuelve al pool;
   *  - si pertenecía a otro o estaba libre, se reasigna al activo.
   * Sin comensal activo no hace nada; líneas ya pagadas tampoco se tocan.
   */
  public toggleLineForActiveDiner(line: BillLine): void {
    if (this.activeDinerNumber === null) return;
    if (line.isPaidLine || !line.id) return;

    const id = line.id;
    const active = this.activeDinerNumber;
    this.assignedLines = this.assignedLines.map((l) =>
      l.id === id ? { ...l, diner: l.diner === active ? null : active } : l,
    );
    this.assignSync$.next();
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

  /**
   * Comensales que reparten la cuota equal en la ronda actual.
   *  - Toggle OFF: solo los pendientes (`unpaidDinerNumbers`).
   *  - Toggle ON: todos los comensales menos los que ya pagaron en esta ronda
   *    equal. Esto permite repartir el remanente cuando todos pagaron líneas
   *    pero queda parte compartida sin cobrar.
   */
  public get splittingDiners(): number[] {
    if (this.includePaidInEqualSplit) {
      return this.allDinerNumbers.filter((d) => !this.equalRoundPaidDiners.includes(d));
    }
    return this.unpaidDinerNumbers;
  }

  /**
   * Lista que se renderiza en el grid de "Partes iguales".
   *  - Toggle OFF: solo los pendientes.
   *  - Toggle ON: todos (los ya cobrados en la ronda salen como read-only).
   */
  public get displayedEqualDiners(): number[] {
    return this.includePaidInEqualSplit ? this.allDinerNumbers : this.unpaidDinerNumbers;
  }

  /** Verdadero si el comensal participa activamente en el reparto equal. */
  public isInEqualSplit(dinerNum: number): boolean {
    return this.splittingDiners.includes(dinerNum);
  }

  /**
   * Se llama cuando el cajero alterna "Incluir comensales ya pagados".
   * Cambia el divisor del reparto → invalidamos la cuota fija para que
   * `ensureFixedEqualPart` la recalcule con el nuevo conjunto.
   */
  public onToggleIncludePaid(): void {
    this.resetFixedEqualPart();
    if (this.mode === 'equal') {
      this.ensureFixedEqualPart();
    }
  }

  public get equalPart(): number {
    if (this.fixedEqualPartCents !== null) {
      return this.fixedEqualPartCents;
    }
    const splitting = this.splittingDiners;
    if (this.chargeSession) {
      if (splitting.length > 0) {
        return Math.floor(this.chargeSession.remaining_cents / splitting.length);
      }
      return this.chargeSession.suggested_per_diner_cents;
    }
    if (splitting.length <= 0) return 0;
    return Math.floor(this.total / splitting.length);
  }

  public get remainder(): number {
    const splitting = this.splittingDiners;
    if (this.chargeSession) {
      return this.chargeSession.remaining_cents - (this.equalPart * splitting.length);
    }
    return this.total - this.equalPart * splitting.length;
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
    // Cambiar el número de comensales invalida la cuota fija: el divisor del
    // reparto ya no es el mismo.
    this.resetFixedEqualPart();
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
      return;
    }

    this.isLoading = true;
    this.error = null;

    this.chargeSessionService.getCurrentChargeSession(this.orderId).subscribe({
      next: (session) => {
        this.chargeSession = session;
        this.rebuildAssignedLines();
        this.sessionUpdated.emit(session);
        this.isLoading = false;
        // Sin ngOnChanges en esta vía: fijamos la cuota explícitamente.
        this.ensureFixedEqualPart();
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
        this.chargeSession = session;
        this.rebuildAssignedLines();
        this.sessionUpdated.emit(session);
        this.isLoading = false;
        this.ensureFixedEqualPart();
      },
      error: (error) => {
        console.error('Error creating charge session:', error);
        this.error = 'Error al crear la sesión de cobro';
        this.isLoading = false;
      },
    });
  }

  public isDinerPaid(dinerNum: number): boolean {
    if (!this.chargeSession) return this.paidDiners.includes(dinerNum);
    return this.chargeSession.paid_diner_numbers.includes(dinerNum);
  }

  public getDinerAmount(dinerNum: number): number {
    const splitting = this.splittingDiners;
    const index = splitting.indexOf(dinerNum);

    // Comensales ya pagados (o fuera del reparto) no deben nada.
    if (index === -1) {
      return 0;
    }

    if (!this.chargeSession) {
      // Sin sesión todavía: el último de la lista absorbe el residuo
      // (camino casi muerto: el modal apenas renderiza sin chargeSession).
      return this.equalPart + (index === splitting.length - 1 ? this.remainder : 0);
    }

    // El residuo de redondeo lo absorbe quien quede solo al final, no
    // quien esté en la última posición del array. Así, mientras haya varios
    // comensales en la ronda todos ven la misma cuota fija; cuando solo
    // queda uno, ese paga el restante exacto (la cuota más los céntimos
    // pendientes).
    if (splitting.length === 1) {
      return this.chargeSession.remaining_cents;
    }

    return this.equalPart;
  }

  /**
   * Calcula la cuota fija del reparto equal UNA VEZ sobre los comensales
   * pendientes (`splittingDiners`). Se mantiene hasta que se invalide
   * explícitamente (cambio de modo, cambio de comensales, refund de línea).
   */
  private ensureFixedEqualPart(): void {
    if (this.fixedEqualPartCents !== null || !this.chargeSession || this.mode !== 'equal') {
      return;
    }
    const splitting = this.splittingDiners;
    if (splitting.length <= 0) return;
    this.fixedEqualPartCents = Math.floor(this.chargeSession.remaining_cents / splitting.length);
    this.equalRoundFixedPartChanged.emit(this.fixedEqualPartCents);
  }

  private resetFixedEqualPart(): void {
    const wasSet = this.fixedEqualPartCents !== null;
    this.fixedEqualPartCents = null;
    if (wasSet) {
      this.equalRoundFixedPartChanged.emit(null);
    }
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
