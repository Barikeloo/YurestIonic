export interface KpiMetric {
  v: number;
  prev: number;
  deltaPct: number;
}

export interface HourData {
  l: string;
  v: number;
  n?: number;
  forecast?: boolean;
}

export interface DayData {
  l: string;
  v: number;
  n?: number;
}

export interface HeatmapCell {
  h: string;
  v: number;
  n: number;
}

export interface HeatmapRow {
  day: string;
  hours: HeatmapCell[];
}

export interface FamilyData {
  label: string;
  v: number;
  color: string;
}

export interface TopProduct {
  name: string;
  family: string;
  units: number;
  revenue: number;
  cost: number;
  avgDaily: number;
  stock: number;
  color: string;
}

export interface FinanzasAlert {
  id: number;
  type: 'warning' | 'critical' | 'info';
  title: string;
  sub: string;
  time: string;
  tab: FinanzasTab;
}

export interface TicketLine {
  name: string;
  qty: number;
  unitPrice: number;
  tax: number;
  total: number;
}

export interface Payment {
  method: string;
  amount: number;
  tip: number;
}

export interface TaxBreakdownLine {
  rate: number;
  base: number;
  tax: number;
}

export interface TicketDetail {
  id: string;
  zone: string;
  diners: number;
  opened: string;
  closed: string;
  duration: string;
  waiter: string;
  status: string;
  lines: TicketLine[];
  payments: Payment[];
  taxBreakdown: TaxBreakdownLine[];
  note?: string;
  subtotal: number;
  taxTotal: number;
  tipsTotal: number;
}

export interface Order {
  id: string;
  zone: string;
  status: 'paid' | 'cancelled' | 'open';
  total: number;
  tip: number;
  method: string;
  time: string;
  diners: number;
  lines?: TicketLine[];
}

export interface Employee {
  id: string;
  name: string;
  role: string;
  initials: string;
  color: string;
  shift: string;
  tickets: number;
  revenue: number;
  avgTicket: number;
  items: number;
  tips: number;
  discounts: number;
  cancellations: number;
  comp: number;
  active: boolean;
  sparkRevenue: number[];
}

export interface OpenTable {
  id: string;
  zone: string;
  diners: number;
  opened: string;
  minutesOpen: number;
  current: number;
  waiter: string;
  state: string;
  lastEvent: string;
  alert?: string;
}

export interface PendingPayment {
  id: string;
  zone: string;
  total: number;
  since: string;
  issue: string;
}

export interface Cancellation {
  id: string;
  date: string;
  zone: string;
  amount: number;
  reason: string;
  who: string;
  authorizedBy: string;
  category: string;
}

export interface PreCloseItem {
  id: number;
  label: string;
  done: boolean;
  blocking: boolean;
}

export interface CrossSellPair {
  a: string;
  b: string;
  together: number;
  total: number;
  highlight?: string;
}

export interface ProductTrend {
  trend: 'up' | 'down' | 'flat';
  delta: number;
  spark: number[];
}

export interface CashMovementItem {
  id: string;
  type: 'in' | 'out';
  reason: string;
  amount: number;
  time: string;
  user: string;
}

export interface CashSessionHistory {
  id: string;
  opened: string;
  closed: string;
  operator: string;
  sales: number;
  theoretical: number;
  counted: number;
  diff: number;
  tickets: number;
}

export interface QuarterVat {
  period: string;
  elapsed: number;
  base4: number;
  tax4: number;
  base10: number;
  tax10: number;
  base21: number;
  tax21: number;
}

export interface Location {
  id: string;
  name: string;
  city: string;
  revenue: number;
  tickets: number;
  isCurrent?: boolean;
}

export interface Forecast {
  currentTime: string;
  closed: number;
  projection: number;
  rangeMin: number;
  rangeMax: number;
  confidence: number;
}

export interface HardwareItem {
  id: string;
  name: string;
  status: 'ok' | 'warning' | 'error';
  icon: string;
  detail: string;
  critical?: boolean;
}

export interface ProductRanking {
  name: string;
  family: string;
  units: number;
  revenue: number;
  cost: number;
  price: number;
  avgDaily: number;
  stock: number;
  pct: number;
}

export interface ZoneData {
  id: string;
  name: string;
  x: number;
  y: number;
  w: number;
  h: number;
  revenue: number;
  tickets: number;
  occupancy: number;
}

export interface CannibalPair {
  a: string;
  b: string;
  overlap: number;
  note: string;
}

export interface TaxSlab {
  rate: number;
  label: string;
  note: string;
  base: number;
  tax: number;
  total: number;
}

export type FinanzasPeriod = 'today' | 'yesterday' | 'week' | 'month';
export type FinanzasTab = 'resumen' | 'ventas' | 'productos' | 'empleados' | 'caja' | 'impuestos' | 'informes';
export type ResumenVariant = 'A' | 'B' | 'C';
