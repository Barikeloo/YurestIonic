import {
  CannibalPair,
  CashMovementItem,
  CashSessionHistory,
  Cancellation,
  CrossSellPair,
  Employee,
  FamilyData,
  FinanzasAlert,
  Forecast,
  HardwareItem,
  HeatmapRow,
  HourData,
  Location,
  OpenTable,
  Order,
  PendingPayment,
  PreCloseItem,
  ProductRanking,
  ProductTrend,
  QuarterVat,
  TaxSlab,
  TicketDetail,
  TopProduct,
  ZoneData,
} from '../models/finanzas.models';

export const MOCK_META = {
  restaurant: 'La Tasca de Miguel',
  device: 'TPV Sala',
  operator: 'María G.',
  date: 'Jueves, 14 mayo 2026',
};

export const MOCK_SUMMARY = {
  revenue:   { v: 247385, prev: 218940, deltaPct: 13.0 },
  tickets:   { v: 84,     prev: 76,     deltaPct: 10.5 },
  avgTicket: { v: 2945,   prev: 2880,   deltaPct: 2.3  },
  itemsSold: { v: 312,    prev: 289,    deltaPct: 8.0  },
  diners:    { v: 196,    prev: 174,    deltaPct: 12.6 },
  tipsCard:  { v: 2840,   prev: 2120,   deltaPct: 34.0 },
  cashOpen:  { v: 318240, status: 'open' },
};

export const MOCK_SPARKS = {
  revenue:   [1840, 2010, 2190, 1780, 2350, 2470, 2820, 2030, 1950, 2110, 2280, 2410, 2189, 2473],
  tickets:   [62,   68,   72,   60,   78,   82,   96,   70,   65,   71,   75,   79,   76,   84  ],
  avgTicket: [2967, 2956, 3043, 2967, 3013, 3012, 2937, 2900, 3000, 2972, 3040, 3051, 2880, 2945],
  items:     [228,  245,  267,  220,  286,  305,  358,  256,  240,  268,  280,  295,  289,  312 ],
};

export const MOCK_BY_HOUR: HourData[] = [
  { l: '08', v:  4280, n: 8  },
  { l: '09', v:  8950, n: 14 },
  { l: '10', v:  6420, n: 9  },
  { l: '11', v:  5180, n: 7  },
  { l: '12', v: 11240, n: 6  },
  { l: '13', v: 28760, n: 11 },
  { l: '14', v: 39420, n: 14 },
  { l: '15', v: 22480, n: 9  },
  { l: '16', v:  6240, n: 3  },
  { l: '17', v:  3180, n: 2  },
  { l: '18', v:  7820, n: 4  },
  { l: '19', v: 12640, n: 6  },
  { l: '20', v: 28940, n: 10 },
  { l: '21', v: 35610, n: 12 },
  { l: '22', v: 26225, n: 8  },
  { l: '23', v:     0, n: 0  },
];

export const MOCK_BY_HOUR_PREV: HourData[] = [
  { l: '08', v:  3920 }, { l: '09', v:  7480 }, { l: '10', v:  5860 },
  { l: '11', v:  4720 }, { l: '12', v:  9840 }, { l: '13', v: 25180 },
  { l: '14', v: 36240 }, { l: '15', v: 19420 }, { l: '16', v:  5080 },
  { l: '17', v:  2980 }, { l: '18', v:  6820 }, { l: '19', v: 10780 },
  { l: '20', v: 24680 }, { l: '21', v: 31420 }, { l: '22', v: 24560 },
  { l: '23', v:     0 },
];

export const MOCK_BY_HOUR_LAST_WEEK: HourData[] = [
  { l: '08', v:  4180 }, { l: '09', v:  9120 }, { l: '10', v:  6580 },
  { l: '11', v:  5440 }, { l: '12', v: 11820 }, { l: '13', v: 29240 },
  { l: '14', v: 41280 }, { l: '15', v: 23560 }, { l: '16', v:  6680 },
  { l: '17', v:  3320 }, { l: '18', v:  8240 }, { l: '19', v: 13180 },
  { l: '20', v: 30220 }, { l: '21', v: 37840 }, { l: '22', v: 28140 },
  { l: '23', v:     0 },
];

export const MOCK_BY_DAY = [
  { l: '1 may', v: 184000, n: 62 }, { l: '2 may', v: 201000, n: 68 },
  { l: '3 may', v: 219000, n: 72 }, { l: '4 may', v: 178000, n: 60 },
  { l: '5 may', v: 235000, n: 78 }, { l: '6 may', v: 247000, n: 82 },
  { l: '7 may', v: 282000, n: 96 }, { l: '8 may', v: 203000, n: 70 },
  { l: '9 may', v: 195000, n: 65 }, { l: '10 may', v: 211000, n: 71 },
  { l: '11 may', v: 228000, n: 75 }, { l: '12 may', v: 241000, n: 79 },
  { l: '13 may', v: 218940, n: 76 }, { l: '14 may', v: 247385, n: 84 },
];

function makeHeatmap(): HeatmapRow[] {
  const days = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
  const hours = ['08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23'];
  const intensity: Record<string, number[]> = {
    'Lun': [0.1,0.3,0.2,0.2,0.4, 0.7, 0.95,0.55,0.15,0.1, 0.2, 0.35,0.55,0.6, 0.4, 0.0],
    'Mar': [0.1,0.3,0.2,0.2,0.45,0.75,0.95,0.55,0.15,0.1, 0.2, 0.4, 0.6, 0.65,0.45,0.0],
    'Mié': [0.15,0.3,0.2,0.2,0.45,0.78,0.92,0.55,0.18,0.1,0.25,0.4, 0.65,0.7, 0.5, 0.0],
    'Jue': [0.15,0.32,0.22,0.2,0.5,0.85,1.0,0.6, 0.2, 0.1, 0.28,0.45,0.75,0.88,0.65,0.0],
    'Vie': [0.2, 0.35,0.25,0.25,0.55,0.95,1.0,0.65,0.22,0.15,0.3, 0.5, 0.9, 0.98,0.85,0.1],
    'Sáb': [0.05,0.15,0.3,0.4,0.7,1.0, 0.95,0.65,0.4, 0.3, 0.4, 0.55,0.95,1.0, 0.9, 0.15],
    'Dom': [0.0, 0.1, 0.4,0.55,0.85,1.0,0.85,0.5, 0.25,0.05,0.1, 0.15,0.3, 0.35,0.15,0.0],
  };
  return days.map(d => ({
    day: d,
    hours: hours.map((h, i) => ({
      h,
      v: Math.round(intensity[d][i] * 38000),
      n: Math.round(intensity[d][i] * 14),
    })),
  }));
}
export const MOCK_HEATMAP: HeatmapRow[] = makeHeatmap();

export const MOCK_TOP_PRODUCTS: TopProduct[] = [
  { name: 'Cerveza caña',         family: 'Bebidas',  units: 124, revenue: 24800, cost:  400, avgDaily: 110, stock: 500, color: '#1a9e5a' },
  { name: 'Croquetas de jamón',   family: 'Tapas',    units:  38, revenue: 22800, cost: 2500, avgDaily:  34, stock:  80, color: '#ff4d4d' },
  { name: 'Vino tinto (copa)',    family: 'Bebidas',  units:  67, revenue: 23450, cost: 1200, avgDaily:  60, stock: 200, color: '#7857d6' },
  { name: 'Tortilla española',    family: 'Tapas',    units:  22, revenue: 19800, cost: 3500, avgDaily:  20, stock:  30, color: '#0077cc' },
  { name: 'Patatas bravas',       family: 'Tapas',    units:  31, revenue: 18600, cost: 1800, avgDaily:  28, stock:  60, color: '#d18a1c' },
  { name: 'Pulpo a la gallega',   family: 'Raciones', units:  14, revenue: 21000, cost: 8000, avgDaily:  12, stock:   6, color: '#ff4d4d' },
  { name: 'Café solo',            family: 'Bebidas',  units:  88, revenue:  9680, cost:  200, avgDaily:  85, stock: 999, color: '#1a9e5a' },
  { name: 'Tarta de queso',       family: 'Postres',  units:  19, revenue: 11400, cost: 2800, avgDaily:  22, stock:  12, color: '#d18a1c' },
  { name: 'Chuletón 400g',        family: 'Raciones', units:   9, revenue: 16200, cost:12000, avgDaily:  10, stock:   8, color: '#ff4d4d' },
  { name: 'Calamares fritos',     family: 'Tapas',    units:   7, revenue:  9100, cost: 3500, avgDaily:   6, stock:  15, color: '#d18a1c' },
  { name: 'Coca-Cola',            family: 'Bebidas',  units:  42, revenue:  5040, cost:  300, avgDaily:  45, stock: 150, color: '#1a9e5a' },
  { name: 'Pan rústico',          family: 'Tapas',    units:  78, revenue:  7020, cost:  400, avgDaily:  80, stock: 200, color: '#0077cc' },
  { name: 'Ensalada mixta',       family: 'Tapas',    units:  12, revenue:  8400, cost: 2200, avgDaily:  15, stock:  20, color: '#7857d6' },
  { name: 'Flan casero',          family: 'Postres',  units:  14, revenue:  7000, cost: 1500, avgDaily:  18, stock:  10, color: '#7857d6' },
  { name: 'Agua mineral',         family: 'Bebidas',  units:  67, revenue:  4690, cost:  200, avgDaily:  70, stock: 300, color: '#1a9e5a' },
];

export const MOCK_DEAD_STOCK = [
  { name: 'Gazpacho andaluz',  family: 'Tapas',    stock: 18, lastSale: 'hace 12 días', price: 580 },
  { name: 'Sangría jarra 1L',  family: 'Bebidas',  stock:  5, lastSale: 'hace 8 días',  price: 1800 },
  { name: 'Carpaccio de buey', family: 'Raciones', stock:  3, lastSale: 'hace 15 días', price: 1450 },
  { name: 'Tiramisú',          family: 'Postres',  stock:  7, lastSale: 'hace 9 días',  price: 680 },
];

export const MOCK_BY_FAMILY: FamilyData[] = [
  { label: 'Bebidas',  v:  89240, color: '#1a9e5a' },
  { label: 'Tapas',    v:  78650, color: '#ff4d4d' },
  { label: 'Raciones', v:  42180, color: '#d18a1c' },
  { label: 'Postres',  v:  22315, color: '#7857d6' },
  { label: 'Menú',     v:  15000, color: '#0077cc' },
];

export const MOCK_BY_METHOD = {
  cash:       { v:  78420, n: 31 },
  card:       { v: 132680, n: 38 },
  bizum:      { v:  24850, n: 9  },
  voucher:    { v:   6280, n: 3  },
  invitation: { v:   5155, n: 3  },
};

export const MOCK_ORDERS: Order[] = [
  { id: 'T-0512', zone: 'Mesa 8',     status: 'paid',      total:  8940, tip: 200, method: 'card',  time: '22:38', diners: 4,
    lines: [{ name: 'Croquetas', qty: 2, unitPrice: 1400, tax: 10, total: 2800 }, { name: 'Pulpo',  qty: 1, unitPrice: 1800, tax: 10, total: 1800 }, { name: 'Vino tinto', qty: 2, unitPrice: 350, tax: 21, total: 700 }, { name: 'Café solo', qty: 2, unitPrice: 150, tax: 10, total: 300 }] },
  { id: 'T-0511', zone: 'Mesa 12',    status: 'paid',      total:  4380, tip:   0, method: 'cash',  time: '22:32', diners: 2,
    lines: [{ name: 'Tortilla', qty: 1, unitPrice: 1200, tax: 10, total: 1200 }, { name: 'Cerveza', qty: 3, unitPrice: 250, tax: 21, total: 750 }] },
  { id: 'T-0510', zone: 'Barra 3',    status: 'paid',      total:   680, tip:   0, method: 'cash',  time: '22:28', diners: 1,
    lines: [{ name: 'Cerveza', qty: 2, unitPrice: 250, tax: 21, total: 500 }, { name: 'Aperitivo', qty: 1, unitPrice: 0, tax: 0, total: 0 }] },
  { id: 'T-0509', zone: 'Mesa 5',     status: 'paid',      total: 12450, tip: 350, method: 'card',  time: '22:15', diners: 5,
    lines: [{ name: 'Chuletón', qty: 2, unitPrice: 2200, tax: 10, total: 4400 }, { name: 'Patatas', qty: 2, unitPrice: 700, tax: 10, total: 1400 }, { name: 'Vino', qty: 1, unitPrice: 2400, tax: 21, total: 2400 }] },
  { id: 'T-0508', zone: 'Terraza 2',  status: 'paid',      total:  6720, tip:   0, method: 'bizum', time: '22:08', diners: 3,
    lines: [{ name: 'Calamares', qty: 1, unitPrice: 1300, tax: 10, total: 1300 }, { name: 'Cerveza', qty: 4, unitPrice: 250, tax: 21, total: 1000 }] },
  { id: 'T-0507', zone: 'Mesa 4',     status: 'paid',      total:  3780, tip: 100, method: 'card',  time: '21:54', diners: 2,
    lines: [{ name: 'Tortilla', qty: 1, unitPrice: 1200, tax: 10, total: 1200 }, { name: 'Pan rústico', qty: 2, unitPrice: 90, tax: 10, total: 180 }] },
  { id: 'T-0506', zone: 'Mesa 7',     status: 'paid',      total:  9820, tip:   0, method: 'mixed', time: '21:42', diners: 4 },
  { id: 'T-0505', zone: 'Barra 1',    status: 'paid',      total:  1240, tip:   0, method: 'cash',  time: '21:36', diners: 1 },
  { id: 'T-0504', zone: 'Mesa 10',    status: 'cancelled', total: 12450, tip:   0, method: 'card',  time: '21:24', diners: 3 },
  { id: 'T-0503', zone: 'Mesa 2',     status: 'paid',      total:  7280, tip: 200, method: 'card',  time: '21:18', diners: 3 },
  { id: 'T-0502', zone: 'Terraza 4',  status: 'paid',      total: 11320, tip: 500, method: 'card',  time: '21:08', diners: 6 },
  { id: 'T-0501', zone: 'Mesa 6',     status: 'paid',      total:  5840, tip:   0, method: 'bizum', time: '20:58', diners: 2 },
  { id: 'T-0500', zone: 'Mesa 3',     status: 'paid',      total:  8120, tip: 300, method: 'card',  time: '20:44', diners: 4 },
  { id: 'T-0499', zone: 'Mesa 9',     status: 'paid',      total:  3240, tip:   0, method: 'cash',  time: '20:32', diners: 2 },
  { id: 'T-0498', zone: 'Barra 2',    status: 'paid',      total:   920, tip:   0, method: 'cash',  time: '20:18', diners: 1 },
  { id: 'T-0497', zone: 'Mesa 11',    status: 'paid',      total:  6540, tip: 150, method: 'card',  time: '20:10', diners: 3 },
];

export const MOCK_ALERTS: FinanzasAlert[] = [
  { id: 1, type: 'warning',  title: 'Descuadre en sesión #134',        sub: '−25,00 € en sesión de Ana L.',          time: 'hace 6 días', tab: 'caja'      },
  { id: 2, type: 'critical', title: 'Pulpo a la gallega · stock crítico', sub: 'Quedan 6 uds, agotará el sábado',    time: 'hace 2h',     tab: 'productos' },
  { id: 3, type: 'info',     title: '4 productos sin ventas en 7 días', sub: 'Revisar o desactivar',                 time: 'hoy 09:00',   tab: 'productos' },
  { id: 4, type: 'critical', title: '1 ticket anulado de 124,50 €',    sub: 'Mesa 10, anulado por María G.',         time: 'hace 1h',     tab: 'ventas'    },
  { id: 5, type: 'info',     title: 'Modelo 303 – T2 cierra en 47 días', sub: 'Vence 20 julio 2026',                time: 'recordatorio',tab: 'impuestos' },
];

export const MOCK_EMPLOYEES: Employee[] = [
  { id: 'mg', name: 'María García', role: 'Encargada', initials: 'MG', color: '#ff4d4d',
    shift: '08:00 – cierre · 14h 38m', tickets: 28, revenue: 84620, avgTicket: 3023, items: 102,
    tips: 1820, discounts: 480, cancellations: 1, comp: 920, active: true,
    sparkRevenue: [72,78,82,76,88,84,91,75,80,83,86,90,78,85] },
  { id: 'cr', name: 'Carlos Ruiz', role: 'Camarero', initials: 'CR', color: '#0077cc',
    shift: '13:00 – 23:00 · 9h 38m', tickets: 22, revenue: 64280, avgTicket: 2922, items: 78,
    tips: 680, discounts: 0, cancellations: 0, comp: 0, active: true,
    sparkRevenue: [58,62,64,60,66,68,71,60,62,64,65,68,62,64] },
  { id: 'al', name: 'Ana López', role: 'Camarera', initials: 'AL', color: '#1a9e5a',
    shift: '08:00 – 16:00 · cerrado', tickets: 18, revenue: 52480, avgTicket: 2916, items: 64,
    tips: 240, discounts: 0, cancellations: 0, comp: 0, active: false,
    sparkRevenue: [48,52,50,49,55,56,58,50,51,53,54,55,50,52] },
  { id: 'pm', name: 'Pedro Moreno', role: 'Camarero (terraza)', initials: 'PM', color: '#7857d6',
    shift: '19:00 – cierre · 3h 38m', tickets: 14, revenue: 38260, avgTicket: 2733, items: 52,
    tips: 100, discounts: 2400, cancellations: 0, comp: 0, active: true,
    sparkRevenue: [32,35,38,36,42,44,48,38,36,38,40,42,38,38] },
  { id: 'js', name: 'Javier Sánchez', role: 'Barra', initials: 'JS', color: '#d18a1c',
    shift: '12:00 – 23:00 · 10h 38m', tickets: 2, revenue: 7745, avgTicket: 3872, items: 16,
    tips: 0, discounts: 0, cancellations: 0, comp: 0, active: true,
    sparkRevenue: [8,9,10,9,11,12,13,10,9,10,11,12,9,8] },
];

export const MOCK_CROSS_SELL: CrossSellPair[] = [
  { a: 'Chuletón 400g',      b: 'Vino tinto (copa)', together: 73, total: 9 },
  { a: 'Croquetas de jamón', b: 'Cerveza caña',       together: 68, total: 38 },
  { a: 'Patatas bravas',     b: 'Cerveza caña',       together: 62, total: 31 },
  { a: 'Pulpo a la gallega', b: 'Vino blanco',        together: 58, total: 14 },
  { a: 'Tarta de queso',     b: 'Café solo',          together: 84, total: 19, highlight: 'top' },
  { a: 'Tortilla española',  b: 'Pan rústico',        together: 91, total: 22, highlight: 'top' },
  { a: 'Calamares fritos',   b: 'Cerveza caña',       together: 57, total: 7 },
];

function mkSpark(base: number, dir: 'up' | 'down' | 'flat'): number[] {
  return Array.from({ length: 14 }, (_, i) => {
    const t = i / 13;
    const noise = (Math.sin(i * 1.7) + Math.cos(i * 0.8)) * base * 0.05;
    const trend = dir === 'up' ? t * base * 0.5 : dir === 'down' ? -t * base * 0.4 : 0;
    return Math.max(0, Math.round(base + trend + noise));
  });
}

export const MOCK_PRODUCT_TRENDS: Record<string, ProductTrend> = {
  'Cerveza caña':        { trend: 'flat', delta:   2.1, spark: mkSpark(120, 'flat') },
  'Vino tinto (copa)':   { trend: 'up',   delta:  18.4, spark: mkSpark(65,  'up')  },
  'Croquetas de jamón':  { trend: 'up',   delta:  12.6, spark: mkSpark(36,  'up')  },
  'Pulpo a la gallega':  { trend: 'up',   delta:  22.0, spark: mkSpark(13,  'up')  },
  'Tortilla española':   { trend: 'flat', delta:  -1.2, spark: mkSpark(22,  'flat')},
  'Patatas bravas':      { trend: 'flat', delta:   3.4, spark: mkSpark(30,  'flat')},
  'Chuletón 400g':       { trend: 'up',   delta:  14.0, spark: mkSpark(9,   'up')  },
  'Tarta de queso':      { trend: 'down', delta: -18.4, spark: mkSpark(19,  'down')},
  'Café solo':           { trend: 'flat', delta:   1.0, spark: mkSpark(88,  'flat')},
  'Coca-Cola':           { trend: 'down', delta: -12.0, spark: mkSpark(42,  'down')},
};

// ─── Mesas abiertas ────────────────────────────────────────────────────────────
export const MOCK_OPEN_TABLES: OpenTable[] = [
  { id: 'M-5',  zone: 'Mesa 5',    diners: 5, opened: '21:38', minutesOpen:  60, current:  8240, waiter: 'Carlos R.', state: 'eating',   lastEvent: 'Cafés pedidos 22:18'    },
  { id: 'M-8',  zone: 'Mesa 8',    diners: 4, opened: '21:12', minutesOpen:  86, current: 12480, waiter: 'María G.',  state: 'paying',   lastEvent: 'Cuenta pedida 22:30',   },
  { id: 'T-2',  zone: 'Terraza 2', diners: 3, opened: '20:55', minutesOpen: 103, current:  6720, waiter: 'Pedro M.',  state: 'eating',   lastEvent: 'Postres servidos 22:14' },
  { id: 'M-12', zone: 'Mesa 12',   diners: 2, opened: '22:08', minutesOpen:  30, current:  2480, waiter: 'Carlos R.', state: 'ordering', lastEvent: 'Comanda 22:10'          },
  { id: 'B-3',  zone: 'Barra 3',   diners: 1, opened: '21:48', minutesOpen:  50, current:  1820, waiter: 'Javier S.', state: 'idle',     lastEvent: 'Sin actividad 25 min',   alert: 'idle' },
  { id: 'M-1',  zone: 'Mesa 1',    diners: 6, opened: '20:42', minutesOpen: 116, current: 15640, waiter: 'María G.',  state: 'eating',   lastEvent: 'Segunda ronda 22:20',    alert: 'long' },
];

export const MOCK_PENDING_PAYMENTS: PendingPayment[] = [
  { id: 'T-0514', zone: 'Mesa 1',  total: 15640, since: '22:20', issue: 'cuenta_pedida' },
  { id: 'T-0511', zone: 'Mesa 12', total:  4380, since: '22:32', issue: 'sin_imprimir'  },
  { id: 'T-0507', zone: 'Mesa 4',  total:  3780, since: '21:54', issue: 'sin_imprimir'  },
];

export const MOCK_CANCELLATIONS: Cancellation[] = [
  { id: 'T-0504', date: '14/05 21:24', zone: 'Mesa 10', amount: 12450, reason: 'Cliente insatisfecho – plato frío',       who: 'María G.',  authorizedBy: 'María G.',  category: 'queja'      },
  { id: 'T-0498', date: '14/05 18:42', zone: 'Mesa 7',  amount:  3680, reason: 'Error en pedido – duplicado',             who: 'Pedro M.',  authorizedBy: 'María G.',  category: 'error'      },
  { id: 'T-0476', date: '13/05 14:18', zone: 'Mesa 3',  amount:  5240, reason: 'Cliente se fue sin pagar',                who: 'Ana L.',    authorizedBy: 'María G.',  category: 'fuga'       },
  { id: 'T-0452', date: '12/05 22:30', zone: 'Terraza', amount:  8920, reason: 'Plato devuelto – alergia no comunicada',  who: 'Carlos R.', authorizedBy: 'María G.',  category: 'devolucion' },
  { id: 'T-0431', date: '11/05 21:12', zone: 'Mesa 5',  amount:  1240, reason: 'Cobro duplicado – error TPV',             who: 'Carlos R.', authorizedBy: 'María G.',  category: 'error'      },
  { id: 'T-0418', date: '10/05 14:50', zone: 'Mesa 8',  amount:  6450, reason: 'Cliente cancela antes de servir',         who: 'Ana L.',    authorizedBy: 'Ana L.',    category: 'cancelacion'},
];

export const MOCK_PRE_CLOSE = {
  openTablesCount: 6,
  openTablesAmount: 47380,
  unprintedTickets: 2,
  unprintedAmount: 6260,
  undeclaredTips: 240,
  projectedDiff: -120,
  checklist: [
    { id: 1, label: 'Cerrar todas las mesas abiertas', done: false, blocking: true  },
    { id: 2, label: 'Imprimir tickets pendientes',      done: false, blocking: true  },
    { id: 3, label: 'Contar propinas declaradas',       done: true,  blocking: false },
    { id: 4, label: 'Sangría al banco si > 300 €',      done: true,  blocking: false },
    { id: 5, label: 'Verificar movimientos del turno',  done: true,  blocking: false },
    { id: 6, label: 'Arqueo ciego',                     done: false, blocking: true  },
    { id: 7, label: 'Imprimir Z y archivar',            done: false, blocking: true  },
  ] as PreCloseItem[],
};

export const MOCK_CASH_SESSION = {
  id: '135',
  operator: 'María G.',
  opened: '08:00',
  initial: 20000,
  cashPayments: 78420,
  cashIn:  15000,
  cashOut:  8500,
  movements: [
    { id: 'm1', type: 'in'  as const, reason: 'Fondo de cambio adicional', amount: 15000, time: '10:32', user: 'María G.'  },
    { id: 'm2', type: 'out' as const, reason: 'Pago a proveedor – pan',     amount:  4200, time: '13:14', user: 'María G.'  },
    { id: 'm3', type: 'out' as const, reason: 'Compra mercadería urgente',  amount:  4300, time: '17:48', user: 'Ana L.'   },
  ] as CashMovementItem[],
};

export const MOCK_CASH_HISTORY: CashSessionHistory[] = [
  { uuid: '00000134-0000-0000-0000-000000000000', id: '134', opened: '13/05 08:02', closed: '13/05 23:58', operator: 'Ana L.',   sales: 218940, theoretical: 116940, counted: 91940, diff: -25000, tickets: 76 },
  { uuid: '00000133-0000-0000-0000-000000000000', id: '133', opened: '12/05 08:10', closed: '12/05 23:42', operator: 'María G.', sales: 241280, theoretical: 126280, counted: 126280, diff: 0,     tickets: 79 },
  { uuid: '00000132-0000-0000-0000-000000000000', id: '132', opened: '11/05 08:05', closed: '11/05 23:55', operator: 'Carlos R.',sales: 228400, theoretical: 118400, counted: 120100, diff: 1700,  tickets: 75 },
  { uuid: '00000131-0000-0000-0000-000000000000', id: '131', opened: '10/05 08:00', closed: '10/05 23:48', operator: 'María G.', sales: 211000, theoretical: 106000, counted: 105200, diff: -800,  tickets: 71 },
  { uuid: '00000130-0000-0000-0000-000000000000', id: '130', opened: '09/05 08:12', closed: '09/05 23:52', operator: 'Ana L.',   sales: 195200, theoretical: 100200, counted: 100200, diff: 0,     tickets: 65 },
  { uuid: '00000129-0000-0000-0000-000000000000', id: '129', opened: '08/05 08:08', closed: '08/05 23:40', operator: 'María G.', sales: 203100, theoretical: 102100, counted: 101600, diff: -500,  tickets: 70 },
  { uuid: '00000128-0000-0000-0000-000000000000', id: '128', opened: '07/05 08:00', closed: '07/05 23:59', operator: 'Carlos R.',sales: 282000, theoretical: 148000, counted: 148000, diff: 0,     tickets: 96 },
  { uuid: '00000127-0000-0000-0000-000000000000', id: '127', opened: '06/05 08:04', closed: '06/05 23:50', operator: 'María G.', sales: 247000, theoretical: 128000, counted: 128400, diff: 400,   tickets: 82 },
];

export const MOCK_FORECAST: Forecast = {
  currentTime: '22:38',
  closed: 247385,
  projection: 248200,
  rangeMin: 243800,
  rangeMax: 253600,
  confidence: 78,
};

export const MOCK_TAX_BREAKDOWN: TaxSlab[] = [
  { rate:  4, label: 'IVA Superreducido', note: 'Pan, productos básicos', base:   7374, tax:    295, total:   7669 },
  { rate: 10, label: 'IVA Reducido',      note: 'Comidas y hostelería',   base: 168132, tax:  16813, total: 184945 },
  { rate: 21, label: 'IVA General',       note: 'Bebidas alcohólicas',    base:  45185, tax:   9489, total:  54674 },
];

export const MOCK_QUARTERLY: Record<string, QuarterVat> = {
  T1: { period: 'T1 · ene-mar · 2026', elapsed: 100, base4: 1129800, tax4:  45192, base10: 6943200, tax10: 694320, base21: 2427700, tax21: 509817 },
  T2: { period: 'T2 · abr-jun · 2026', elapsed:  47, base4:  531100, tax4:  21244, base10: 3263400, tax10: 326340, base21: 1141000, tax21: 239610 },
  T3: { period: 'T3 · jul-sep · 2026', elapsed:   0, base4:       0, tax4:      0, base10:       0, tax10:      0, base21:       0, tax21:      0 },
  T4: { period: 'T4 · oct-dic · 2026', elapsed:   0, base4:       0, tax4:      0, base10:       0, tax10:      0, base21:       0, tax21:      0 },
};

export const MOCK_HARDWARE: HardwareItem[] = [
  { id: 'tpv-sala',   name: 'TPV Sala',          status: 'ok',      icon: '⌗', detail: 'Online · sync hace 12s' },
  { id: 'imp-cocina', name: 'Impresora cocina',   status: 'warning', icon: '◰', detail: 'Papel bajo (15%)' },
  { id: 'imp-bar',    name: 'Impresora barra',    status: 'ok',      icon: '◰', detail: 'Online' },
  { id: 'imp-tic',    name: 'Impresora tickets',  status: 'ok',      icon: '◰', detail: 'Online' },
  { id: 'dataf',      name: 'Datáfono',           status: 'ok',      icon: '⌷', detail: 'Conectado · Redsys' },
  { id: 'kds-coc',    name: 'KDS cocina',         status: 'error',   icon: '◷', detail: 'Sin conexión hace 4 min', critical: true },
];

export const MOCK_LOCATIONS: Location[] = [
  { id: 'l1', name: 'La Tasca de Miguel',  city: 'Madrid · Malasaña',   revenue: 247385, tickets:  84, isCurrent: true  },
  { id: 'l2', name: 'La Tasca II',         city: 'Madrid · Lavapiés',   revenue: 198420, tickets:  67 },
  { id: 'l3', name: 'La Tasca Express',    city: 'Madrid · Chamberí',   revenue: 124680, tickets:  48 },
];

const _td_lines = [
  { name: 'Chuletón 400g',        qty: 2, unitPrice: 2890, total: 5780, tax: 10 },
  { name: 'Pulpo a la gallega',   qty: 1, unitPrice: 1500, total: 1500, tax: 10 },
  { name: 'Ensalada mixta',       qty: 1, unitPrice:  780, total:  780, tax: 10 },
  { name: 'Pan rústico',          qty: 2, unitPrice:  100, total:  200, tax:  4 },
  { name: 'Vino tinto (botella)', qty: 1, unitPrice: 1650, total: 1650, tax: 21 },
  { name: 'Agua mineral',         qty: 2, unitPrice:  100, total:  200, tax:  4 },
  { name: 'Café solo',            qty: 3, unitPrice:  110, total:  330, tax: 10 },
  { name: 'Tarta de queso',       qty: 2, unitPrice:  600, total: 1200, tax: 10 },
  { name: 'Chupito de orujo',     qty: 5, unitPrice:  160, total:  800, tax: 21 },
];
const _td_payments = [
  { method: 'card', amount: 8000, tip: 350 },
  { method: 'cash', amount: 4450, tip:   0 },
];
const _td_taxBreakdown = [
  { rate:  4, base:  385, tax:  15 },
  { rate: 10, base: 8918, tax: 892 },
  { rate: 21, base: 2025, tax: 425 },
];
const _td_subtotal  = _td_lines.reduce((s, l) => s + l.total, 0);
const _td_taxTotal  = _td_taxBreakdown.reduce((s, b) => s + b.tax, 0);
const _td_tipsTotal = _td_payments.reduce((s, p) => s + p.tip, 0);

export const MOCK_TICKET_DETAIL: TicketDetail = {
  id:       'T-0509',
  zone:     'Mesa 5',
  diners:   5,
  opened:   '21:38',
  closed:   '22:15',
  duration: '37 min',
  waiter:   'Carlos R.',
  status:   'paid',
  lines:    _td_lines,
  payments: _td_payments,
  taxBreakdown: _td_taxBreakdown,
  note:     'Mesa celebrando aniversario — 1 invitación de café',
  subtotal:  _td_subtotal,
  taxTotal:  _td_taxTotal,
  tipsTotal: _td_tipsTotal,
};

export const MOCK_PRODUCT_RANKING: ProductRanking[] = [
  { name: 'Cerveza caña',       family: 'Bebidas',  units: 124, revenue: 24800, cost:  72, price: 200,  avgDaily: 108, stock: 240, pct: 9.4 },
  { name: 'Vino tinto (copa)',  family: 'Bebidas',  units:  67, revenue: 23450, cost: 105, price: 350,  avgDaily:  62, stock:  84, pct: 8.9 },
  { name: 'Croquetas de jamón', family: 'Tapas',    units:  38, revenue: 22800, cost: 180, price: 600,  avgDaily:  34, stock:  32, pct: 8.7 },
  { name: 'Pulpo a la gallega', family: 'Raciones', units:  14, revenue: 21000, cost: 720, price: 1500, avgDaily:  13, stock:   6, pct: 8.0 },
  { name: 'Tortilla española',  family: 'Tapas',    units:  22, revenue: 19800, cost: 240, price: 900,  avgDaily:  20, stock:  18, pct: 7.5 },
  { name: 'Patatas bravas',     family: 'Tapas',    units:  31, revenue: 18600, cost: 110, price: 600,  avgDaily:  28, stock:  42, pct: 7.1 },
  { name: 'Chuletón 400g',      family: 'Raciones', units:   9, revenue: 26010, cost:1380, price: 2890, avgDaily:   8, stock:  12, pct: 9.9 },
  { name: 'Tarta de queso',     family: 'Postres',  units:  19, revenue: 11400, cost: 180, price: 600,  avgDaily:  17, stock:  14, pct: 4.3 },
  { name: 'Café solo',          family: 'Bebidas',  units:  88, revenue:  9680, cost:  18, price: 110,  avgDaily:  80, stock: 999, pct: 3.7 },
  { name: 'Coca-Cola',          family: 'Bebidas',  units:  42, revenue:  8400, cost:  55, price: 200,  avgDaily:  38, stock: 156, pct: 3.2 },
  { name: 'Pan rústico',        family: 'Tapas',    units:  78, revenue:  7800, cost:  20, price: 100,  avgDaily:  70, stock:  48, pct: 3.0 },
  { name: 'Agua mineral',       family: 'Bebidas',  units:  67, revenue:  6700, cost:  18, price: 100,  avgDaily:  62, stock: 220, pct: 2.5 },
  { name: 'Ensalada mixta',     family: 'Tapas',    units:  12, revenue:  9360, cost: 220, price: 780,  avgDaily:  10, stock:  28, pct: 3.6 },
  { name: 'Calamares fritos',   family: 'Raciones', units:   7, revenue: 11200, cost: 560, price: 1600, avgDaily:   6, stock:   9, pct: 4.3 },
  { name: 'Flan casero',        family: 'Postres',  units:  14, revenue:  6300, cost: 120, price: 450,  avgDaily:  13, stock:  22, pct: 2.4 },
];

export const MOCK_ZONES_LAYOUT: ZoneData[] = [
  { id: 'sala',      name: 'Sala interior', x:  5, y:  5, w: 50, h: 60, revenue: 124680, tickets: 42, occupancy: 78 },
  { id: 'barra',     name: 'Barra',         x: 60, y:  5, w: 35, h: 18, revenue:  28440, tickets: 18, occupancy: 55 },
  { id: 'terraza',   name: 'Terraza',       x: 60, y: 28, w: 35, h: 37, revenue:  78640, tickets: 19, occupancy: 88 },
  { id: 'reservado', name: 'Reservado',     x:  5, y: 70, w: 30, h: 25, revenue:   9120, tickets:  3, occupancy: 22 },
  { id: 'privado',   name: 'Salón privado', x: 40, y: 70, w: 55, h: 25, revenue:   6505, tickets:  2, occupancy: 18 },
];

export const MOCK_CANNIBALS: CannibalPair[] = [
  { a: 'Croquetas de jamón', b: 'Patatas bravas', overlap: 82, note: 'Aparecen casi siempre juntos pero rara vez ambos al inicio. Pueden estar canibalizando entrantes.' },
  { a: 'Coca-Cola',          b: 'Agua mineral',   overlap: 71, note: 'Comparten ocasión de consumo. Considerar combo.' },
];
