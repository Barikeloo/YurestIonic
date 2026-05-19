// data.jsx — Modelo real basado en YurestIonic
// • Precios en CÉNTIMOS (formatCents para mostrar)
// • Enums: OrderStatus, PaymentMethod, CajaState, UserRole, CashMovementReason

// ─── Enums ──────────────────────────────────────────
const OrderStatus = {
  OPEN: 'open',
  TO_CHARGE: 'to-charge',
  INVOICED: 'invoiced',
  CANCELLED: 'cancelled',
};

const PaymentMethod = {
  CASH: 'cash',
  CARD: 'card',
  BIZUM: 'bizum',
  MIXED: 'mixed',
  INVITATION: 'invitation',
  VOUCHER: 'voucher',
  OTHER: 'other',
};

const PaymentMethodLabels = {
  cash: 'Efectivo',
  card: 'Tarjeta',
  bizum: 'Bizum',
  mixed: 'Mixto',
  invitation: 'Invitación',
  voucher: 'Vale',
  other: 'Otro',
};

const PaymentMethodIcons = {
  cash: '💵',
  card: '💳',
  bizum: '📱',
  mixed: '🔀',
  invitation: '🎁',
  voucher: '🎟️',
  other: '💼',
};

const CajaState = {
  PRE_APERTURA: 'pre-apertura',
  ACTIVA: 'activa',
  ARQUEO: 'arqueo',
  HISTORICO: 'historico',
};

const UserRole = {
  OPERATOR: 'operator',
  SUPERVISOR: 'supervisor',
  ADMIN: 'admin',
};

const CashMovementType = { IN: 'in', OUT: 'out' };

const CashMovementReason = {
  CHANGE_REFILL:    { value: 'change_refill', label: 'Reposición de cambio', type: 'in' },
  TIP_DECLARED:     { value: 'tip_declared', label: 'Propina declarada', type: 'in' },
  ADJUSTMENT_IN:    { value: 'adjustment', label: 'Ajuste', type: 'in' },
  OTHER_IN:         { value: 'other', label: 'Otro', type: 'in' },
  SANGRIA:          { value: 'sangria', label: 'Sangría / retiro', type: 'out' },
  SUPPLIER_PAYMENT: { value: 'supplier_payment', label: 'Pago proveedor', type: 'out' },
  ADJUSTMENT_OUT:   { value: 'adjustment', label: 'Ajuste', type: 'out' },
  OTHER_OUT:        { value: 'other', label: 'Otro', type: 'out' },
};

// ─── Restaurante / usuario actual ───────────────────
const RESTAURANTE = {
  uuid: 'rest-voraz-01',
  name: 'Restaurante Voraz',
  cif: 'B-87654321',
  address: 'C/ Mayor 47, Madrid',
  phone: '+34 912 345 678',
  bizum: '+34 612 345 678',
};

const DEVICE = {
  id: 'PDA-A47',
  name: 'PDA Sala 2',
};

const USERS_QUICK = [
  { user_uuid: 'u-marina', name: 'Marina Aznar', role: 'operator', initials: 'MA' },
  { user_uuid: 'u-jose',   name: 'José Luis Pérez', role: 'operator', initials: 'JL' },
  { user_uuid: 'u-alba',   name: 'Alba Roca', role: 'supervisor', initials: 'AR' },
  { user_uuid: 'u-carlos', name: 'Carlos Ruiz', role: 'operator', initials: 'CR' },
  { user_uuid: 'u-elena',  name: 'Elena Vega', role: 'supervisor', initials: 'EV' },
  { user_uuid: 'u-david',  name: 'David Soto', role: 'admin', initials: 'DS' },
];

const CURRENT_USER = {
  id: 'u-marina',
  name: 'Marina Aznar',
  role: UserRole.OPERATOR,
  email: 'marina@restaurantevoraz.es',
  pin: '4287',
  initials: 'MA',
  shift: 'Cena · 18:00-23:30',
};

// ─── Zonas y mesas ──────────────────────────────────
const ZONES = [
  { id: 'z-terraza', name: 'Terraza' },
  { id: 'z-salon',   name: 'Salón' },
  { id: 'z-barra',   name: 'Barra' },
  { id: 'z-reservados', name: 'Reservados' },
];

// id, name, zone_id, capacidad, occupied, status, diners, remaining_total (cents),
// opened_at (date string), camarero (initials), lineas, merged_table_group_id
const TABLES = [
  // Terraza
  { id: 1,  name: 'T1', zone_id: 'z-terraza', capacidad: 2, occupied: false, status: null },
  { id: 2,  name: 'T2', zone_id: 'z-terraza', capacidad: 4, occupied: true, status: OrderStatus.OPEN,
    diners: 3, remaining_total: 4720, opened_at: '21:14', camarero: 'MA', lineas: 6 },
  { id: 3,  name: 'T3', zone_id: 'z-terraza', capacidad: 4, occupied: true, status: OrderStatus.TO_CHARGE,
    diners: 4, remaining_total: 11310, opened_at: '20:24', camarero: 'MA', lineas: 11, order_id: 'o-1247' },
  { id: 4,  name: 'T4', zone_id: 'z-terraza', capacidad: 2, occupied: false, status: null },
  { id: 5,  name: 'T5', zone_id: 'z-terraza', capacidad: 6, occupied: true, status: OrderStatus.OPEN,
    diners: 5, remaining_total: 8840, opened_at: '21:00', camarero: 'JL', lineas: 9 },
  { id: 6,  name: 'T6', zone_id: 'z-terraza', capacidad: 4, occupied: false, status: null, reserva: '22:30 · Familia Ortiz' },
  { id: 7,  name: 'T7', zone_id: 'z-terraza', capacidad: 4, occupied: true, status: OrderStatus.OPEN,
    diners: 2, remaining_total: 2450, opened_at: '21:30', camarero: 'MA', lineas: 4 },
  { id: 8,  name: 'T8', zone_id: 'z-terraza', capacidad: 2, occupied: false, status: null },
  // Salón
  { id: 9,  name: 'S1', zone_id: 'z-salon', capacidad: 2, occupied: true, status: OrderStatus.OPEN,
    diners: 2, remaining_total: 3200, opened_at: '21:24', camarero: 'MA', lineas: 5 },
  { id: 10, name: 'S2', zone_id: 'z-salon', capacidad: 2, occupied: false, status: null },
  { id: 11, name: 'S3', zone_id: 'z-salon', capacidad: 4, occupied: false, status: null },
  { id: 12, name: 'S4', zone_id: 'z-salon', capacidad: 4, occupied: true, status: OrderStatus.TO_CHARGE,
    diners: 3, remaining_total: 6480, opened_at: '20:46', camarero: 'JL', lineas: 7, order_id: 'o-1248' },
  { id: 13, name: 'S5', zone_id: 'z-salon', capacidad: 6, occupied: true, status: OrderStatus.OPEN,
    diners: 6, remaining_total: 14230, opened_at: '21:07', camarero: 'MA', lineas: 14,
    // Mesa fusionada S5 + S6
    merged_table_group_id: 'mg-1' },
  { id: 14, name: 'S6', zone_id: 'z-salon', capacidad: 4, occupied: true, status: OrderStatus.OPEN,
    diners: 0, remaining_total: 0, merged_table_group_id: 'mg-1' },
  { id: 15, name: 'S7', zone_id: 'z-salon', capacidad: 2, occupied: false, status: null },
  // Barra
  { id: 16, name: 'B1', zone_id: 'z-barra', capacidad: 1, occupied: true, status: OrderStatus.OPEN,
    diners: 1, remaining_total: 620, opened_at: '21:34', camarero: 'MA', lineas: 2 },
  { id: 17, name: 'B2', zone_id: 'z-barra', capacidad: 1, occupied: false, status: null },
  { id: 18, name: 'B3', zone_id: 'z-barra', capacidad: 1, occupied: true, status: OrderStatus.OPEN,
    diners: 1, remaining_total: 1150, opened_at: '21:28', camarero: 'JL', lineas: 3 },
  { id: 19, name: 'B4', zone_id: 'z-barra', capacidad: 1, occupied: false, status: null },
  // Reservados
  { id: 20, name: 'R1', zone_id: 'z-reservados', capacidad: 8, occupied: false, status: null, reserva: '22:00 · Cumple Lucía (8 pax)' },
  { id: 21, name: 'R2', zone_id: 'z-reservados', capacidad: 6, occupied: false, status: null },
];

// ─── Familias y productos ───────────────────────────
const FAMILIES = [
  { id: 'f-entrantes',   name: 'Entrantes' },
  { id: 'f-principales', name: 'Principales' },
  { id: 'f-bebidas',     name: 'Bebidas' },
  { id: 'f-cervezas',    name: 'Cervezas' },
  { id: 'f-vinos',       name: 'Vinos' },
  { id: 'f-postres',     name: 'Postres' },
  { id: 'f-cafe',        name: 'Café' },
];

// allergens: G L H F P M S A SE SU MO MZ LP CR CC
const ALERGENO_LABEL = {
  G: 'Gluten', L: 'Lácteos', H: 'Huevo', F: 'Frutos secos', P: 'Pescado',
  M: 'Mariscos', S: 'Soja', A: 'Apio', SE: 'Sésamo', SU: 'Sulfitos',
  MO: 'Moluscos', MZ: 'Mostaza', LP: 'Lupines', CR: 'Crustáceos', CC: 'Cacahuetes',
};

// Productos con: variants (tamaño), accompaniments (acompañamientos single/multi, required), extras
// price en CENTS
const PRODUCTS = [
  // Entrantes
  { id: 'p01', code: '101', family_id: 'f-entrantes', name: 'Croquetas de jamón', price: 850, iva: 10, emoji: '🥟', stock: 24, alergenos: ['G','L','H'] },
  { id: 'p02', code: '102', family_id: 'f-entrantes', name: 'Patatas bravas', price: 620, iva: 10, emoji: '🥔', stock: 40, alergenos: ['H'] },
  { id: 'p03', code: '103', family_id: 'f-entrantes', name: 'Tartar de atún', price: 1480, iva: 10, emoji: '🍣', stock: 8, alergenos: ['P','S','SE'] },
  { id: 'p04', code: '104', family_id: 'f-entrantes', name: 'Ensaladilla rusa', price: 740, iva: 10, emoji: '🥗', stock: 30, alergenos: ['H','P'] },
  { id: 'p05', code: '105', family_id: 'f-entrantes', name: 'Burrata con tomate', price: 1200, iva: 10, emoji: '🍅', stock: 12, alergenos: ['L'] },
  { id: 'p06', code: '106', family_id: 'f-entrantes', name: 'Pulpo a la brasa', price: 1850, iva: 10, emoji: '🐙', stock: 0, alergenos: ['MO'] },

  // Principales (con variants y modifiers)
  { id: 'p10', code: '201', family_id: 'f-principales', name: 'Solomillo a la pimienta',
    price: 2250, iva: 10, emoji: '🥩', stock: 14, alergenos: ['L','SU'],
    variants: [
      { id: 'v-sol-normal', name: 'Ración normal (200g)', price: 2250, stock: 14 },
      { id: 'v-sol-doble',  name: 'Ración doble (350g)', price: 2850, stock: 6 },
    ],
    accompaniments: [
      { id: 'm-acc1', name: 'Patatas fritas', price: 0, selection_type: 'single', is_required: true, is_default: true },
      { id: 'm-acc2', name: 'Patatas asadas', price: 0, selection_type: 'single', is_required: true },
      { id: 'm-acc3', name: 'Ensalada de la casa', price: 0, selection_type: 'single', is_required: true },
      { id: 'm-acc4', name: 'Verduras a la plancha', price: 150, selection_type: 'single', is_required: true },
    ],
    extras: [
      { id: 'm-ext1', name: 'Salsa pimienta extra', price: 100, selection_type: 'multi' },
      { id: 'm-ext2', name: 'Huevo a caballo', price: 200, selection_type: 'multi' },
      { id: 'm-ext3', name: 'Punto de sal Maldón', price: 0, selection_type: 'multi' },
    ],
    punto: ['Poco hecho','Al punto','Hecho','Muy hecho'],
  },
  { id: 'p11', code: '202', family_id: 'f-principales', name: 'Hamburguesa Voraz', price: 1490, iva: 10, emoji: '🍔', stock: 18, alergenos: ['G','L','H','MZ'],
    extras: [
      { id: 'm-h1', name: 'Bacon', price: 150, selection_type: 'multi' },
      { id: 'm-h2', name: 'Queso cheddar extra', price: 120, selection_type: 'multi' },
      { id: 'm-h3', name: 'Sin cebolla', price: 0, selection_type: 'multi' },
      { id: 'm-h4', name: 'Sin pepinillo', price: 0, selection_type: 'multi' },
    ],
  },
  { id: 'p12', code: '203', family_id: 'f-principales', name: 'Pizza margarita', price: 1150, iva: 10, emoji: '🍕', stock: 9, alergenos: ['G','L'] },
  { id: 'p13', code: '204', family_id: 'f-principales', name: 'Lubina al horno', price: 1980, iva: 10, emoji: '🐟', stock: 5, alergenos: ['P'] },
  { id: 'p14', code: '205', family_id: 'f-principales', name: 'Risotto de setas', price: 1320, iva: 10, emoji: '🍚', stock: 16, alergenos: ['L','A'] },
  { id: 'p15', code: '206', family_id: 'f-principales', name: 'Tagliatelle carbonara', price: 1280, iva: 10, emoji: '🍝', stock: 11, alergenos: ['G','L','H'] },

  // Bebidas
  { id: 'p20', code: '301', family_id: 'f-bebidas', name: 'Coca-Cola', price: 280, iva: 21, emoji: '🥤', stock: 50 },
  { id: 'p21', code: '302', family_id: 'f-bebidas', name: 'Agua mineral 50cl', price: 180, iva: 21, emoji: '💧', stock: 80 },
  { id: 'p22', code: '303', family_id: 'f-bebidas', name: 'Zumo de naranja', price: 350, iva: 21, emoji: '🍊', stock: 25 },
  { id: 'p23', code: '304', family_id: 'f-bebidas', name: 'Tónica Schweppes', price: 320, iva: 21, emoji: '🥃', stock: 36 },

  // Cervezas
  { id: 'p30', code: '401', family_id: 'f-cervezas', name: 'Mahou caña', price: 250, iva: 21, emoji: '🍺', stock: 99, alergenos: ['G'] },
  { id: 'p31', code: '402', family_id: 'f-cervezas', name: 'Estrella Galicia', price: 320, iva: 21, emoji: '🍺', stock: 64, alergenos: ['G'] },
  { id: 'p32', code: '403', family_id: 'f-cervezas', name: 'Voll-Damm', price: 380, iva: 21, emoji: '🍻', stock: 12, alergenos: ['G'] },

  // Vinos
  { id: 'p40', code: '501', family_id: 'f-vinos', name: 'Verdejo Rueda DO', price: 450, iva: 21, emoji: '🍷', stock: 18, alergenos: ['SU'] },
  { id: 'p41', code: '502', family_id: 'f-vinos', name: 'Ribera del Duero', price: 520, iva: 21, emoji: '🍷', stock: 0, alergenos: ['SU'] },
  { id: 'p42', code: '503', family_id: 'f-vinos', name: 'Champagne copa', price: 750, iva: 21, emoji: '🥂', stock: 13, alergenos: ['SU'] },

  // Postres
  { id: 'p50', code: '601', family_id: 'f-postres', name: 'Tarta de queso', price: 650, iva: 10, emoji: '🍰', stock: 7, alergenos: ['L','H','G'] },
  { id: 'p51', code: '602', family_id: 'f-postres', name: 'Coulant de chocolate', price: 720, iva: 10, emoji: '🍫', stock: 4, alergenos: ['L','H','G'] },
  { id: 'p52', code: '603', family_id: 'f-postres', name: 'Helado artesano', price: 550, iva: 10, emoji: '🍨', stock: 22, alergenos: ['L','H'] },

  // Café
  { id: 'p60', code: '701', family_id: 'f-cafe', name: 'Café solo', price: 140, iva: 10, emoji: '☕', stock: 99 },
  { id: 'p61', code: '702', family_id: 'f-cafe', name: 'Café cortado', price: 160, iva: 10, emoji: '☕', stock: 99, alergenos: ['L'] },
  { id: 'p62', code: '703', family_id: 'f-cafe', name: 'Café con leche', price: 180, iva: 10, emoji: '☕', stock: 99, alergenos: ['L'] },
  { id: 'p63', code: '704', family_id: 'f-cafe', name: 'Cappuccino', price: 230, iva: 10, emoji: '☕', stock: 99, alergenos: ['L'] },
];

// ─── Líneas pedido de mesa T3 (ejemplo TO_CHARGE) ───
const PEDIDO_T3_LINES = [
  { id: 'l1', product_id: 'p01', product_name: 'Croquetas de jamón', quantity: 2, price: 850, modifiers: [], status: 'servido' },
  { id: 'l2', product_id: 'p05', product_name: 'Burrata con tomate', quantity: 1, price: 1200, modifiers: [], status: 'servido' },
  { id: 'l3', product_id: 'p10', product_name: 'Solomillo a la pimienta', quantity: 1, price: 2250,
    variant_id: 'v-sol-normal', variant_name: 'Ración normal',
    modifiers: [
      { id: 'm-acc1', name: 'Patatas fritas', price: 0, kind: 'accompaniment' },
      { id: 'm-ext2', name: 'Huevo a caballo', price: 200, kind: 'extra' },
    ],
    punto: 'Poco hecho', status: 'servido' },
  { id: 'l4', product_id: 'p11', product_name: 'Hamburguesa Voraz', quantity: 1, price: 1490,
    modifiers: [
      { id: 'm-h3', name: 'Sin cebolla', price: 0, kind: 'extra' },
      { id: 'm-h1', name: 'Bacon', price: 150, kind: 'extra' },
    ], status: 'servido' },
  { id: 'l5', product_id: 'p13', product_name: 'Lubina al horno', quantity: 1, price: 1980, modifiers: [], status: 'servido' },
  { id: 'l6', product_id: 'p40', product_name: 'Verdejo Rueda DO', quantity: 4, price: 450, modifiers: [], status: 'servido' },
  { id: 'l7', product_id: 'p21', product_name: 'Agua mineral 50cl', quantity: 2, price: 180, modifiers: [], status: 'servido' },
  { id: 'l8', product_id: 'p51', product_name: 'Coulant de chocolate', quantity: 2, price: 720, modifiers: [], status: 'enviado', nota: 'Para compartir' },
  { id: 'l9', product_id: 'p61', product_name: 'Café cortado', quantity: 2, price: 160, modifiers: [], status: 'enviado' },
  { id: 'l10', product_id: 'p62', product_name: 'Café con leche', quantity: 1, price: 180, modifiers: [], status: 'enviado' },
];

// ─── Caja: sesión activa ────────────────────────────
const CASH_SESSION = {
  id: 'cs-2026-05-27',
  status: 'open',
  device_id: 'PDA-A47',
  operator_name: 'Marina Aznar',
  operator_id: 'u-marina',
  opened_at: '2026-05-27T18:00:00',
  initial_amount_cents: 15000, // 150€
  total_in_movements: 2500,
  total_out_movements: 1500,
  total_sales: 73420,
  payments_count: 17,
  total_cash_payments: 28450,
  total_card_payments: 38470,
  total_bizum_payments: 6500,
  expected_amount: 44450, // initial + cash + in - out
};

// ─── Histórico de cierres ──────────────────────────
const CLOSED_SESSIONS = [
  { z_report_number: 47, opened_at: '2026-05-26T18:00:00', closed_at: '2026-05-26T23:42:00',
    device_id: 'PDA-A47', operator_name: 'Marina Aznar',
    initial_amount_cents: 15000, final_amount_cents: 52380, expected_amount: 52380,
    discrepancy_cents: 0, tickets: 23, diners: 78,
    total_sales: 89240, total_cash: 32450, total_card: 49830, total_bizum: 6960 },
  { z_report_number: 46, opened_at: '2026-05-25T18:00:00', closed_at: '2026-05-25T23:38:00',
    device_id: 'PDA-A47', operator_name: 'José Luis Pérez',
    initial_amount_cents: 15000, final_amount_cents: 47820, expected_amount: 48050,
    discrepancy_cents: -230, diffReason: 'Cambio mal entregado',
    tickets: 19, diners: 61,
    total_sales: 72340, total_cash: 28520, total_card: 38820, total_bizum: 5000 },
  { z_report_number: 45, opened_at: '2026-05-24T18:00:00', closed_at: '2026-05-24T23:25:00',
    device_id: 'PDA-A47', operator_name: 'Marina Aznar',
    initial_amount_cents: 15000, final_amount_cents: 38450, expected_amount: 38320,
    discrepancy_cents: 130, diffReason: 'Propina no declarada',
    tickets: 16, diners: 52,
    total_sales: 56720, total_cash: 21420, total_card: 30300, total_bizum: 5000 },
];

const ORPHAN_SESSION = null; // o { device_id, opened_at } si quieres simular

// ─── Movimientos de caja ────────────────────────────
const CASH_MOVEMENTS = [
  { id: 'mv1', type: 'in', reason: 'change_refill', amount_cents: 2500, description: 'Cambio inicial extra', operator: 'MA', time: '20:15' },
  { id: 'mv2', type: 'out', reason: 'supplier_payment', amount_cents: 1500, description: 'Pago panadería', operator: 'JL', time: '19:42' },
];

// ─── Pedidos activos para vista "Pedidos" ───────────
const ACTIVE_ORDERS = [
  { order_id: 'o-1247', table_name: 'T3', zone: 'Terraza', opened_at: '20:24', status: OrderStatus.TO_CHARGE, total: 11310, lineas: 11, diners: 4, camarero: 'MA' },
  { order_id: 'o-1248', table_name: 'S4', zone: 'Salón',   opened_at: '20:46', status: OrderStatus.TO_CHARGE, total: 6480, lineas: 7, diners: 3, camarero: 'JL' },
  { order_id: 'o-1242', table_name: 'T2', zone: 'Terraza', opened_at: '21:14', status: OrderStatus.OPEN, total: 4720, lineas: 6, diners: 3, camarero: 'MA' },
  { order_id: 'o-1244', table_name: 'T5', zone: 'Terraza', opened_at: '21:00', status: OrderStatus.OPEN, total: 8840, lineas: 9, diners: 5, camarero: 'JL' },
  { order_id: 'o-1245', table_name: 'T7', zone: 'Terraza', opened_at: '21:30', status: OrderStatus.OPEN, total: 2450, lineas: 4, diners: 2, camarero: 'MA' },
  { order_id: 'o-1246', table_name: 'S5+S6', zone: 'Salón', opened_at: '21:07', status: OrderStatus.OPEN, total: 14230, lineas: 14, diners: 6, camarero: 'MA', merged: true },
  { order_id: 'o-1249', table_name: 'S1', zone: 'Salón',   opened_at: '21:24', status: OrderStatus.OPEN, total: 3200, lineas: 5, diners: 2, camarero: 'MA' },
  { order_id: 'o-1250', table_name: 'B1', zone: 'Barra',   opened_at: '21:34', status: OrderStatus.OPEN, total: 620, lineas: 2, diners: 1, camarero: 'MA' },
  { order_id: 'o-1251', table_name: 'B3', zone: 'Barra',   opened_at: '21:28', status: OrderStatus.OPEN, total: 1150, lineas: 3, diners: 1, camarero: 'JL' },
];

// ─── Avisos de cocina (real-time) ───────────────────
const AVISOS_COCINA = [
  { id: 'a1', mesa: 'T5', tipo: 'listo', plato: 'Solomillo a la pimienta ×2', hora: 'hace 30s', urgente: true },
  { id: 'a2', mesa: 'S5', tipo: 'listo', plato: 'Tagliatelle carbonara', hora: 'hace 1 min', urgente: false },
  { id: 'a3', mesa: 'T2', tipo: 'listo', plato: 'Pizza margarita', hora: 'hace 2 min', urgente: false },
  { id: 'a4', mesa: 'S1', tipo: 'demora', plato: 'Tarta de queso · 8 min en cola', hora: 'hace 3 min', urgente: true },
  { id: 'a5', mesa: 'T3', tipo: 'rotura', plato: 'Sin Ribera del Duero', hora: 'hace 5 min', urgente: false },
];

// ─── Charge session de ejemplo (split bill) ─────────
// Para una mesa de 4 comensales, 2 ya han pagado
const SAMPLE_CHARGE_SESSION = {
  id: 'cs-1247',
  order_id: 'o-1247',
  diners_count: 4,
  paid_diner_numbers: [], // ningún comensal ha pagado todavía
  remaining_cents: 11310,
  total_cents: 11310,
  suggested_per_diner_cents: 2828,
};

window.YDATA = {
  // enums
  OrderStatus, PaymentMethod, PaymentMethodLabels, PaymentMethodIcons,
  CajaState, UserRole, CashMovementType, CashMovementReason,
  // data
  RESTAURANTE, DEVICE, USERS_QUICK, CURRENT_USER,
  ZONES, TABLES, FAMILIES, PRODUCTS, ALERGENO_LABEL,
  PEDIDO_T3_LINES, ACTIVE_ORDERS, AVISOS_COCINA,
  CASH_SESSION, CLOSED_SESSIONS, ORPHAN_SESSION, CASH_MOVEMENTS,
  SAMPLE_CHARGE_SESSION,
};
