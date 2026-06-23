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


  @Input() equalRoundPaidDiners: number[] = [];


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


  public activeDinerNumber: number | null = null;
  private chargeSessionLoaded = false;
  public isLoading = false;
  public error: string | null = null;


  public includePaidInEqualSplit = false;


  private fixedEqualPartCents: number | null = null;



  private readonly assignSync$ = new Subject<void>();
  private readonly destroy$ = new Subject<void>();


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


    if (this.selectedLineId && !this.assignedLines.some((l) => l.id === this.selectedLineId)) {
      this.selectedLineId = null;
    }
  }



  private flushAssignments(): void {
    this.persistAssignmentsThen(null);
  }



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



  public get hasRecordedPayments(): boolean {
    if (!this.chargeSession) return false;
    return this.chargeSession.paid_cents > 0 || this.chargeSession.paid_diner_numbers.length > 0;
  }



  public get isEqualOnlyMode(): boolean {
    if (!this.hasRecordedPayments) return false;
    return (this.chargeSession?.paid_order_line_ids ?? []).length === 0;
  }



  public onChangeMode(newMode: 'equal' | 'lines' | 'diner'): void {
    if (newMode === this.mode) return;

    if (this.isEqualOnlyMode) {

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



  public selectActiveDiner(n: number): void {
    if (this.isDinerPaid(n) && this.getSubtotal(n) === 0) return;
    this.activeDinerNumber = this.activeDinerNumber === n ? null : n;
  }



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



  public get splittingDiners(): number[] {
    if (this.includePaidInEqualSplit) {
      return this.allDinerNumbers.filter((d) => !this.equalRoundPaidDiners.includes(d));
    }
    return this.unpaidDinerNumbers;
  }



  public get displayedEqualDiners(): number[] {
    return this.includePaidInEqualSplit ? this.allDinerNumbers : this.unpaidDinerNumbers;
  }



  public isInEqualSplit(dinerNum: number): boolean {
    return this.splittingDiners.includes(dinerNum);
  }



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





  public get unassignedLines(): BillLine[] {
    return this.assignedLines.filter((l) => l.diner == null);
  }



  public get unassignedTotal(): number {
    return this.unassignedLines.reduce((sum, l) => sum + l.price, 0);
  }



  public get paidLinesTotal(): number {
    return this.assignedLines
      .filter((l) => l.isPaidLine)
      .reduce((sum, l) => sum + l.price, 0);
  }



  public get assignedTotal(): number {
    return this.assignedLines
      .filter((l) => l.diner != null)
      .reduce((sum, l) => sum + l.price, 0);
  }



  public get assignmentProgress(): number {
    const total = this.assignedTotal + this.unassignedTotal;
    if (total <= 0) return 0;
    return Math.round((this.assignedTotal / total) * 100);
  }

  public get allDinerNumbers(): number[] {
    const count = this.chargeSession?.diners_count ?? this.diners;
    return Array.from({ length: count }, (_, i) => i + 1);
  }



  public dinerColor(n: number): string {
    const palette = SplitBillModalComponent.DINER_PALETTE;
    return palette[(n - 1) % palette.length];
  }



  public getDinerLineCount(diner: number): number {
    return this.assignedLines.filter((l) => l.diner === diner).length;
  }



  public selectLine(id: string | undefined): void {
    if (!id) return;
    const line = this.assignedLines.find((l) => l.id === id);
    if (line?.isPaidLine) return;
    this.selectedLineId = this.selectedLineId === id ? null : id;
  }



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



  public unassignLine(id: string | undefined): void {
    if (!id) return;
    const line = this.assignedLines.find((l) => l.id === id);
    if (line?.isPaidLine) return;
    this.assignedLines = this.assignedLines.map((l) =>
      l.id === id ? { ...l, diner: null } : l,
    );
    this.assignSync$.next();
  }



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


    this.isLoading = true;
    this.persistAssignmentsThen(() => {
      this.isLoading = false;

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


    if (index === -1) {
      return 0;
    }

    if (!this.chargeSession) {

      return this.equalPart + (index === splitting.length - 1 ? this.remainder : 0);
    }


    if (splitting.length === 1) {
      return this.chargeSession.remaining_cents;
    }

    return this.equalPart;
  }



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

    return this.chargeSession.remaining_cents <= 0;
  }
}
