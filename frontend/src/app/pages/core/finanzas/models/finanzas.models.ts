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
  id: string | number;
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

// ── API response types (Fase 2+) ──────────────────────────────────────────

export interface KpiApiMetric {
  v: number;
  prev: number;
  delta_pct: number;
}

export interface SalePaymentRow {
  method: string;
  amount: number;
  tip?: number;
}

export interface SaleRow {
  uuid: string;
  ticket_number: number;
  value_date: string;
  total: number;
  status: string;
  zone_name: string;
  table_name: string;
  diners: number;
  opened_by: string;
  payment_methods: SalePaymentRow[];
  tips_total: number;
}

export interface PaginationMeta {
  total: number;
  page: number;
  per_page: number;
  last_page: number;
}

export interface SalesTotals {
  revenue: number;
  cash: number;
  card: number;
  bizum: number;
  other: number;
  tips: number;
}

export interface ProductReportItem {
  name: string;
  family: string;
  family_color: string;
  units: number;
  revenue: number;
  cost: number;
  price: number;
  pct: number;
  avg_daily: number;
  trend_spark: number[];
}

export interface EmployeeReportItem {
  user_uuid: string;
  name: string;
  role: string;
  initials: string;
  color: string;
  tickets: number;
  revenue: number;
  avg_ticket: number;
  items_sold: number;
  tips: number;
  discounts: number;
  cancellations: number;
  spark_revenue: number[];
}

export interface QuarterlyTax {
  quarter: string;
  period: string;
  elapsed_pct: number;
  rates: { rate: number; base: number; tax: number }[];
}

export interface DashboardSummaryResponse {
  period: FinanzasPeriod;
  date_label: string;
  kpis: {
    revenue:    KpiApiMetric;
    tickets:    KpiApiMetric;
    avg_ticket: KpiApiMetric;
    items_sold: KpiApiMetric;
    diners:     KpiApiMetric;
  };
  sparks: {
    revenue:    number[];
    tickets:    number[];
    avg_ticket: number[];
    items:      number[];
  };
  by_hour:      { l: string; v: number; n?: number }[];
  by_hour_prev: { l: string; v: number }[];
  by_day:       { l: string; v: number; n?: number }[];
  by_family:    { label: string; v: number }[];
  top_products: { name: string; family: string; units: number; revenue: number }[];
  by_payment_method: {
    cash:       number;
    card:       number;
    bizum:      number;
    voucher:    number;
    invitation: number;
    other:      number;
  };
}

export interface SalesReportResponse {
  data:   SaleRow[];
  meta:   PaginationMeta;
  totals: SalesTotals;
}

export interface SaleDetailResponse {
  uuid: string;
  ticket_number: number;
  value_date: string;
  status: string;
  zone_name: string;
  table_name: string;
  diners: number;
  opened_by: string;
  duration_minutes: number;
  lines: {
    product_name: string;
    family_name: string;
    qty: number;
    unit_price: number;
    tax_pct: number;
    total: number;
  }[];
  payments: { method: string; amount: number; tip?: number }[];
  tax_breakdown: { rate: number; base: number; tax: number }[];
  subtotal: number;
  tax_total: number;
  tips_total: number;
  cancel_reason: string | null;
}

export interface StockAlertItem {
  name: string;
  family: string;
  stock: number;
}

export interface ZoneReportItem {
  name: string;
  revenue: number;
  tickets: number;
}

export interface ProductsReportResponse {
  period_revenue: number;
  items: ProductReportItem[];
  stock_critical: StockAlertItem[];
  no_sales_7d: StockAlertItem[];
  alert_count: number;
  by_zone: ZoneReportItem[];
}

export interface EmployeesReportResponse {
  items: EmployeeReportItem[];
}

export interface RestaurantInfo {
  name:       string;
  legal_name: string;
  tax_id:     string;
}

export type Quarter = 'T1' | 'T2' | 'T3' | 'T4';

export interface TaxReportResponse {
  period_label: string;
  breakdown:    TaxSlab[];
  tips_card:    number;
  quarterly:    QuarterlyTax;
  restaurant:   RestaurantInfo;
}
