import { Component, EventEmitter, Input, OnChanges, Output, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { BadgeComponent } from '../badge/badge.component';
import { BtnComponent } from '../btn/btn.component';
import { CardComponent } from '../card/card.component';

export interface BillLine {
  id?: string;
  name: string;
  price: number;
  diner?: number | null;
}

@Component({
  selector: 'app-split-bill-modal',
  templateUrl: './split-bill-modal.component.html',
  styleUrls: ['./split-bill-modal.component.scss'],
  imports: [CommonModule, FormsModule, CardComponent, BtnComponent, BadgeComponent],
  standalone: true,
})
export class SplitBillModalComponent implements OnChanges {
  @Input() isOpen = false;
  @Input() total = 0;
  @Input() tableLabel = '';
  @Input() lines: BillLine[] = [];
  @Input() diners = 2;
  @Input() paidDiners: number[] = [];
  @Output() closeModal = new EventEmitter<void>();
  @Output() confirmSplit = new EventEmitter<{ selectedLines: BillLine[]; diner?: number; amount?: number; isEqualPart?: boolean }>();

  public mode: 'equal' | 'lines' | 'diner' = 'equal';
  public parts = 2;
  public assignedLines: BillLine[] = [];

  public ngOnInit(): void {
    this.assignedLines = [...this.lines];
    this.parts = this.diners;
    console.log('SplitBillModal ngOnInit - diners:', this.diners, 'paidDiners:', this.paidDiners);
  }

  public ngOnChanges(changes: SimpleChanges): void {
    if (changes['lines'] || changes['diners'] || changes['paidDiners']) {
      this.assignedLines = [...this.lines];
      this.parts = this.diners;
      console.log('SplitBillModal ngOnChanges - diners:', this.diners, 'paidDiners:', this.paidDiners);
    }
  }

  public get remainingDiners(): number {
    return this.diners - this.paidDiners.length;
  }

  public get unpaidDinerNumbers(): number[] {
    const allDiners = Array.from({ length: this.diners }, (_, i) => i + 1);
    return allDiners.filter((d) => !this.paidDiners.includes(d));
  }

  public get equalPart(): number {
    return Math.floor(this.total / this.remainingDiners);
  }

  public get remainder(): number {
    return this.total - this.equalPart * this.remainingDiners;
  }

  public assignLine(id: string | undefined, diner: number): void {
    if (!id) return;
    this.assignedLines = this.assignedLines.map((l) =>
      l.id === id ? { ...l, diner: l.diner === diner ? null : diner } : l
    );
  }

  public getSubtotal(diner: number): number {
    return this.assignedLines.filter((l) => l.diner === diner).reduce((sum, l) => sum + l.price, 0);
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

  public decreaseParts(): void {
    this.parts = Math.max(2, this.parts - 1);
  }

  public increaseParts(): void {
    this.parts = Math.min(10, this.parts + 1);
  }

  public chargeDiner(diner: number): void {
    const selectedLines = this.assignedLines.filter((l) => l.diner === diner);
    if (selectedLines.length > 0) {
      this.confirmSplit.emit({ selectedLines, diner });
      this.closeModal.emit();
    }
  }

  public chargeEqualPart(dinerNum: number): void {
    const unpaidNumbers = this.unpaidDinerNumbers;
    const index = unpaidNumbers.indexOf(dinerNum);
    const partAmount = this.equalPart + (index === unpaidNumbers.length - 1 ? this.remainder : 0);
    // For equal parts, emit with all lines for the first part, empty for subsequent parts
    // This allows the backend to create partial sales
    this.confirmSplit.emit({
      selectedLines: this.paidDiners.length === 0 ? this.assignedLines : [],
      diner: dinerNum,
      amount: partAmount,
      isEqualPart: true,
    });
    this.closeModal.emit();
  }

  public onConfirm(): void {
    this.confirmSplit.emit({ selectedLines: this.assignedLines });
    this.closeModal.emit();
  }

  public formatCents(cents: number): string {
    return (cents / 100).toFixed(2);
  }
}
