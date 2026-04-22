// screens.jsx — PreApertura, Dashboard (3 variantes), Histórico, Barra

// ── DATA COMPARTIDA ──────────────────────────────────────
const SESSION = {
  operator: 'María G.', openedAt: '09:30',
  initial: 15000,
  sales: { total: 183550, tickets: 23, average: 7980, diners: 61 },
  byMethod: { cash: 72050, card: 98700, bizum: 14000, voucher: 1200, invitation: 1800 },
  movements: [
    { id: 1, type: 'in', reason: 'Reposición cambio', amount: 5000, time: '11:14', user: 'María G.' },
    { id: 2, type: 'out', reason: 'Sangría al banco', amount: 20000, time: '13:30', user: 'Ana L.' },
    { id: 3, type: 'in', reason: 'Propina declarada', amount: 800, time: '14:02', user: 'Carlos R.' },
  ],
  tickets: [
    { id: 'T-0423', table: 'Mesa 4', total: 3780, method: 'card', time: '14:52', status: 'closed' },
    { id: 'T-0422', table: 'Mesa 7', total: 8940, method: 'mixed', time: '14:31', status: 'closed' },
    { id: 'T-0421', table: 'Barra', total: 680, method: 'cash', time: '14:18', status: 'closed' },
    { id: 'T-0420', table: 'Mesa 2', total: 12450, method: 'card', time: '13:55', status: 'cancelled' },
  ],
  mesasAbiertas: [
    { id: 1, name: 'Mesa 2', diners: 4, total: 8940, time: '11:30' },
    { id: 2, name: 'Mesa 5', diners: 2, total: 4520, time: '12:15' },
    { id: 3, name: 'Mesa 8', diners: 6, total: 15670, time: '13:00' },
  ],
  expected: 72050,
};

const METHOD_COLORS = { cash: '#1a9e5a', card: '#3d3d3d', bizum: '#0077cc', voucher: '#5a5a5a', invitation: '#ff4d4d' };
const METHOD_LABELS = { cash: 'Efectivo', card: 'Tarjeta', bizum: 'Bizum', voucher: 'Vale', invitation: 'Invitación' };

// ── SUB-COMPONENTES COMPARTIDOS ──────────────────────────
function MethodBar() {
  const total = Object.values(SESSION.byMethod).reduce((a, b) => a + b, 0);
  return (
    <Card>
      <div style={{ fontSize: 12, fontWeight: 600, color: '#5a5a5a', fontFamily: 'DM Sans', marginBottom: 12 }}>Desglose por método</div>
      <div style={{ display: 'flex', borderRadius: 6, overflow: 'hidden', height: 8, marginBottom: 12, gap: 2 }}>
        {Object.entries(SESSION.byMethod).map(([m, v]) => (
          <div key={m} style={{ flex: v, background: METHOD_COLORS[m], minWidth: 2 }} />
        ))}
      </div>
      {Object.entries(SESSION.byMethod).map(([m, v]) => (
        <div key={m} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '5px 0', borderBottom: '1px solid #f4f4f4' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <div style={{ width: 8, height: 8, borderRadius: 2, background: METHOD_COLORS[m], flexShrink: 0 }} />
            <span style={{ fontFamily: 'DM Sans', fontSize: 13, color: '#3d3d3d' }}>{METHOD_LABELS[m]}</span>
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <span style={{ fontFamily: "'DM Mono', monospace", fontSize: 13, fontWeight: 600 }}>{fmt(v)}</span>
            <span style={{ fontSize: 10, color: '#a0a0a0', fontFamily: 'DM Sans', width: 28, textAlign: 'right' }}>{Math.round(v / total * 100)}%</span>
          </div>
        </div>
      ))}
    </Card>
  );
}

function MovimientosList({ onMovimiento }) {
  return (
    <Card>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 }}>
        <div style={{ fontSize: 12, fontWeight: 600, color: '#5a5a5a', fontFamily: 'DM Sans' }}>Movimientos manuales</div>
        <div style={{ display: 'flex', gap: 6 }}>
          <Btn size="sm" variant="outline" color="#1a9e5a" onClick={onMovimiento}>+ Entrada</Btn>
          <Btn size="sm" variant="outline" color="#ff4d4d" onClick={onMovimiento}>− Salida</Btn>
        </div>
      </div>
      {SESSION.movements.map(m => (
        <div key={m.id} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '8px 0', borderBottom: '1px solid #f4f4f4' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
            <div style={{ width: 30, height: 30, borderRadius: 8, background: m.type === 'in' ? '#e8f7ef' : '#ffecec', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 14, flexShrink: 0 }}>
              {m.type === 'in' ? '↑' : '↓'}
            </div>
            <div>
              <div style={{ fontSize: 12, fontFamily: 'DM Sans', fontWeight: 600, color: '#0d0d0d' }}>{m.reason}</div>
              <div style={{ fontSize: 10, color: '#a0a0a0', fontFamily: 'DM Sans' }}>{m.time} · {m.user}</div>
            </div>
          </div>
          <span style={{ fontFamily: "'DM Mono', monospace", fontSize: 13, fontWeight: 600, color: m.type === 'in' ? '#1a9e5a' : '#ff4d4d' }}>
            {m.type === 'in' ? '+' : '−'}{fmt(m.amount)}
          </span>
        </div>
      ))}
      {SESSION.movements.length === 0 && (
        <div style={{ textAlign: 'center', padding: '20px 0', color: '#a0a0a0', fontFamily: 'DM Sans', fontSize: 13 }}>Sin movimientos</div>
      )}
    </Card>
  );
}

function TicketsList({ role }) {
  return (
    <Card>
      <div style={{ fontSize: 12, fontWeight: 600, color: '#5a5a5a', fontFamily: 'DM Sans', marginBottom: 12 }}>Últimos tickets del turno</div>
      {SESSION.tickets.map(t => (
        <div key={t.id} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '8px 0', borderBottom: '1px solid #f4f4f4', opacity: t.status === 'cancelled' ? 0.5 : 1 }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
            <div style={{ width: 30, height: 30, borderRadius: 8, background: t.status === 'cancelled' ? '#ffecec' : '#f4f4f4', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 13, flexShrink: 0 }}>
              🧾
            </div>
            <div>
              <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                <span style={{ fontFamily: 'DM Sans', fontSize: 13, fontWeight: 600, color: '#0d0d0d' }}>{t.table}</span>
                {t.status === 'cancelled' && <Badge text="Anulado" color="#ff4d4d" />}
              </div>
              <div style={{ fontSize: 10, color: '#a0a0a0', fontFamily: 'DM Sans' }}>{t.id} · {t.time} · {METHOD_LABELS[t.method]}</div>
            </div>
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
            <span style={{ fontFamily: "'DM Mono', monospace", fontSize: 13, fontWeight: 600, textDecoration: t.status === 'cancelled' ? 'line-through' : 'none' }}>{fmt(t.total)}</span>
            {t.status !== 'cancelled' && ROLES[role]?.canCancelSale && (
              <button style={{ padding: '3px 8px', borderRadius: 6, border: '1.5px solid #e8e8e8', background: 'none', fontSize: 11, color: '#a0a0a0', cursor: 'pointer', fontFamily: 'DM Sans' }}>Anular</button>
            )}
          </div>
        </div>
      ))}
    </Card>
  );
}

function MesasAbiertas({ onCobrar, onSplit }) {
  return (
    <Card>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 }}>
        <div style={{ fontSize: 12, fontWeight: 600, color: '#5a5a5a', fontFamily: 'DM Sans' }}>Mesas con cuenta pendiente</div>
        <Badge text={`${SESSION.mesasAbiertas.length} abiertas`} color="#ff4d4d" />
      </div>
      {SESSION.mesasAbiertas.map(m => (
        <div key={m.id} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '9px 0', borderBottom: '1px solid #f4f4f4' }}>
          <div>
            <div style={{ fontFamily: 'DM Sans', fontSize: 13, fontWeight: 600, color: '#0d0d0d' }}>{m.name}</div>
            <div style={{ fontSize: 11, color: '#a0a0a0', fontFamily: 'DM Sans' }}>{m.diners} comensales · desde {m.time}</div>
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <span style={{ fontFamily: "'DM Mono', monospace", fontSize: 14, fontWeight: 700 }}>{fmt(m.total)}</span>
            <Btn size="sm" variant="fill" onClick={() => onCobrar(m.total, m.name)}>Cobrar</Btn>
            <Btn size="sm" variant="outline" color="#5a5a5a" onClick={onSplit}>Dividir</Btn>
          </div>
        </div>
      ))}
    </Card>
  );
}

function AlertsBar() {
  return (
    <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', marginBottom: 12 }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 6, background: '#ffecec', borderRadius: 8, padding: '6px 12px', fontSize: 12, fontFamily: 'DM Sans', color: '#ff7070' }}>
        <span>⚠</span><span>2 invitaciones · valor teórico {fmt(1250)}</span>
      </div>
      <div style={{ display: 'flex', alignItems: 'center', gap: 6, background: '#ffecec', borderRadius: 8, padding: '6px 12px', fontSize: 12, fontFamily: 'DM Sans', color: '#ff4d4d' }}>
        <span>⚠</span><span>1 ticket anulado · Mesa 2 · {fmt(12450)}</span>
      </div>
    </div>
  );
}

// ── PRE-APERTURA SCREEN ──────────────────────────────────
function PreAperturaScreen({ onAbrir, role, showOrphan }) {
  return (
    <div style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center', background: '#fafafa', padding: 32 }}>
      <div style={{ maxWidth: 440, width: '100%' }}>

        {/* Sesión huérfana */}
        {showOrphan && (
          <Card style={{ background: '#ffecec', border: '1.5px solid #ff4d4d', marginBottom: 20 }} padding={16}>
            <div style={{ fontSize: 13, fontWeight: 700, color: '#ff4d4d', fontFamily: 'DM Sans', marginBottom: 4 }}>
              ⚠ Turno sin cerrar detectado
            </div>
            <div style={{ fontSize: 12, color: '#ff7070', fontFamily: 'DM Sans', marginBottom: 12 }}>
              Turno del 21/04 a las 14:30 (Ana L.) no fue cerrado correctamente en TPV Sala.
            </div>
            {ROLES[role]?.canForceClose
              ? <Btn size="sm" variant="danger">Forzar cierre del turno anterior</Btn>
              : <div style={{ fontSize: 12, color: '#ff7070', fontFamily: 'DM Sans' }}>Contacta con un administrador para continuar.</div>
            }
          </Card>
        )}

        {/* Último cierre */}
        <Card style={{ marginBottom: 24 }} padding={20}>
          <div style={{ fontSize: 10, color: '#a0a0a0', fontFamily: 'DM Sans', marginBottom: 12, textTransform: 'uppercase', letterSpacing: '0.5px' }}>Último cierre · Z #41</div>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 16 }}>
            <div>
              <div style={{ fontSize: 16, fontWeight: 700, fontFamily: 'DM Sans', color: '#0d0d0d' }}>Ana López</div>
              <div style={{ fontSize: 12, color: '#7a7a7a', fontFamily: 'DM Sans' }}>21/04 · 22:47 → 23:02</div>
            </div>
            <div style={{ textAlign: 'right' }}>
              <div style={{ fontSize: 24, fontFamily: "'DM Mono', monospace", fontWeight: 700 }}>{fmt(183550)}</div>
              <Badge text="Descuadre − 12,00 €" color="#ff4d4d" />
            </div>
          </div>
          <div style={{ background: '#f4f4f4', borderRadius: 8, padding: '10px 14px', display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 8 }}>
            {[['Tickets', '23'], ['Comensales', '61'], ['Ticket medio', '€ 79,80']].map(([k, v]) => (
              <div key={k} style={{ textAlign: 'center' }}>
                <div style={{ fontSize: 16, fontFamily: "'DM Mono', monospace", fontWeight: 700, color: '#0d0d0d' }}>{v}</div>
                <div style={{ fontSize: 10, color: '#a0a0a0', fontFamily: 'DM Sans' }}>{k}</div>
              </div>
            ))}
          </div>
        </Card>

        <div style={{ textAlign: 'center' }}>
          <div style={{ fontSize: 13, color: '#a0a0a0', fontFamily: 'DM Sans', marginBottom: 16 }}>
            {showOrphan ? 'Resuelve el turno huérfano antes de abrir' : 'La caja está cerrada · TPV Sala'}
          </div>
          <Btn size="lg" onClick={onAbrir} block variant="success" disabled={showOrphan && !ROLES[role]?.canForceClose}>
            Abrir caja
          </Btn>
        </div>
      </div>
    </div>
  );
}

// ── DASHBOARD — VARIANTE SPLIT (estándar) ────────────────
function DashboardSplit({ role, onMovimiento, onCobrar, onSplit }) {
  return (
    <div style={{ flex: 1, overflowY: 'auto', padding: 16, background: '#fafafa', display: 'flex', flexDirection: 'column', gap: 12 }}>
      {/* KPIs */}
      <div style={{ display: 'flex', gap: 10 }}>
        <KpiCard label="Ventas netas" value={fmt(SESSION.sales.total)} color="#1a9e5a" />
        <KpiCard label="Tickets" value={SESSION.sales.tickets} sub={`${SESSION.sales.diners} comensales`} mono={false} />
        <KpiCard label="Ticket medio" value={fmt(SESSION.sales.average)} />
        <KpiCard label="Efectivo teórico" value={fmt(SESSION.expected)} sub={`Fondo: ${fmt(SESSION.initial)}`} />
      </div>

      <AlertsBar />

      {/* Mesas abiertas */}
      <MesasAbiertas onCobrar={onCobrar} onSplit={onSplit} />

      {/* Desglose + Movimientos */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
        <MethodBar />
        <MovimientosList onMovimiento={onMovimiento} />
      </div>

      <TicketsList role={role} />
    </div>
  );
}

// ── DASHBOARD — VARIANTE SUMMARY (efectivo destacado) ────
function DashboardSummary({ role, onMovimiento, onCobrar, onSplit }) {
  return (
    <div style={{ flex: 1, overflowY: 'auto', padding: 16, background: '#fafafa', display: 'flex', flexDirection: 'column', gap: 12 }}>
      <AlertsBar />

      {/* Hero row */}
      <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr', gap: 12 }}>
        {/* Cash hero card */}
        <div style={{ background: 'linear-gradient(135deg, #1a9e5a 0%, #14854c 100%)', borderRadius: 14, padding: 24, display: 'flex', flexDirection: 'column', justifyContent: 'space-between', minHeight: 150 }}>
          <div>
            <div style={{ fontSize: 10, color: 'rgba(255,255,255,0.65)', fontFamily: 'DM Sans', textTransform: 'uppercase', letterSpacing: '0.6px', marginBottom: 6 }}>Efectivo en caja · teórico</div>
            <div style={{ fontSize: 48, fontFamily: "'DM Mono', monospace", fontWeight: 700, color: '#fff', lineHeight: 1, letterSpacing: -2 }}>
              {fmtNum(SESSION.expected)}<span style={{ fontSize: '0.4em', opacity: 0.6, fontWeight: 400 }}> €</span>
            </div>
            <div style={{ fontSize: 12, color: 'rgba(255,255,255,0.55)', fontFamily: 'DM Sans', marginTop: 4 }}>
              Fondo inicial: {fmt(SESSION.initial)} · +{fmt(5000)} entradas · −{fmt(20000)} salidas
            </div>
          </div>
          <div style={{ display: 'flex', gap: 8, marginTop: 14 }}>
            <button onClick={onMovimiento} style={{ padding: '7px 14px', borderRadius: 8, border: '1.5px solid rgba(255,255,255,0.45)', background: 'none', color: 'rgba(255,255,255,0.9)', fontFamily: 'DM Sans', fontSize: 12, fontWeight: 600, cursor: 'pointer' }}>+ Entrada</button>
            <button onClick={onMovimiento} style={{ padding: '7px 14px', borderRadius: 8, border: '1.5px solid rgba(255,255,255,0.45)', background: 'none', color: 'rgba(255,255,255,0.9)', fontFamily: 'DM Sans', fontSize: 12, fontWeight: 600, cursor: 'pointer' }}>− Salida</button>
          </div>
        </div>

        {/* KPI stacked */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
          <KpiCard label="Ventas netas" value={fmt(SESSION.sales.total)} color="#1a9e5a" />
          <KpiCard label="Tickets" value={SESSION.sales.tickets} sub={`${SESSION.sales.diners} comensales`} mono={false} />
          <KpiCard label="Ticket medio" value={fmt(SESSION.sales.average)} />
        </div>
      </div>

      <MesasAbiertas onCobrar={onCobrar} onSplit={onSplit} />

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
        <MethodBar />
        <MovimientosList onMovimiento={onMovimiento} />
      </div>
    </div>
  );
}

// ── DASHBOARD — VARIANTE COMPACT (densa, 3 columnas) ─────
function DashboardCompact({ role, onMovimiento, onCobrar, onSplit }) {
  return (
    <div style={{ flex: 1, overflowY: 'auto', padding: 14, background: '#fafafa', display: 'flex', flexDirection: 'column', gap: 10 }}>
      {/* KPIs compactos */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 8 }}>
        {[
          { l: 'Ventas netas', v: fmt(SESSION.sales.total), c: '#1a9e5a' },
          { l: 'Tickets', v: `${SESSION.sales.tickets}`, c: '#0d0d0d' },
          { l: 'Ticket medio', v: fmt(SESSION.sales.average), c: '#0d0d0d' },
          { l: 'Efectivo teórico', v: fmt(SESSION.expected), c: '#0d0d0d' },
        ].map(k => (
          <Card key={k.l} padding={12}>
            <div style={{ fontSize: 9, color: '#a0a0a0', fontFamily: 'DM Sans', textTransform: 'uppercase', letterSpacing: '0.6px', marginBottom: 4 }}>{k.l}</div>
            <div style={{ fontSize: 18, fontFamily: "'DM Mono', monospace", fontWeight: 700, color: k.c, lineHeight: 1 }}>{k.v}</div>
          </Card>
        ))}
      </div>

      <AlertsBar />

      {/* 3 columnas */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 10, flex: 1 }}>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
          <MethodBar />
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
          <MesasAbiertas onCobrar={onCobrar} onSplit={onSplit} />
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
          <MovimientosList onMovimiento={onMovimiento} />
          <TicketsList role={role} />
        </div>
      </div>
    </div>
  );
}

// ── DASHBOARD WRAPPER ────────────────────────────────────
function DashboardScreen({ variant, role, onMovimiento, onCobrar, onSplit }) {
  if (variant === 'summary') return <DashboardSummary role={role} onMovimiento={onMovimiento} onCobrar={onCobrar} onSplit={onSplit} />;
  if (variant === 'compact') return <DashboardCompact role={role} onMovimiento={onMovimiento} onCobrar={onCobrar} onSplit={onSplit} />;
  return <DashboardSplit role={role} onMovimiento={onMovimiento} onCobrar={onCobrar} onSplit={onSplit} />;
}

// ── HISTÓRICO SCREEN ─────────────────────────────────────
function HistoricoScreen({ onViewZ }) {
  const [filter, setFilter] = React.useState('all');
  const [search, setSearch] = React.useState('');

  const sessions = [
    { id: 1, zNum: 42, date: '21/04/2026', opened: '09:30', closed: '22:47', operator: 'Ana L.', tickets: 23, diners: 61, total: 183550, net: 183550, gross: 184750, discounts: 1200, cash: 72050, diff: -1200, diffReason: 'Error conteo', invitations: 2, invValue: 1250, cancellations: 1, initial: 15000, movIn: 5000, movOut: 20000, expected: 72050, counted: 70850 },
    { id: 2, zNum: 41, date: '20/04/2026', opened: '10:00', closed: '23:15', operator: 'Carlos R.', tickets: 31, diners: 78, total: 241200, net: 241200, gross: 241200, discounts: 800, cash: 98000, diff: 0, invitations: 0, invValue: 0, cancellations: 0, initial: 15000, movIn: 3000, movOut: 25000, expected: 98000, counted: 98000 },
    { id: 3, zNum: 40, date: '19/04/2026', opened: '09:45', closed: '22:30', operator: 'María G.', tickets: 18, diners: 42, total: 134800, net: 134800, gross: 134800, discounts: 0, cash: 55000, diff: 500, invitations: 1, invValue: 850, cancellations: 0, initial: 15000, movIn: 0, movOut: 15000, expected: 55000, counted: 55500 },
    { id: 4, zNum: 39, date: '18/04/2026', opened: '10:15', closed: '22:00', operator: 'Ana L.', tickets: 27, diners: 55, total: 198400, net: 198400, gross: 200200, discounts: 1800, cash: 81000, diff: -2300, diffReason: 'Propina no declarada', invitations: 3, invValue: 1800, cancellations: 2, initial: 15000, movIn: 5000, movOut: 18000, expected: 83300, counted: 81000 },
    { id: 5, zNum: 38, date: '17/04/2026', opened: '09:30', closed: '21:45', operator: 'Pedro M.', tickets: 15, diners: 38, total: 112300, net: 112300, gross: 112300, discounts: 0, cash: 45000, diff: 0, invitations: 0, invValue: 0, cancellations: 0, initial: 15000, movIn: 0, movOut: 12000, expected: 45000, counted: 45000 },
    { id: 6, zNum: 37, date: '16/04/2026', opened: '10:00', closed: '23:30', operator: 'Carlos R.', tickets: 34, diners: 86, total: 278400, net: 278400, gross: 280000, discounts: 1600, cash: 112000, diff: 0, invitations: 2, invValue: 950, cancellations: 1, initial: 15000, movIn: 8000, movOut: 30000, expected: 112000, counted: 112000 },
  ];

  const filtered = sessions
    .filter(s => filter === 'all' ? true : filter === 'disc' ? s.diff !== 0 : s.diff === 0)
    .filter(s => search === '' ? true : s.operator.toLowerCase().includes(search.toLowerCase()));

  const totalVentas = filtered.reduce((a, s) => a + s.total, 0);
  const totalTickets = filtered.reduce((a, s) => a + s.tickets, 0);
  const totalDiff = filtered.reduce((a, s) => a + s.diff, 0);

  return (
    <div style={{ flex: 1, overflowY: 'auto', padding: 20, background: '#fafafa', display: 'flex', flexDirection: 'column', gap: 12 }}>
      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <div style={{ fontSize: 16, fontWeight: 700, fontFamily: 'DM Sans', color: '#0d0d0d' }}>Histórico de sesiones</div>
        <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
          <input
            placeholder="Buscar operador..."
            value={search}
            onChange={e => setSearch(e.target.value)}
            style={{ borderRadius: 8, border: '1.5px solid #e8e8e8', padding: '7px 12px', fontFamily: 'DM Sans', fontSize: 13, outline: 'none', background: '#fff', width: 180 }}
          />
          <Segment
            options={[{ value: 'all', label: 'Todos' }, { value: 'disc', label: 'Con descuadre' }, { value: 'ok', label: 'Cuadrados' }]}
            value={filter} onChange={setFilter}
          />
        </div>
      </div>

      {/* KPIs resumen */}
      <div style={{ display: 'flex', gap: 10 }}>
        <KpiCard label="Ventas periodo" value={fmt(totalVentas)} color="#1a9e5a" />
        <KpiCard label="Tickets" value={totalTickets} sub={`${filtered.length} sesiones`} mono={false} />
        <KpiCard label="Descuadre acumulado" value={`${totalDiff >= 0 ? '+' : ''}${fmtNum(totalDiff)} €`} color={totalDiff < 0 ? '#ff4d4d' : totalDiff > 0 ? '#1a9e5a' : '#0d0d0d'} />
      </div>

      {/* Lista */}
      {filtered.map(s => (
        <Card key={s.id} onClick={() => onViewZ(s)} padding={16}>
          <div style={{ display: 'grid', gridTemplateColumns: 'auto 1fr auto auto auto', gap: 14, alignItems: 'center' }}>
            {/* Z badge */}
            <div style={{ width: 48, height: 48, borderRadius: 12, background: s.diff !== 0 ? '#ffecec' : '#e8f7ef', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
              <span style={{ fontSize: 10, fontWeight: 700, fontFamily: 'DM Sans', color: s.diff !== 0 ? '#ff4d4d' : '#1a9e5a' }}>Z</span>
              <span style={{ fontSize: 13, fontWeight: 700, fontFamily: "'DM Mono', monospace", color: s.diff !== 0 ? '#ff4d4d' : '#1a9e5a' }}>{s.zNum}</span>
            </div>
            {/* Info */}
            <div>
              <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 2 }}>
                <span style={{ fontFamily: 'DM Sans', fontSize: 14, fontWeight: 600, color: '#0d0d0d' }}>{s.date}</span>
                <span style={{ fontFamily: 'DM Sans', fontSize: 12, color: '#a0a0a0' }}>{s.opened} → {s.closed}</span>
                <Badge text={s.operator} color="#5a5a5a" />
              </div>
              <div style={{ fontSize: 11, color: '#a0a0a0', fontFamily: 'DM Sans' }}>
                {s.tickets} tickets · {s.diners} comensales
                {s.cancellations > 0 && ` · ${s.cancellations} anulación${s.cancellations > 1 ? 'es' : ''}`}
              </div>
            </div>
            {/* Total */}
            <div style={{ textAlign: 'right' }}>
              <div style={{ fontSize: 16, fontFamily: "'DM Mono', monospace", fontWeight: 700 }}>{fmt(s.total)}</div>
              <div style={{ fontSize: 10, color: '#a0a0a0', fontFamily: 'DM Sans' }}>venta neta</div>
            </div>
            {/* Efectivo */}
            <div style={{ textAlign: 'right' }}>
              <div style={{ fontSize: 13, fontFamily: "'DM Mono', monospace", color: '#1a9e5a', fontWeight: 600 }}>{fmt(s.cash)}</div>
              <div style={{ fontSize: 10, color: '#a0a0a0', fontFamily: 'DM Sans' }}>efectivo</div>
            </div>
            {/* Descuadre */}
            <div style={{ textAlign: 'right', minWidth: 90 }}>
              {s.diff === 0
                ? <Badge text="Cuadrado" color="#1a9e5a" />
                : <Badge text={`${s.diff > 0 ? '+' : ''}${fmtNum(s.diff)} €`} color={s.diff < 0 ? '#ff4d4d' : '#1a9e5a'} />
              }
            </div>
          </div>
        </Card>
      ))}
    </div>
  );
}

// ── BARRA / TICKET RÁPIDO ────────────────────────────────
function BarraScreen({ onCobrar }) {
  const [cart, setCart] = React.useState([]);
  const [cat, setCat] = React.useState('Todo');

  const categories = ['Todo', 'Bebidas', 'Tapas', 'Raciones', 'Postres'];
  const catIcons = { Bebidas: '🥤', Tapas: '🥪', Raciones: '🍽', Postres: '🍮' };

  const products = [
    { id: 1, name: 'Café solo', price: 150, cat: 'Bebidas' },
    { id: 2, name: 'Café con leche', price: 180, cat: 'Bebidas' },
    { id: 3, name: 'Cerveza caña', price: 250, cat: 'Bebidas' },
    { id: 4, name: 'Agua 500ml', price: 180, cat: 'Bebidas' },
    { id: 5, name: 'Vino copa', price: 350, cat: 'Bebidas' },
    { id: 6, name: 'Zumo naranja', price: 280, cat: 'Bebidas' },
    { id: 7, name: 'Pan con tomate', price: 320, cat: 'Tapas' },
    { id: 8, name: 'Croquetas (6 u.)', price: 680, cat: 'Tapas' },
    { id: 9, name: 'Jamón ibérico', price: 980, cat: 'Tapas' },
    { id: 10, name: 'Tortilla española', price: 780, cat: 'Tapas' },
    { id: 11, name: 'Patatas bravas', price: 580, cat: 'Tapas' },
    { id: 12, name: 'Calamares romana', price: 1250, cat: 'Raciones' },
    { id: 13, name: 'Pulpo a la gallega', price: 1680, cat: 'Raciones' },
    { id: 14, name: 'Churrasco', price: 1980, cat: 'Raciones' },
    { id: 15, name: 'Tiramisú', price: 680, cat: 'Postres' },
    { id: 16, name: 'Tarta de queso', price: 720, cat: 'Postres' },
    { id: 17, name: 'Flan casero', price: 550, cat: 'Postres' },
  ];

  const filtered = cat === 'Todo' ? products : products.filter(p => p.cat === cat);
  const cartTotal = cart.reduce((a, i) => a + i.price * i.qty, 0);

  const addItem = (product) => setCart(prev => {
    const ex = prev.find(i => i.id === product.id);
    return ex ? prev.map(i => i.id === product.id ? { ...i, qty: i.qty + 1 } : i) : [...prev, { ...product, qty: 1 }];
  });

  const removeItem = (id) => setCart(prev => {
    const ex = prev.find(i => i.id === id);
    return ex?.qty === 1 ? prev.filter(i => i.id !== id) : prev.map(i => i.id === id ? { ...i, qty: i.qty - 1 } : i);
  });

  return (
    <div style={{ flex: 1, display: 'grid', gridTemplateColumns: '1fr 300px', height: '100%', overflow: 'hidden' }}>
      {/* Products */}
      <div style={{ overflowY: 'auto', padding: 14, background: '#fafafa', display: 'flex', flexDirection: 'column', gap: 12 }}>
        <Segment options={categories.map(c => ({ value: c, label: c }))} value={cat} onChange={setCat} />
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(120px, 1fr))', gap: 8 }}>
          {filtered.map(p => {
            const inCart = cart.find(i => i.id === p.id);
            return (
              <div key={p.id} onClick={() => addItem(p)} style={{
                background: '#fff', borderRadius: 12,
                border: `1.5px solid ${inCart ? '#ff4d4d' : '#e8e8e8'}`,
                padding: '12px 8px', textAlign: 'center', cursor: 'pointer',
                transition: 'all 0.12s', position: 'relative',
                boxShadow: inCart ? '0 0 0 3px #ff4d4d22' : 'none',
              }}>
                {inCart && (
                  <div style={{ position: 'absolute', top: 6, right: 6, width: 20, height: 20, borderRadius: '50%', background: '#ff4d4d', color: '#fff', fontSize: 11, fontWeight: 700, fontFamily: 'DM Sans', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    {inCart.qty}
                  </div>
                )}
                <div style={{ fontSize: 26, marginBottom: 6 }}>{catIcons[p.cat] || '◇'}</div>
                <div style={{ fontSize: 12, fontWeight: 600, color: '#0d0d0d', marginBottom: 3, lineHeight: 1.2, fontFamily: 'DM Sans' }}>{p.name}</div>
                <div style={{ fontSize: 13, fontFamily: "'DM Mono', monospace", color: '#ff4d4d', fontWeight: 700 }}>{fmt(p.price)}</div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Cart */}
      <div style={{ background: '#fff', borderLeft: '1px solid #e8e8e8', display: 'flex', flexDirection: 'column', overflow: 'hidden' }}>
        <div style={{ padding: '14px 16px', borderBottom: '1px solid #e8e8e8', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <div style={{ fontSize: 14, fontWeight: 700, fontFamily: 'DM Sans', color: '#0d0d0d' }}>Ticket rápido</div>
          {cart.length > 0 && (
            <button onClick={() => setCart([])} style={{ border: 'none', background: 'none', fontSize: 12, color: '#a0a0a0', cursor: 'pointer', fontFamily: 'DM Sans' }}>Limpiar</button>
          )}
        </div>

        <div style={{ flex: 1, overflowY: 'auto', padding: '8px 16px' }}>
          {cart.length === 0 ? (
            <div style={{ textAlign: 'center', paddingTop: 48, color: '#d3d3d3', fontFamily: 'DM Sans', fontSize: 13 }}>
              <div style={{ fontSize: 32, marginBottom: 8 }}>🛒</div>
              Sin productos
            </div>
          ) : cart.map(item => (
            <div key={item.id} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '9px 0', borderBottom: '1px solid #f4f4f4' }}>
              <div style={{ flex: 1, minWidth: 0 }}>
                <div style={{ fontFamily: 'DM Sans', fontSize: 13, fontWeight: 600, color: '#0d0d0d', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{item.name}</div>
                <div style={{ fontFamily: "'DM Mono', monospace", fontSize: 11, color: '#a0a0a0' }}>{fmt(item.price)} × {item.qty}</div>
              </div>
              <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexShrink: 0 }}>
                <span style={{ fontFamily: "'DM Mono', monospace", fontSize: 13, fontWeight: 700 }}>{fmt(item.price * item.qty)}</span>
                <div style={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                  <button onClick={() => addItem(item)} style={{ width: 22, height: 22, borderRadius: 5, border: '1.5px solid #e8e8e8', background: '#fff', cursor: 'pointer', fontSize: 13, lineHeight: 1, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>+</button>
                  <button onClick={() => removeItem(item.id)} style={{ width: 22, height: 22, borderRadius: 5, border: '1.5px solid #e8e8e8', background: '#fff', cursor: 'pointer', fontSize: 13, lineHeight: 1, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>−</button>
                </div>
              </div>
            </div>
          ))}
        </div>

        <div style={{ padding: '12px 16px', borderTop: '1px solid #e8e8e8' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 }}>
            <span style={{ fontFamily: 'DM Sans', fontSize: 15, fontWeight: 700 }}>Total</span>
            <span style={{ fontFamily: "'DM Mono', monospace", fontSize: 22, fontWeight: 700 }}>{fmt(cartTotal)}</span>
          </div>
          <Btn variant="success" size="lg" block disabled={cart.length === 0} onClick={() => onCobrar(cartTotal, 'Barra')}>
            Cobrar — {fmt(cartTotal)}
          </Btn>
        </div>
      </div>
    </div>
  );
}

Object.assign(window, { PreAperturaScreen, DashboardScreen, HistoricoScreen, BarraScreen });
