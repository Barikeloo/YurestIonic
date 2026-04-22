// modals.jsx — Apertura, Movimiento, Cobrar, SplitBill, CierreWizard, ZReport

// ── APERTURA MODAL ───────────────────────────────────────
function AperturaModal({ show, onClose, onConfirm }) {
  const [operator, setOperator] = React.useState('maria');
  const [amount, setAmount] = React.useState(15000);
  const [showNote, setShowNote] = React.useState(false);
  const [note, setNote] = React.useState('');

  const operators = [
    { id: 'maria', name: 'María García', initials: 'MG' },
    { id: 'carlos', name: 'Carlos Ruiz', initials: 'CR' },
    { id: 'ana', name: 'Ana López', initials: 'AL' },
    { id: 'pedro', name: 'Pedro Moreno', initials: 'PM' },
  ];

  return (
    <Modal show={show} onClose={onClose} title="Abrir caja — Nueva sesión">
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 28 }}>
        {/* Operador */}
        <div>
          <div style={{ fontSize: 12, fontWeight: 600, color: '#7a7a7a', fontFamily: 'DM Sans', marginBottom: 10, textTransform: 'uppercase', letterSpacing: '0.5px' }}>Operador</div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
            {operators.map(op => (
              <div key={op.id} onClick={() => setOperator(op.id)} style={{
                display: 'flex', alignItems: 'center', gap: 12,
                padding: '10px 14px', borderRadius: 10,
                border: `1.5px solid ${operator === op.id ? '#ff4d4d' : '#e8e8e8'}`,
                background: operator === op.id ? '#ffecec' : '#fafafa',
                cursor: 'pointer', transition: 'all 0.12s',
              }}>
                <div style={{
                  width: 36, height: 36, borderRadius: '50%',
                  background: operator === op.id ? '#ff4d4d' : '#d3d3d3',
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  color: '#fff', fontSize: 12, fontWeight: 700, fontFamily: 'DM Sans', flexShrink: 0,
                }}>{op.initials}</div>
                <span style={{ fontFamily: 'DM Sans', fontSize: 14, fontWeight: operator === op.id ? 600 : 400, color: '#0d0d0d' }}>{op.name}</span>
              </div>
            ))}
          </div>
          <div style={{ marginTop: 14 }}>
            <button onClick={() => setShowNote(!showNote)} style={{ background: 'none', border: 'none', color: '#a0a0a0', fontSize: 12, fontFamily: 'DM Sans', cursor: 'pointer', padding: 0 }}>
              {showNote ? '− Ocultar nota' : '+ Añadir nota de apertura'}
            </button>
            {showNote && (
              <textarea value={note} onChange={e => setNote(e.target.value)} placeholder="Nota opcional..." style={{
                marginTop: 6, width: '100%', borderRadius: 8, border: '1.5px solid #e8e8e8',
                padding: '8px 10px', fontFamily: 'DM Sans, sans-serif', fontSize: 13, color: '#0d0d0d',
                resize: 'none', height: 72, boxSizing: 'border-box', outline: 'none',
              }} />
            )}
          </div>
        </div>

        {/* Fondo + NumPad */}
        <div>
          <div style={{ fontSize: 12, fontWeight: 600, color: '#7a7a7a', fontFamily: 'DM Sans', marginBottom: 10, textTransform: 'uppercase', letterSpacing: '0.5px' }}>Fondo inicial</div>
          <AmountDisplay cents={amount} large />
          <NumPad value={amount} onChange={setAmount} />
        </div>
      </div>

      <div style={{ marginTop: 24, display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
        <Btn variant="gray" onClick={onClose}>Cancelar</Btn>
        <Btn variant="success" onClick={() => { onConfirm?.({ operator, amount, note }); onClose(); }}>
          Abrir caja — {fmt(amount)}
        </Btn>
      </div>
    </Modal>
  );
}

// ── MOVIMIENTO MODAL ─────────────────────────────────────
function MovimientoModal({ show, onClose, role }) {
  const [type, setType] = React.useState('in');
  const [reason, setReason] = React.useState('change_refill');
  const [amount, setAmount] = React.useState(0);
  const [desc, setDesc] = React.useState('');

  const reasons = {
    in: [
      { v: 'change_refill', l: 'Reposición cambio' },
      { v: 'tip_declared', l: 'Propina declarada' },
      { v: 'adjustment', l: 'Ajuste' },
      { v: 'other', l: 'Otro' },
    ],
    out: [
      { v: 'sangria', l: 'Sangría al banco' },
      { v: 'supplier_payment', l: 'Pago proveedor' },
      { v: 'tip_declared', l: 'Propina camarero' },
      { v: 'adjustment', l: 'Ajuste' },
      { v: 'other', l: 'Otro' },
    ],
  };

  const needsSupervisor = amount >= 5000;
  const blocked = needsSupervisor && !ROLES[role]?.canMovement50;
  const accentColor = type === 'in' ? '#1a9e5a' : '#ff4d4d';

  return (
    <Modal show={show} onClose={onClose} title="Movimiento de caja">
      <Segment
        options={[{ value: 'in', label: '↑ Entrada' }, { value: 'out', label: '↓ Salida' }]}
        value={type}
        onChange={v => { setType(v); setReason(v === 'in' ? 'change_refill' : 'sangria'); }}
      />

      <div style={{ marginTop: 16 }}>
        <div style={{ fontSize: 11, color: '#a0a0a0', fontFamily: 'DM Sans', marginBottom: 8, textTransform: 'uppercase', letterSpacing: '0.5px' }}>Motivo</div>
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6 }}>
          {reasons[type].map(r => (
            <button key={r.v} onClick={() => setReason(r.v)} style={{
              padding: '6px 14px', borderRadius: 20,
              border: `1.5px solid ${reason === r.v ? accentColor : '#e8e8e8'}`,
              background: reason === r.v ? accentColor + '1a' : '#fff',
              color: reason === r.v ? accentColor : '#5a5a5a',
              fontFamily: 'DM Sans', fontSize: 13, fontWeight: reason === r.v ? 600 : 400, cursor: 'pointer',
            }}>{r.l}</button>
          ))}
        </div>
      </div>

      <div style={{ marginTop: 16, display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20, alignItems: 'start' }}>
        <div>
          <AmountDisplay cents={amount} label="Importe" large color={accentColor} />
          {blocked && (
            <div style={{ background: '#ffecec', borderRadius: 8, padding: '8px 12px', fontSize: 12, color: '#ff4d4d', fontFamily: 'DM Sans', textAlign: 'center', marginTop: 8 }}>
              ⚠ Requiere supervisor (≥ 50,00 €)
            </div>
          )}
        </div>
        <NumPad value={amount} onChange={setAmount} />
      </div>

      <textarea value={desc} onChange={e => setDesc(e.target.value)} placeholder="Descripción libre (opcional)..." style={{
        marginTop: 14, width: '100%', borderRadius: 8, border: '1.5px solid #e8e8e8',
        padding: '8px 10px', fontFamily: 'DM Sans', fontSize: 13,
        resize: 'none', height: 60, boxSizing: 'border-box', outline: 'none',
      }} />

      <div style={{ marginTop: 16, display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
        <Btn variant="gray" onClick={onClose}>Cancelar</Btn>
        <Btn
          variant={type === 'in' ? 'success' : 'danger'}
          color={accentColor}
          disabled={amount === 0 || blocked}
          onClick={onClose}
        >
          {type === 'in' ? 'Registrar entrada' : 'Registrar salida'} — {fmt(amount)}
        </Btn>
      </div>
    </Modal>
  );
}

// ── COBRAR MODAL ─────────────────────────────────────────
function CobrarModal({ show, onClose, total = 3780, tableLabel = 'Mesa 4' }) {
  const [method, setMethod] = React.useState('cash');
  const [cashGiven, setCashGiven] = React.useState(0);
  const [tip, setTip] = React.useState(0);
  const [showTip, setShowTip] = React.useState(false);
  const [showFiscal, setShowFiscal] = React.useState(false);
  const [mixLines, setMixLines] = React.useState([
    { method: 'card', amount: 0 },
    { method: 'cash', amount: 0 },
  ]);

  const change = cashGiven - total;
  const mixedSum = mixLines.reduce((a, l) => a + l.amount, 0);
  const mixedLeft = total - mixedSum;
  const methodLabels = { cash: 'Efectivo', card: 'Tarjeta', bizum: 'Bizum', voucher: 'Vale' };

  const methods = [
    { v: 'cash', l: 'Efectivo', icon: '💵' },
    { v: 'card', l: 'Tarjeta', icon: '💳' },
    { v: 'bizum', l: 'Bizum', icon: '📱' },
    { v: 'mixed', l: 'Mixto', icon: '🔀' },
    { v: 'invitation', l: 'Invitación', icon: '🎁' },
  ];

  const ticketLines = [
    { n: 'Chuletón 400g', p: 2890 },
    { n: 'Ensalada mixta', p: 780 },
    { n: 'Vino Rioja (btll.)', p: 1650 },
    { n: 'Café solo × 2', p: 460 },
  ];

  return (
    <Modal show={show} onClose={onClose} title={`Cobrar — ${tableLabel}`} wide>
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 28 }}>

        {/* ── Columna izquierda ── */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
          {/* Resumen ticket */}
          <Card style={{ background: '#fafafa' }} padding={12}>
            <div style={{ fontSize: 11, color: '#a0a0a0', fontFamily: 'DM Sans', marginBottom: 8, textTransform: 'uppercase', letterSpacing: '0.5px' }}>Resumen</div>
            {ticketLines.map((l, i) => (
              <div key={i} style={{ display: 'flex', justifyContent: 'space-between', padding: '4px 0', borderBottom: '1px solid #f4f4f4' }}>
                <span style={{ fontFamily: 'DM Sans', fontSize: 13, color: '#3d3d3d' }}>{l.n}</span>
                <span style={{ fontFamily: "'DM Mono', monospace", fontSize: 13 }}>{fmt(l.p)}</span>
              </div>
            ))}
            <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 8, fontWeight: 700, fontFamily: 'DM Sans', fontSize: 15 }}>
              <span>Total</span>
              <span style={{ fontFamily: "'DM Mono', monospace", color: '#0d0d0d' }}>{fmt(total)}</span>
            </div>
          </Card>

          {/* Método selector */}
          <div>
            <div style={{ fontSize: 11, color: '#a0a0a0', fontFamily: 'DM Sans', marginBottom: 8, textTransform: 'uppercase', letterSpacing: '0.5px' }}>Método de pago</div>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(5,1fr)', gap: 6 }}>
              {methods.map(m => (
                <button key={m.v} onClick={() => setMethod(m.v)} style={{
                  padding: '10px 4px', borderRadius: 10,
                  border: `1.5px solid ${method === m.v ? '#ff4d4d' : '#e8e8e8'}`,
                  background: method === m.v ? '#ffecec' : '#fff',
                  cursor: 'pointer', textAlign: 'center', transition: 'all 0.12s',
                }}>
                  <div style={{ fontSize: 20 }}>{m.icon}</div>
                  <div style={{ fontSize: 10, fontFamily: 'DM Sans', color: method === m.v ? '#ff4d4d' : '#7a7a7a', marginTop: 3, fontWeight: method === m.v ? 700 : 400 }}>{m.l}</div>
                </button>
              ))}
            </div>
          </div>

          {/* Factura toggle */}
          <div>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <span style={{ fontSize: 13, fontFamily: 'DM Sans', color: '#3d3d3d' }}>Factura completa (NIF)</span>
              <Toggle value={showFiscal} onChange={setShowFiscal} />
            </div>
            {showFiscal && (
              <div style={{ display: 'flex', flexDirection: 'column', gap: 6, marginTop: 10 }}>
                {['NIF / CIF', 'Razón social', 'Dirección fiscal'].map(f => (
                  <input key={f} placeholder={f} style={{
                    borderRadius: 8, border: '1.5px solid #e8e8e8', padding: '8px 10px',
                    fontFamily: 'DM Sans', fontSize: 13, outline: 'none', boxSizing: 'border-box', width: '100%',
                  }} />
                ))}
              </div>
            )}
          </div>
        </div>

        {/* ── Columna derecha ── */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>

          {/* Efectivo */}
          {method === 'cash' && (
            <>
              <AmountDisplay cents={cashGiven} label="Cliente entrega" large />
              <NumPad value={cashGiven} onChange={setCashGiven} />
              {cashGiven > 0 && (
                <Card style={{ background: change >= 0 ? '#e8f7ef' : '#ffecec', border: 'none' }} padding={14}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <span style={{ fontFamily: 'DM Sans', fontSize: 14, color: change >= 0 ? '#1a9e5a' : '#ff4d4d' }}>
                      {change >= 0 ? '✓ Cambio' : '⚠ Falta'}
                    </span>
                    <span style={{ fontFamily: "'DM Mono', monospace", fontSize: 26, fontWeight: 700, color: change >= 0 ? '#1a9e5a' : '#ff4d4d' }}>
                      {fmt(Math.abs(change))}
                    </span>
                  </div>
                </Card>
              )}
              <div>
                <div style={{ fontSize: 11, color: '#a0a0a0', fontFamily: 'DM Sans', marginBottom: 6 }}>Importes rápidos</div>
                <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
                  {[4000, 5000, 6000, 10000, 20000, 50000].map(v => (
                    <button key={v} onClick={() => setCashGiven(v)} style={{
                      padding: '5px 10px', borderRadius: 8, border: `1.5px solid ${cashGiven === v ? '#ff4d4d' : '#e8e8e8'}`,
                      background: cashGiven === v ? '#ffecec' : '#fff',
                      fontFamily: "'DM Mono', monospace", fontSize: 12, cursor: 'pointer',
                      color: cashGiven === v ? '#ff4d4d' : '#3d3d3d',
                    }}>{fmt(v)}</button>
                  ))}
                </div>
              </div>
            </>
          )}

          {/* Tarjeta */}
          {method === 'card' && (
            <div style={{ textAlign: 'center', paddingTop: 12 }}>
              <div style={{ fontSize: 60, marginBottom: 10 }}>💳</div>
              <div style={{ fontSize: 14, fontFamily: 'DM Sans', color: '#5a5a5a', marginBottom: 20 }}>Pase la tarjeta por el datáfono</div>
              <Card style={{ background: '#fafafa' }} padding={16}>
                <div style={{ display: 'flex', justifyContent: 'space-between', fontFamily: 'DM Sans', fontSize: 13, marginBottom: 8 }}>
                  <span style={{ color: '#7a7a7a' }}>Importe a cobrar</span>
                  <span style={{ fontFamily: "'DM Mono', monospace", fontWeight: 700, fontSize: 20 }}>{fmt(total)}</span>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', paddingTop: 8, borderTop: '1px solid #e8e8e8' }}>
                  <span style={{ fontFamily: 'DM Sans', fontSize: 13, color: '#3d3d3d' }}>Añadir propina</span>
                  <Toggle value={showTip} onChange={setShowTip} color="#1a9e5a" />
                </div>
                {showTip && (
                  <div style={{ marginTop: 10, display: 'flex', gap: 6 }}>
                    {[0, 100, 200, 500].map(t => (
                      <button key={t} onClick={() => setTip(t)} style={{
                        flex: 1, padding: '6px 0', borderRadius: 8,
                        border: `1.5px solid ${tip === t ? '#1a9e5a' : '#e8e8e8'}`,
                        background: tip === t ? '#e8f7ef' : '#fff',
                        fontFamily: "'DM Mono', monospace", fontSize: 12, cursor: 'pointer',
                        color: tip === t ? '#1a9e5a' : '#5a5a5a', fontWeight: tip === t ? 700 : 400,
                      }}>{t === 0 ? 'Sin prop.' : fmt(t)}</button>
                    ))}
                  </div>
                )}
                {showTip && tip > 0 && (
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 10, paddingTop: 8, borderTop: '1px solid #e8e8e8', fontFamily: 'DM Sans', fontWeight: 700, fontSize: 15 }}>
                    <span>Total tarjeta</span>
                    <span style={{ fontFamily: "'DM Mono', monospace" }}>{fmt(total + tip)}</span>
                  </div>
                )}
              </Card>
            </div>
          )}

          {/* Bizum */}
          {method === 'bizum' && (
            <div style={{ textAlign: 'center', paddingTop: 12 }}>
              <div style={{ fontSize: 60, marginBottom: 10 }}>📱</div>
              <div style={{ fontSize: 14, fontFamily: 'DM Sans', color: '#5a5a5a', marginBottom: 6 }}>Bizum al número del restaurante</div>
              <div style={{ fontFamily: "'DM Mono', monospace", fontSize: 28, fontWeight: 700, marginBottom: 20, color: '#0d0d0d' }}>+34 612 345 678</div>
              <Card style={{ background: '#fafafa' }} padding={14}>
                <div style={{ display: 'flex', justifyContent: 'space-between', fontFamily: 'DM Sans', fontSize: 13 }}>
                  <span style={{ color: '#7a7a7a' }}>Importe exacto</span>
                  <span style={{ fontFamily: "'DM Mono', monospace", fontWeight: 700, fontSize: 22 }}>{fmt(total)}</span>
                </div>
              </Card>
            </div>
          )}

          {/* Mixto */}
          {method === 'mixed' && (
            <div>
              <div style={{ fontSize: 11, color: '#a0a0a0', fontFamily: 'DM Sans', marginBottom: 8, textTransform: 'uppercase', letterSpacing: '0.5px' }}>Distribución del pago</div>
              {mixLines.map((line, i) => (
                <div key={i} style={{ display: 'grid', gridTemplateColumns: '1fr auto', gap: 8, marginBottom: 8, alignItems: 'center' }}>
                  <select value={line.method} onChange={e => { const n = [...mixLines]; n[i] = { ...line, method: e.target.value }; setMixLines(n); }} style={{ borderRadius: 8, border: '1.5px solid #e8e8e8', padding: '8px 10px', fontFamily: 'DM Sans', fontSize: 13, outline: 'none', color: '#0d0d0d' }}>
                    {['cash', 'card', 'bizum', 'voucher'].map(m => <option key={m} value={m}>{methodLabels[m]}</option>)}
                  </select>
                  <input type="number" placeholder="0,00" value={line.amount > 0 ? (line.amount / 100).toFixed(2) : ''} onChange={e => { const n = [...mixLines]; n[i] = { ...line, amount: Math.round(parseFloat(e.target.value || 0) * 100) }; setMixLines(n); }} style={{ width: 88, borderRadius: 8, border: '1.5px solid #e8e8e8', padding: '8px 10px', fontFamily: "'DM Mono', monospace", fontSize: 13, outline: 'none', textAlign: 'right' }} />
                </div>
              ))}
              <button onClick={() => setMixLines([...mixLines, { method: 'cash', amount: 0 }])} style={{ width: '100%', padding: '8px', border: '1.5px dashed #d3d3d3', borderRadius: 8, background: 'none', fontFamily: 'DM Sans', fontSize: 12, color: '#7a7a7a', cursor: 'pointer', marginBottom: 10 }}>+ Añadir método</button>
              <Card style={{ background: mixedLeft === 0 ? '#e8f7ef' : mixedLeft < 0 ? '#ffecec' : '#fafafa', border: 'none' }} padding={12}>
                <div style={{ display: 'flex', justifyContent: 'space-between', fontFamily: 'DM Sans', fontSize: 13 }}>
                  <span style={{ color: '#7a7a7a' }}>Pendiente</span>
                  <span style={{ fontFamily: "'DM Mono', monospace", fontWeight: 700, color: mixedLeft === 0 ? '#1a9e5a' : mixedLeft < 0 ? '#ff4d4d' : '#0d0d0d' }}>
                    {mixedLeft === 0 ? '✓ Completado' : fmt(mixedLeft)}
                  </span>
                </div>
              </Card>
            </div>
          )}

          {/* Invitación */}
          {method === 'invitation' && (
            <div style={{ textAlign: 'center', paddingTop: 16 }}>
              <div style={{ fontSize: 56, marginBottom: 8 }}>🎁</div>
              <div style={{ fontSize: 15, fontFamily: 'DM Sans', fontWeight: 600, color: '#0d0d0d', marginBottom: 4 }}>Invitación de la casa</div>
              <div style={{ fontSize: 13, fontFamily: 'DM Sans', color: '#a0a0a0', marginBottom: 20 }}>Requiere supervisor. El importe se registra como invitación.</div>
              <Card style={{ background: '#ffecec', border: 'none' }} padding={14}>
                <div style={{ fontFamily: 'DM Sans', fontSize: 13, color: '#ff4d4d' }}>
                  Valor teórico: <strong style={{ fontFamily: "'DM Mono', monospace", fontSize: 18 }}>{fmt(total)}</strong>
                </div>
              </Card>
            </div>
          )}

          <Btn variant="success" size="lg" block onClick={onClose}>
            Confirmar cobro — {fmt(total)}
          </Btn>
        </div>
      </div>
    </Modal>
  );
}

// ── SPLIT BILL MODAL ─────────────────────────────────────
function SplitBillModal({ show, onClose }) {
  const [mode, setMode] = React.useState('equal');
  const [parts, setParts] = React.useState(2);
  const total = 12780;

  const lines = [
    { id: 1, name: 'Chuletón 400g', price: 2890, diner: 1 },
    { id: 2, name: 'Lubina al horno', price: 2450, diner: 2 },
    { id: 3, name: 'Ensalada César', price: 980, diner: 1 },
    { id: 4, name: 'Carpaccio', price: 1250, diner: 2 },
    { id: 5, name: 'Vino Rioja (btll.)', price: 1650, diner: null },
    { id: 6, name: 'Agua mineral × 2', price: 360, diner: null },
    { id: 7, name: 'Postre surtido', price: 1200, diner: null },
    { id: 8, name: 'Cafés × 2 + copa', price: 1000, diner: null },
  ];
  const [assigned, setAssigned] = React.useState(lines);

  const equalPart = Math.floor(total / parts);
  const remainder = total - equalPart * parts;

  const subTotal = (n) => assigned.filter(l => l.diner === n).reduce((s, l) => s + l.price, 0);

  const assignLine = (id, diner) => setAssigned(prev => prev.map(l => l.id === id ? { ...l, diner: l.diner === diner ? null : diner } : l));

  return (
    <Modal show={show} onClose={onClose} title="Dividir cuenta — Mesa 4" wide>
      <Segment
        options={[{ value: 'equal', label: 'Partes iguales' }, { value: 'lines', label: 'Por líneas' }, { value: 'diner', label: 'Por comensal' }]}
        value={mode} onChange={setMode}
      />

      <div style={{ marginTop: 20 }}>
        {/* ── Partes iguales */}
        {mode === 'equal' && (
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 16, marginBottom: 20 }}>
              <span style={{ fontFamily: 'DM Sans', fontSize: 14, color: '#3d3d3d' }}>Número de partes</span>
              <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                <button onClick={() => setParts(Math.max(2, parts - 1))} style={{ width: 36, height: 36, borderRadius: 8, border: '1.5px solid #e8e8e8', background: '#fff', fontSize: 18, cursor: 'pointer', fontWeight: 700 }}>−</button>
                <span style={{ fontFamily: "'DM Mono', monospace", fontSize: 24, fontWeight: 700, width: 36, textAlign: 'center' }}>{parts}</span>
                <button onClick={() => setParts(Math.min(10, parts + 1))} style={{ width: 36, height: 36, borderRadius: 8, border: '1.5px solid #e8e8e8', background: '#fff', fontSize: 18, cursor: 'pointer', fontWeight: 700 }}>+</button>
              </div>
              <div style={{ marginLeft: 8, fontSize: 13, fontFamily: 'DM Sans', color: '#7a7a7a' }}>Total: <strong style={{ fontFamily: "'DM Mono'" }}>{fmt(total)}</strong></div>
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(150px, 1fr))', gap: 10 }}>
              {Array.from({ length: parts }, (_, i) => (
                <Card key={i} style={{ textAlign: 'center', background: '#fafafa' }}>
                  <div style={{ fontSize: 11, color: '#a0a0a0', fontFamily: 'DM Sans', marginBottom: 4 }}>Parte {i + 1}</div>
                  <div style={{ fontSize: 22, fontFamily: "'DM Mono', monospace", fontWeight: 700 }}>{fmt(equalPart + (i === parts - 1 ? remainder : 0))}</div>
                  <Btn size="sm" variant="success" block style={{ marginTop: 10 }}>Cobrar</Btn>
                </Card>
              ))}
            </div>
          </div>
        )}

        {/* ── Por líneas */}
        {mode === 'lines' && (
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
            <div>
              <div style={{ fontSize: 11, color: '#a0a0a0', fontFamily: 'DM Sans', marginBottom: 8, textTransform: 'uppercase', letterSpacing: '0.5px' }}>Líneas — toca para asignar</div>
              {assigned.map(l => (
                <div key={l.id} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '8px 10px', borderRadius: 8, border: '1.5px solid #e8e8e8', marginBottom: 6, background: l.diner ? '#fafafa' : '#fff' }}>
                  <div>
                    <div style={{ fontFamily: 'DM Sans', fontSize: 13, color: '#0d0d0d', fontWeight: l.diner ? 600 : 400 }}>{l.name}</div>
                    <div style={{ fontFamily: "'DM Mono', monospace", fontSize: 11, color: '#a0a0a0' }}>{fmt(l.price)}</div>
                  </div>
                  <div style={{ display: 'flex', gap: 4 }}>
                    {[1, 2, 3].map(n => (
                      <button key={n} onClick={() => assignLine(l.id, n)} style={{
                        width: 28, height: 28, borderRadius: 6,
                        border: `1.5px solid ${l.diner === n ? '#ff4d4d' : '#e8e8e8'}`,
                        background: l.diner === n ? '#ff4d4d' : '#fff',
                        color: l.diner === n ? '#fff' : '#7a7a7a',
                        fontSize: 12, fontWeight: 700, cursor: 'pointer', fontFamily: 'DM Sans',
                      }}>{n}</button>
                    ))}
                  </div>
                </div>
              ))}
            </div>
            <div>
              <div style={{ fontSize: 11, color: '#a0a0a0', fontFamily: 'DM Sans', marginBottom: 8, textTransform: 'uppercase', letterSpacing: '0.5px' }}>Subcuentas</div>
              {[1, 2, 3].map(n => (
                <Card key={n} style={{ marginBottom: 10, border: subTotal(n) > 0 ? '1.5px solid #e8e8e8' : '1.5px dashed #e8e8e8', background: subTotal(n) > 0 ? '#fff' : '#fafafa' }} padding={12}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: subTotal(n) > 0 ? 8 : 0 }}>
                    <span style={{ fontFamily: 'DM Sans', fontSize: 13, fontWeight: 600, color: subTotal(n) > 0 ? '#0d0d0d' : '#a0a0a0' }}>Cuenta {n}</span>
                    <span style={{ fontFamily: "'DM Mono', monospace", fontSize: 18, fontWeight: 700, color: subTotal(n) > 0 ? '#0d0d0d' : '#d3d3d3' }}>{fmt(subTotal(n))}</span>
                  </div>
                  {assigned.filter(l => l.diner === n).map(l => (
                    <div key={l.id} style={{ fontSize: 11, fontFamily: 'DM Sans', color: '#7a7a7a', display: 'flex', justifyContent: 'space-between', borderTop: '1px solid #f4f4f4', padding: '3px 0' }}>
                      <span>{l.name}</span><span style={{ fontFamily: "'DM Mono'" }}>{fmt(l.price)}</span>
                    </div>
                  ))}
                  {subTotal(n) > 0 && <Btn size="sm" variant="success" block style={{ marginTop: 8 }} onClick={e => e.stopPropagation()}>Cobrar cuenta {n}</Btn>}
                </Card>
              ))}
            </div>
          </div>
        )}

        {/* ── Por comensal */}
        {mode === 'diner' && (
          <div>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 12 }}>
              {[1, 2].map(d => (
                <Card key={d} style={{ background: '#fafafa' }}>
                  <div style={{ fontFamily: 'DM Sans', fontSize: 13, fontWeight: 700, marginBottom: 10 }}>Comensal {d}</div>
                  {lines.filter(l => l.diner === d).map(l => (
                    <div key={l.id} style={{ display: 'flex', justifyContent: 'space-between', padding: '4px 0', borderBottom: '1px solid #e8e8e8', fontFamily: 'DM Sans', fontSize: 12 }}>
                      <span style={{ color: '#3d3d3d' }}>{l.name}</span>
                      <span style={{ fontFamily: "'DM Mono'" }}>{fmt(l.price)}</span>
                    </div>
                  ))}
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 8, fontFamily: 'DM Sans', fontWeight: 700, fontSize: 14 }}>
                    <span>Subtotal</span>
                    <span style={{ fontFamily: "'DM Mono'" }}>{fmt(lines.filter(l => l.diner === d).reduce((s, l) => s + l.price, 0))}</span>
                  </div>
                  <Btn size="sm" variant="success" block style={{ marginTop: 10 }}>Cobrar comensal {d}</Btn>
                </Card>
              ))}
            </div>
            <Card style={{ background: '#f4f4f4', border: 'none' }} padding={12}>
              <div style={{ fontSize: 12, fontFamily: 'DM Sans', color: '#7a7a7a', marginBottom: 6 }}>Gastos comunes — pendiente asignar</div>
              <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6 }}>
                {lines.filter(l => !l.diner).map(l => (
                  <Badge key={l.id} text={`${l.name} · ${fmt(l.price)}`} color="#5a5a5a" />
                ))}
              </div>
            </Card>
          </div>
        )}
      </div>
    </Modal>
  );
}

// ── CIERRE WIZARD (pantalla completa) ────────────────────
function CierreWizard({ show, onClose, onComplete }) {
  const [step, setStep] = React.useState(1);
  const [counted, setCounted] = React.useState(0);
  const [reason, setReason] = React.useState('');

  const expected = 72050;
  const discrepancy = counted - expected;
  const hasDisc = counted > 0 && Math.abs(discrepancy) > 0;

  const zData = {
    tickets: 23, diners: 61, gross: 184750, discounts: 1200, net: 183550,
    iva10base: 45800, iva10: 4580, iva21base: 88800, iva21: 18650,
    cash: 72050, card: 98700, bizum: 14000, invitation: 1800,
    invitations: 2, invValue: 1250, cancellations: 1, cnotes: 0,
    tipsCard: 1800, tipsCash: 0,
    initial: 15000, movIn: 5000, movOut: 20000,
  };

  const reasons = ['Error en el conteo', 'Cambio no registrado', 'Propina no declarada', 'Ticket anulado', 'Otro'];
  const steps = [{ n: 1, l: 'Contar' }, { n: 2, l: 'Justificar' }, { n: 3, l: 'Revisar Z' }];
  const totalSteps = hasDisc ? 3 : 3;

  const goNext = () => {
    if (step === 1) return setStep(hasDisc ? 2 : 3);
    if (step === 2) return setStep(3);
    onComplete?.(); onClose();
  };

  if (!show) return null;
  return (
    <div style={{ position: 'fixed', inset: 0, background: '#fff', zIndex: 400, display: 'flex', flexDirection: 'column' }}>
      {/* Header */}
      <div style={{ padding: '14px 32px', borderBottom: '1px solid #e8e8e8', display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexShrink: 0 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 14 }}>
          <button onClick={onClose} style={{ border: 'none', background: 'none', fontSize: 20, color: '#a0a0a0', cursor: 'pointer', lineHeight: 1 }}>←</button>
          <div>
            <div style={{ fontSize: 17, fontWeight: 700, fontFamily: 'DM Sans', color: '#0d0d0d' }}>Cerrar caja</div>
            <div style={{ fontSize: 11, color: '#a0a0a0', fontFamily: 'DM Sans' }}>La Tasca de Miguel · TPV Sala · María G.</div>
          </div>
        </div>

        {/* Step indicators */}
        <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
          {steps.map((s, i) => (
            <div key={s.n} style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                <div style={{
                  width: 28, height: 28, borderRadius: '50%',
                  background: step > s.n ? '#1a9e5a' : step === s.n ? '#ff4d4d' : '#e8e8e8',
                  color: step >= s.n ? '#fff' : '#a0a0a0',
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  fontSize: 12, fontWeight: 700, fontFamily: 'DM Sans',
                }}>{step > s.n ? '✓' : s.n}</div>
                <span style={{ fontSize: 12, fontFamily: 'DM Sans', color: step >= s.n ? '#0d0d0d' : '#a0a0a0', fontWeight: step === s.n ? 700 : 400 }}>{s.l}</span>
              </div>
              {i < steps.length - 1 && <div style={{ width: 28, height: 2, background: step > s.n ? '#1a9e5a' : '#e8e8e8', borderRadius: 1, margin: '0 2px' }} />}
            </div>
          ))}
        </div>
        <div style={{ width: 120 }} />
      </div>

      {/* Content */}
      <div style={{ flex: 1, overflowY: 'auto', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 32 }}>
        {step === 1 && (
          <div style={{ textAlign: 'center', maxWidth: 480, width: '100%' }}>
            <div style={{ fontSize: 12, color: '#a0a0a0', fontFamily: 'DM Sans', marginBottom: 6 }}>Arqueo ciego — No ves el teórico hasta confirmar</div>
            <div style={{ fontSize: 28, fontWeight: 700, fontFamily: 'DM Sans', color: '#0d0d0d', marginBottom: 24, textWrap: 'balance' }}>¿Cuánto efectivo hay en la caja?</div>
            <AmountDisplay cents={counted} large color={counted > 0 ? '#0d0d0d' : '#d3d3d3'} />
            <div style={{ maxWidth: 320, margin: '0 auto', marginTop: 12 }}>
              <NumPad value={counted} onChange={setCounted} />
            </div>
          </div>
        )}

        {step === 2 && (
          <div style={{ maxWidth: 560, width: '100%' }}>
            <div style={{ fontSize: 26, fontWeight: 700, fontFamily: 'DM Sans', color: '#0d0d0d', marginBottom: 6, textAlign: 'center' }}>Descuadre detectado</div>
            <div style={{ textAlign: 'center', marginBottom: 24 }}>
              <Card style={{ display: 'inline-block', background: discrepancy < 0 ? '#ffecec' : '#e8f7ef', border: 'none', borderRadius: 16 }} padding={20}>
                <div style={{ display: 'flex', gap: 28, alignItems: 'center' }}>
                  {[['TEÓRICO', expected, '#0d0d0d'], ['CONTADO', counted, '#0d0d0d'], ['DIFERENCIA', discrepancy, discrepancy < 0 ? '#ff4d4d' : '#1a9e5a']].map(([k, v, c]) => (
                    <div key={k} style={{ textAlign: 'center' }}>
                      <div style={{ fontSize: 10, color: '#a0a0a0', fontFamily: 'DM Sans', marginBottom: 4, letterSpacing: '0.5px' }}>{k}</div>
                      <div style={{ fontSize: 22, fontFamily: "'DM Mono', monospace", fontWeight: 700, color: c }}>
                        {k === 'DIFERENCIA' && v > 0 ? '+' : ''}{fmtNum(Math.abs(v))} €
                      </div>
                    </div>
                  ))}
                </div>
              </Card>
            </div>
            <div style={{ fontSize: 13, fontFamily: 'DM Sans', color: '#3d3d3d', marginBottom: 10 }}>Motivo del descuadre</div>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8, marginBottom: 14 }}>
              {reasons.map(r => (
                <button key={r} onClick={() => setReason(r)} style={{
                  padding: '8px 16px', borderRadius: 20,
                  border: `1.5px solid ${reason === r ? '#ff4d4d' : '#e8e8e8'}`,
                  background: reason === r ? '#ffecec' : '#fff',
                  color: reason === r ? '#ff4d4d' : '#5a5a5a',
                  fontFamily: 'DM Sans', fontSize: 13, fontWeight: reason === r ? 600 : 400, cursor: 'pointer',
                }}>{r}</button>
              ))}
            </div>
            {reason === 'Otro' && (
              <textarea placeholder="Describe el motivo con detalle..." style={{ width: '100%', borderRadius: 8, border: '1.5px solid #e8e8e8', padding: '10px 12px', fontFamily: 'DM Sans', fontSize: 13, resize: 'none', height: 80, boxSizing: 'border-box', outline: 'none' }} />
            )}
          </div>
        )}

        {step === 3 && (
          <div style={{ maxWidth: 760, width: '100%' }}>
            <div style={{ fontSize: 22, fontWeight: 700, fontFamily: 'DM Sans', color: '#0d0d0d', marginBottom: 4, textAlign: 'center' }}>Informe Z — Revisa antes de confirmar</div>
            <div style={{ fontSize: 12, color: '#a0a0a0', fontFamily: 'DM Sans', textAlign: 'center', marginBottom: 20 }}>Una vez confirmado, el Z es inmutable</div>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 12, marginBottom: 16 }}>
              {/* Ventas */}
              <Card padding={14}>
                <div style={{ fontSize: 10, color: '#a0a0a0', fontFamily: 'DM Sans', textTransform: 'uppercase', letterSpacing: '0.5px', marginBottom: 8, fontWeight: 700 }}>Ventas del turno</div>
                {[['Tickets', zData.tickets], ['Comensales', zData.diners], ['Venta bruta', fmt(zData.gross)], ['Descuentos', `− ${fmt(zData.discounts)}`], ['Invitaciones', `${zData.invitations} · ${fmt(zData.invValue)}`], ['Anulaciones', zData.cancellations], ['Venta neta', fmt(zData.net)]].map(([k, v]) => (
                  <div key={k} style={{ display: 'flex', justifyContent: 'space-between', padding: '3px 0', borderBottom: '1px solid #f4f4f4', fontFamily: 'DM Sans', fontSize: 12 }}>
                    <span style={{ color: '#7a7a7a' }}>{k}</span>
                    <span style={{ fontFamily: "'DM Mono', monospace", fontWeight: k === 'Venta neta' ? 700 : 400 }}>{v}</span>
                  </div>
                ))}
              </Card>
              {/* Por método */}
              <Card padding={14}>
                <div style={{ fontSize: 10, color: '#a0a0a0', fontFamily: 'DM Sans', textTransform: 'uppercase', letterSpacing: '0.5px', marginBottom: 8, fontWeight: 700 }}>Por método</div>
                {[['Efectivo', fmt(zData.cash), '#1a9e5a'], ['Tarjeta', fmt(zData.card), '#0d0d0d'], ['Bizum', fmt(zData.bizum), '#0d0d0d'], ['Invitación', fmt(zData.invitation), '#ff4d4d'], ['Propinas tarjeta', fmt(zData.tipsCard), '#1a9e5a']].map(([k, v, c]) => (
                  <div key={k} style={{ display: 'flex', justifyContent: 'space-between', padding: '3px 0', borderBottom: '1px solid #f4f4f4', fontFamily: 'DM Sans', fontSize: 12 }}>
                    <span style={{ color: '#7a7a7a' }}>{k}</span>
                    <span style={{ fontFamily: "'DM Mono', monospace", color: c }}>{v}</span>
                  </div>
                ))}
              </Card>
              {/* Arqueo */}
              <Card padding={14}>
                <div style={{ fontSize: 10, color: '#a0a0a0', fontFamily: 'DM Sans', textTransform: 'uppercase', letterSpacing: '0.5px', marginBottom: 8, fontWeight: 700 }}>Arqueo efectivo</div>
                {[['Fondo inicial', fmt(zData.initial)], ['Entradas', fmt(zData.movIn)], ['Salidas', `− ${fmt(zData.movOut)}`], ['Teórico', fmt(expected)], ['Contado', fmt(counted)], ['Diferencia', `${discrepancy >= 0 ? '+' : ''}${fmtNum(discrepancy)} €`]].map(([k, v]) => (
                  <div key={k} style={{ display: 'flex', justifyContent: 'space-between', padding: '3px 0', borderBottom: '1px solid #f4f4f4', fontFamily: 'DM Sans', fontSize: 12 }}>
                    <span style={{ color: '#7a7a7a' }}>{k}</span>
                    <span style={{ fontFamily: "'DM Mono', monospace", fontWeight: k === 'Diferencia' ? 700 : 400, color: k === 'Diferencia' ? (discrepancy < 0 ? '#ff4d4d' : '#1a9e5a') : '#0d0d0d' }}>{v}</span>
                  </div>
                ))}
                {reason && <div style={{ marginTop: 6, fontSize: 11, color: '#7a7a7a', fontFamily: 'DM Sans' }}>Motivo: {reason}</div>}
              </Card>
            </div>
            <div style={{ display: 'flex', gap: 8, justifyContent: 'center' }}>
              <Btn variant="outline" color="#5a5a5a">🖨 Imprimir Z</Btn>
              <Btn variant="outline" color="#5a5a5a">📧 Enviar por email</Btn>
            </div>
          </div>
        )}
      </div>

      {/* Footer */}
      <div style={{ padding: '14px 32px', borderTop: '1px solid #e8e8e8', display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexShrink: 0 }}>
        <Btn variant="gray" onClick={step === 1 ? onClose : () => setStep(step - 1)}>
          {step === 1 ? 'Cancelar' : '← Anterior'}
        </Btn>
        <div style={{ fontSize: 12, color: '#a0a0a0', fontFamily: 'DM Sans' }}>Paso {step} de {totalSteps}</div>
        <Btn
          variant={step === 3 ? 'success' : 'fill'}
          disabled={step === 1 && counted === 0}
          onClick={goNext}
        >
          {step === 3 ? '✓ Confirmar Z y cerrar caja' : 'Siguiente →'}
        </Btn>
      </div>
    </div>
  );
}

// ── Z REPORT MODAL ───────────────────────────────────────
function ZReportModal({ show, onClose, session }) {
  if (!show || !session) return null;
  const s = session;
  return (
    <Modal show={show} onClose={onClose} title={`Informe Z #${s.zNum} — ${s.date}`} wide>
      <Card style={{ background: '#fafafa', marginBottom: 14 }} padding={14}>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: 10 }}>
          {[['Restaurante', 'La Tasca de Miguel'], ['Device', 'TPV Sala'], ['Apertura', s.opened], ['Cierre', s.closed]].map(([k, v]) => (
            <div key={k}>
              <div style={{ fontSize: 10, color: '#a0a0a0', fontFamily: 'DM Sans', marginBottom: 2 }}>{k}</div>
              <div style={{ fontSize: 13, fontFamily: 'DM Sans', fontWeight: 600, color: '#0d0d0d' }}>{v}</div>
            </div>
          ))}
        </div>
      </Card>
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, marginBottom: 12 }}>
        <Card padding={14}>
          <div style={{ fontSize: 11, fontWeight: 700, fontFamily: 'DM Sans', color: '#5a5a5a', marginBottom: 8, textTransform: 'uppercase', letterSpacing: '0.5px' }}>Ventas</div>
          {[['Tickets', s.tickets], ['Comensales', s.diners], ['Venta bruta', fmt(s.gross)], ['Descuentos', `− ${fmt(s.discounts)}`], ['Invitaciones', `${s.invitations} (${fmt(s.invValue)})`], ['Anulaciones', s.cancellations], ['Venta neta', fmt(s.net)]].map(([k, v]) => (
            <div key={k} style={{ display: 'flex', justifyContent: 'space-between', padding: '4px 0', borderBottom: '1px solid #f4f4f4', fontFamily: 'DM Sans', fontSize: 13 }}>
              <span style={{ color: '#7a7a7a' }}>{k}</span>
              <span style={{ fontFamily: "'DM Mono', monospace", fontWeight: k === 'Venta neta' ? 700 : 400 }}>{v}</span>
            </div>
          ))}
        </Card>
        <Card padding={14}>
          <div style={{ fontSize: 11, fontWeight: 700, fontFamily: 'DM Sans', color: '#5a5a5a', marginBottom: 8, textTransform: 'uppercase', letterSpacing: '0.5px' }}>Arqueo</div>
          {[['Fondo inicial', fmt(s.initial)], ['Entradas', fmt(s.movIn)], ['Salidas', `− ${fmt(s.movOut)}`], ['Teórico efectivo', fmt(s.expected)], ['Contado real', fmt(s.counted)], [`Diferencia`, `${s.diff >= 0 ? '+' : ''}${fmtNum(s.diff)} €`]].map(([k, v]) => (
            <div key={k} style={{ display: 'flex', justifyContent: 'space-between', padding: '4px 0', borderBottom: '1px solid #f4f4f4', fontFamily: 'DM Sans', fontSize: 13 }}>
              <span style={{ color: '#7a7a7a' }}>{k}</span>
              <span style={{ fontFamily: "'DM Mono', monospace", color: k === 'Diferencia' ? (s.diff < 0 ? '#ff4d4d' : '#1a9e5a') : '#0d0d0d', fontWeight: k === 'Diferencia' ? 700 : 400 }}>{v}</span>
            </div>
          ))}
          {s.diffReason && <div style={{ marginTop: 8, fontSize: 11, color: '#7a7a7a', fontFamily: 'DM Sans' }}>Motivo: {s.diffReason}</div>}
          <div style={{ marginTop: 10, padding: '6px 10px', borderRadius: 8, background: s.diff === 0 ? '#e8f7ef' : '#ffecec', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <span style={{ fontFamily: 'DM Sans', fontSize: 12, color: s.diff === 0 ? '#1a9e5a' : '#ff4d4d', fontWeight: 700 }}>
              {s.diff === 0 ? '✓ Caja cuadrada' : `⚠ ${s.diff > 0 ? 'Sobrante' : 'Faltante'}`}
            </span>
            <Badge text={`${s.diff >= 0 ? '+' : ''}${fmtNum(s.diff)} €`} color={s.diff >= 0 ? '#1a9e5a' : '#ff4d4d'} />
          </div>
        </Card>
      </div>
      <div style={{ display: 'flex', gap: 8, justifyContent: 'flex-end' }}>
        <Btn variant="outline" color="#5a5a5a">🖨 Imprimir</Btn>
        <Btn variant="outline" color="#5a5a5a">📧 Email</Btn>
      </div>
    </Modal>
  );
}

Object.assign(window, { AperturaModal, MovimientoModal, CobrarModal, SplitBillModal, CierreWizard, ZReportModal });
