// screens-cobro.jsx — Cobro: efectivo, tarjeta, dividir cuenta

const { useState: useStateB, useMemo: useMemoB } = React;

function ScreenCobro({ nav, mesa, total, toast }) {
  const [mode, setMode] = useStateB('select'); // select | efectivo | tarjeta | dividir | done
  const [efectivoCobrado, setEfectivoCobrado] = useStateB(null);
  const [metodo, setMetodo] = useStateB(null);

  if (mode === 'efectivo') {
    return <CobroEfectivo total={total} onBack={() => setMode('select')}
      onDone={(cobrado) => { setEfectivoCobrado(cobrado); setMetodo('Efectivo'); setMode('done'); }}/>;
  }
  if (mode === 'tarjeta') {
    return <CobroTarjeta total={total} onBack={() => setMode('select')}
      onDone={() => { setMetodo('Tarjeta'); setMode('done'); }}/>;
  }
  if (mode === 'dividir') {
    return <CobroDividir mesa={mesa} total={total} onBack={() => setMode('select')}
      onDone={() => { setMetodo('Dividido'); setMode('done'); }}/>;
  }
  if (mode === 'done') {
    return <CobroDone mesa={mesa} total={total} metodo={metodo}
      cambio={efectivoCobrado != null ? efectivoCobrado - total : null}
      onClose={() => nav.replace({ name: 'tabs', tab: 'mesas' })}/>;
  }

  return (
    <>
      <TopBar
        title="Cobrar mesa"
        subtitle={`Mesa ${mesa.id} · ${mesa.comensales || 0} pax`}
        onBack={() => nav.pop()}
      />

      <div className="content" style={{padding: '14px'}}>
        {/* Total card */}
        <div className="card" style={{
          background: 'var(--black)', color: 'var(--white)', borderColor: 'var(--black)',
          padding: '18px 18px',
          marginBottom: 16,
          textAlign: 'center',
        }}>
          <div style={{fontSize: 11, letterSpacing: 1.5, textTransform: 'uppercase', opacity: .55, fontWeight: 600}}>
            Importe total
          </div>
          <div className="mono" style={{fontSize: 44, fontWeight: 300, letterSpacing: '-1.5px', marginTop: 4, lineHeight: 1}}>
            {fmt(total)}
          </div>
          <div style={{fontSize: 11.5, opacity: .55, marginTop: 6}}>
            IVA incluido · {Math.floor(Math.random()*8)+4} líneas
          </div>
        </div>

        <div className="section-title">Método de pago</div>

        <div style={{display: 'grid', gap: 10, marginBottom: 16}}>
          <MetodoBtn
            icon="cash" title="Efectivo"
            sub="Calcula el cambio automáticamente"
            onClick={() => setMode('efectivo')}/>
          <MetodoBtn
            icon="card" title="Tarjeta / Bizum"
            sub="Conectar con TPV físico o móvil"
            onClick={() => setMode('tarjeta')}/>
          <MetodoBtn
            icon="split" title="Dividir cuenta"
            sub="Por comensal, por producto o partes iguales"
            onClick={() => setMode('dividir')}/>
        </div>

        <div className="section-title">Otras opciones</div>
        <div className="card" style={{padding: 0, overflow:'hidden'}}>
          <SheetItem icon="print" label="Cuenta provisional" sub="Imprimir sin cerrar la mesa"
            onClick={() => toast('Imprimiendo cuenta provisional')}/>
          <SheetItem icon="note" label="Aplicar descuento" sub="Requiere autorización"
            onClick={() => toast('Descuento solicitado')}/>
          <SheetItem icon="users" label="Cambiar a invitación" sub="Marcar como invitación"
            onClick={() => toast('Mesa marcada como invitación')}/>
        </div>
      </div>
    </>
  );
}

function MetodoBtn({ icon, title, sub, onClick }) {
  return (
    <button onClick={onClick} className="card tap" style={{
      display: 'flex', alignItems: 'center', gap: 14,
      padding: '14px',
      textAlign: 'left',
    }}>
      <div style={{
        width: 44, height: 44, borderRadius: 12,
        background: 'var(--accent-soft)', color: 'var(--accent)',
        display: 'flex', alignItems: 'center', justifyContent: 'center',
        flexShrink: 0,
      }}>
        <Icon name={icon} size={20}/>
      </div>
      <div style={{flex: 1, minWidth: 0}}>
        <div style={{fontSize: 14, fontWeight: 600, color: 'var(--black)'}}>{title}</div>
        <div style={{fontSize: 11.5, color: 'var(--gray-500)', marginTop: 2}}>{sub}</div>
      </div>
      <Icon name="chevronRight" size={16} color="var(--gray-300)"/>
    </button>
  );
}

// ────── EFECTIVO ──────────────────────────────────────
function CobroEfectivo({ total, onBack, onDone }) {
  const [cobrado, setCobrado] = useStateB('');
  const num = parseFloat(cobrado.replace(',', '.')) || 0;
  const cambio = num - total;
  const sugerencias = [
    Math.ceil(total),
    Math.ceil(total/5)*5,
    Math.ceil(total/10)*10,
    Math.ceil(total/20)*20,
    Math.ceil(total/50)*50,
  ].filter((v,i,arr) => v >= total && arr.indexOf(v) === i).slice(0, 4);

  const onKey = (k) => {
    if (k === 'back') return setCobrado(c => c.slice(0,-1));
    if (k === 'clear') return setCobrado('');
    if (k === ',') {
      if (cobrado.includes(',')) return;
      return setCobrado(c => (c || '0') + ',');
    }
    setCobrado(c => c + k);
  };

  return (
    <>
      <TopBar title="Pago en efectivo" subtitle={`Total ${fmt(total)}`} onBack={onBack}/>

      <div className="content" style={{padding: '14px'}}>
        <div style={{
          background: 'var(--white)', borderRadius: 14,
          padding: '16px 16px 12px',
          border: '1.5px solid var(--gray-200)',
          marginBottom: 12,
        }}>
          <div className="row-between" style={{marginBottom: 8}}>
            <span style={{fontSize: 12, color: 'var(--gray-500)', fontWeight: 500}}>Recibido</span>
            <span className="mono" style={{fontSize: 28, fontWeight: 500, color:'var(--black)'}}>
              {cobrado || '0'},{cobrado.includes(',') ? '' : '00'}<span style={{color:'var(--gray-300)'}}>€</span>
            </span>
          </div>
          <div className="row-between" style={{paddingTop: 12, borderTop: '1px dashed var(--gray-200)'}}>
            <span style={{fontSize: 12, color: 'var(--gray-500)', fontWeight: 500}}>Cambio</span>
            <span className="mono" style={{
              fontSize: 22, fontWeight: 600,
              color: cambio < 0 ? 'var(--accent)' : cambio === 0 ? 'var(--black)' : 'var(--green)',
            }}>
              {cambio < 0 ? '−' : ''}{fmt(Math.abs(cambio))}
            </span>
          </div>
        </div>

        <div className="section-title">Sugerencias</div>
        <div className="row" style={{gap: 6, flexWrap: 'wrap', marginBottom: 14}}>
          {sugerencias.map(v => (
            <button key={v} className="chip chip-outline"
              onClick={() => setCobrado(v.toFixed(2).replace('.', ','))}>
              <span className="mono">{fmt(v)}</span>
            </button>
          ))}
          <button className="chip chip-outline"
            onClick={() => setCobrado(total.toFixed(2).replace('.', ','))}>
            Importe justo
          </button>
        </div>

        {/* Numpad */}
        <div style={{
          display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)',
          gap: 8,
        }}>
          {['1','2','3','4','5','6','7','8','9',',','0','back'].map(k => (
            <button key={k} onClick={() => onKey(k)} style={{
              height: 50, borderRadius: 12,
              border: '1.5px solid var(--gray-200)',
              background: k === 'back' ? 'var(--gray-100)' : 'var(--white)',
              fontFamily: 'var(--mono)', fontSize: 22, fontWeight: 500,
              color: 'var(--black)',
              cursor: 'pointer',
              display: 'flex', alignItems: 'center', justifyContent: 'center',
            }}>
              {k === 'back' ? <Icon name="back" size={16}/> : k}
            </button>
          ))}
        </div>
      </div>

      <div style={{
        padding: '10px 12px',
        background: 'var(--white)',
        borderTop: '1px solid var(--gray-200)',
        flexShrink: 0,
      }}>
        <button className="btn btn-primary btn-block btn-lg"
          disabled={num < total}
          onClick={() => onDone(num)}>
          <Icon name="check" size={16}/> Confirmar cobro
        </button>
      </div>
    </>
  );
}

// ────── TARJETA ───────────────────────────────────────
function CobroTarjeta({ total, onBack, onDone }) {
  const [step, setStep] = useStateB(0); // 0: connecting, 1: insert, 2: processing, 3: done

  React.useEffect(() => {
    if (step === 0) { const t = setTimeout(() => setStep(1), 1100); return () => clearTimeout(t); }
    if (step === 2) { const t = setTimeout(() => setStep(3), 1300); return () => clearTimeout(t); }
    if (step === 3) { const t = setTimeout(() => onDone(), 900); return () => clearTimeout(t); }
  }, [step]);

  const stages = ['Conectando con TPV…', 'Inserta o acerca la tarjeta', 'Procesando pago…', 'Pago aprobado'];
  const subs = [
    'Estableciendo conexión Bluetooth',
    `Importe ${fmt(total)} · TPV Yurest #A47`,
    'No retires la tarjeta',
    `Importe ${fmt(total)} cargado correctamente`,
  ];

  return (
    <>
      <TopBar title="Pago con tarjeta" subtitle={`Total ${fmt(total)}`} onBack={onBack}/>

      <div className="content" style={{
        padding: '40px 24px',
        display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center',
      }}>
        <div style={{
          width: 200, height: 130,
          background: step === 3 ? 'linear-gradient(135deg, #1A9E5A 0%, #0E7A40 100%)' :
                      'linear-gradient(135deg, #2C2C2C 0%, #000 100%)',
          color: 'var(--white)',
          borderRadius: 14,
          padding: 16,
          position: 'relative',
          marginBottom: 24,
          boxShadow: '0 20px 40px rgba(0,0,0,.2)',
          animation: step === 1 ? 'cardPulse 1.5s ease infinite' :
                     step === 2 ? 'cardSpin .8s linear infinite' : 'none',
          display: 'flex', flexDirection: 'column', justifyContent: 'space-between',
        }}>
          <div style={{display:'flex', justifyContent:'space-between', alignItems:'flex-start'}}>
            <Icon name="card" size={22} color="rgba(255,255,255,.5)"/>
            <div style={{
              width: 28, height: 22, borderRadius: 3,
              background: 'linear-gradient(135deg, #d4b87d, #b89759)',
            }}/>
          </div>
          <div style={{fontFamily: 'var(--mono)', fontSize: 13, letterSpacing: 2}}>
            •••• •••• •••• ••42
          </div>
        </div>

        <div style={{fontSize: 18, fontWeight: 600, color: 'var(--black)', textAlign:'center'}}>
          {stages[step]}
        </div>
        <div style={{fontSize: 12.5, color: 'var(--gray-500)', textAlign: 'center', marginTop: 6}}>
          {subs[step]}
        </div>

        {step === 1 && (
          <button className="btn btn-primary" style={{marginTop: 28, minWidth: 200}}
            onClick={() => setStep(2)}>
            Simular tarjeta acercada
          </button>
        )}
        {step === 3 && (
          <div style={{marginTop: 28, fontFamily: 'var(--mono)', fontSize: 11, color: 'var(--gray-500)', textAlign: 'center'}}>
            AUT: 047221 · TVR 0080048000 · AID A0000000031010
          </div>
        )}
      </div>

      <style>{`
        @keyframes cardPulse {
          0%, 100% { transform: scale(1); box-shadow: 0 20px 40px rgba(0,0,0,.2); }
          50% { transform: scale(1.05); box-shadow: 0 25px 50px rgba(0,0,0,.3); }
        }
        @keyframes cardSpin {
          from { transform: rotate(0); } to { transform: rotate(360deg); }
        }
      `}</style>
    </>
  );
}

// ────── DIVIDIR CUENTA ────────────────────────────────
function CobroDividir({ mesa, total, onBack, onDone }) {
  const { PEDIDO_T3 } = window.YDATA;
  const [mode, setMode] = useStateB('iguales'); // iguales | productos | comensal
  const [partes, setPartes] = useStateB(mesa.comensales || 2);
  const [seleccionados, setSeleccionados] = useStateB({});
  const [pagados, setPagados] = useStateB([]); // ids pagados

  const lineas = PEDIDO_T3;
  const totalSel = lineas.reduce((s, l) => s + (seleccionados[l.id] ? l.precio * l.qty : 0), 0);
  const restante = total - pagados.reduce((s, p) => s + p, 0);
  const porParte = total / partes;

  return (
    <>
      <TopBar
        title="Dividir cuenta"
        subtitle={`Mesa ${mesa.id} · Total ${fmt(total)}`}
        onBack={onBack}
      />

      {/* Mode tabs */}
      <div style={{
        display:'flex', gap: 6, padding: '10px 12px',
        background: 'var(--white)', borderBottom: '1px solid var(--gray-200)',
        flexShrink: 0,
      }}>
        <button className={`chip ${mode==='iguales'?'active':''}`} onClick={() => setMode('iguales')}>
          Partes iguales
        </button>
        <button className={`chip ${mode==='productos'?'active':''}`} onClick={() => setMode('productos')}>
          Por productos
        </button>
        <button className={`chip ${mode==='comensal'?'active':''}`} onClick={() => setMode('comensal')}>
          Por comensal
        </button>
      </div>

      <div className="content" style={{padding: '14px'}}>
        {mode === 'iguales' && (
          <>
            <div className="card" style={{padding: '14px', marginBottom: 14, textAlign: 'center'}}>
              <div style={{fontSize: 11, color:'var(--gray-500)', letterSpacing: 1, textTransform:'uppercase', fontWeight: 600}}>
                Dividir entre
              </div>
              <div style={{display:'flex', alignItems:'center', justifyContent:'center', gap: 14, margin: '12px 0'}}>
                <button onClick={() => setPartes(p => Math.max(2, p-1))} style={{
                  width: 40, height: 40, borderRadius: 20,
                  border: '1.5px solid var(--gray-200)', background:'var(--white)',
                  cursor: 'pointer', display:'flex', alignItems:'center', justifyContent:'center',
                }}><Icon name="minus" size={16}/></button>
                <div className="mono" style={{fontSize: 40, fontWeight: 300, color:'var(--black)', minWidth: 60}}>
                  {partes}
                </div>
                <button onClick={() => setPartes(p => Math.min(20, p+1))} style={{
                  width: 40, height: 40, borderRadius: 20,
                  border: 'none', background: 'var(--accent)', color: 'var(--white)',
                  cursor: 'pointer', display:'flex', alignItems:'center', justifyContent:'center',
                }}><Icon name="plus" size={16}/></button>
              </div>
              <div style={{fontSize: 12, color: 'var(--gray-500)'}}>partes</div>
            </div>

            <div className="card" style={{padding: '14px'}}>
              <div className="row-between">
                <span style={{fontSize: 13, color:'var(--gray-700)'}}>Cada parte paga</span>
                <span className="mono" style={{fontSize: 22, fontWeight: 600, color: 'var(--accent)'}}>
                  {fmt(porParte)}
                </span>
              </div>
              <div style={{fontSize: 11, color: 'var(--gray-500)', marginTop: 4}}>
                Total {fmt(total)} ÷ {partes}
              </div>
            </div>

            <div style={{
              marginTop: 14, display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)',
              gap: 6,
            }}>
              {Array.from({length: partes}, (_, i) => {
                const isPagado = i < pagados.length;
                return (
                  <button key={i} onClick={() => {
                    if (!isPagado) setPagados(p => [...p, porParte]);
                  }} className="card" style={{
                    padding: '10px 6px',
                    minHeight: 64,
                    display: 'flex', flexDirection: 'column', alignItems:'center', justifyContent:'center',
                    background: isPagado ? 'var(--green-soft)' : 'var(--white)',
                    borderColor: isPagado ? 'var(--green)' : 'var(--gray-200)',
                    cursor: isPagado ? 'default' : 'pointer',
                  }}>
                    <div style={{fontSize: 10, fontWeight: 600, color: isPagado ? 'var(--green)' : 'var(--gray-500)', letterSpacing: '.5px'}}>
                      P{i+1}
                    </div>
                    <div className="mono" style={{fontSize: 12, fontWeight: 500, marginTop: 4, color: isPagado ? 'var(--green)' : 'var(--black)'}}>
                      {fmt(porParte)}
                    </div>
                    {isPagado && <Icon name="check" size={12} color="var(--green)" strokeWidth={3}/>}
                  </button>
                );
              })}
            </div>
          </>
        )}

        {mode === 'productos' && (
          <>
            <div className="card" style={{padding: 0, overflow:'hidden', marginBottom: 14}}>
              {lineas.map((l, i) => (
                <button key={l.id} onClick={() => setSeleccionados(s => ({...s, [l.id]: !s[l.id]}))} style={{
                  width: '100%', display: 'grid', gridTemplateColumns: '24px 28px 1fr auto',
                  gap: 10, padding: '10px 12px',
                  borderBottom: i === lineas.length-1 ? 'none' : '1px solid var(--gray-100)',
                  background: seleccionados[l.id] ? 'var(--accent-soft)' : 'var(--white)',
                  border: 0, cursor: 'pointer', textAlign: 'left',
                  fontFamily: 'var(--font)',
                }}>
                  <div style={{
                    width: 20, height: 20, borderRadius: 4,
                    border: `1.5px solid ${seleccionados[l.id] ? 'var(--accent)' : 'var(--gray-300)'}`,
                    background: seleccionados[l.id] ? 'var(--accent)' : 'var(--white)',
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                  }}>
                    {seleccionados[l.id] && <Icon name="check" size={12} color="var(--white)" strokeWidth={3}/>}
                  </div>
                  <div className="mono" style={{fontSize: 12, fontWeight: 600, color:'var(--black)'}}>
                    {l.qty}×
                  </div>
                  <div style={{fontSize: 12.5, color:'var(--black)', minWidth: 0}}>
                    {l.producto}
                  </div>
                  <div className="mono" style={{fontSize: 12.5, fontWeight: 500}}>
                    {fmt(l.precio * l.qty)}
                  </div>
                </button>
              ))}
            </div>

            <div className="card" style={{padding: '12px 14px',
              background: 'var(--black)', color: 'var(--white)', borderColor:'var(--black)'}}>
              <div className="row-between">
                <span style={{fontSize: 12, opacity: .7}}>Seleccionado</span>
                <span className="mono" style={{fontSize: 20, fontWeight: 600}}>{fmt(totalSel)}</span>
              </div>
              <div style={{fontSize: 11, opacity: .55, marginTop: 4}}>
                Resto pendiente: {fmt(total - totalSel - pagados.reduce((s,p)=>s+p,0))}
              </div>
            </div>
          </>
        )}

        {mode === 'comensal' && (
          <>
            {[1,2,3,4].map(c => {
              const ls = lineas.filter(l => l.comensal === c);
              if (ls.length === 0) return null;
              const subt = ls.reduce((s,l)=>s+l.precio*l.qty,0);
              return (
                <div key={c} className="card" style={{padding: '12px 14px', marginBottom: 10}}>
                  <div className="row-between" style={{marginBottom: 6}}>
                    <div className="row" style={{gap: 8}}>
                      <div style={{
                        width: 26, height: 26, borderRadius: '50%',
                        background: 'var(--gray-100)', color:'var(--black)',
                        display:'flex', alignItems:'center', justifyContent:'center',
                        fontSize: 12, fontWeight: 700, fontFamily: 'var(--mono)',
                      }}>C{c}</div>
                      <span style={{fontSize: 13, fontWeight: 600}}>Comensal {c}</span>
                      <span style={{fontSize: 11, color: 'var(--gray-500)'}}>· {ls.length} ítems</span>
                    </div>
                    <span className="mono" style={{fontSize: 15, fontWeight: 600}}>{fmt(subt)}</span>
                  </div>
                  <div style={{fontSize: 11.5, color:'var(--gray-500)'}}>
                    {ls.map(l => `${l.qty}× ${l.producto}`).join(' · ')}
                  </div>
                  <button className="btn btn-sm btn-primary" style={{marginTop: 10, width: '100%'}}
                    onClick={() => setPagados(p => [...p, subt])}>
                    Cobrar a este comensal
                  </button>
                </div>
              );
            })}
            {/* compartidos */}
            {(() => {
              const ls = lineas.filter(l => !l.comensal);
              if (ls.length === 0) return null;
              const subt = ls.reduce((s,l)=>s+l.precio*l.qty,0);
              return (
                <div className="card" style={{padding: '12px 14px',
                  background: 'var(--gray-50)', borderStyle: 'dashed'}}>
                  <div className="row-between">
                    <span style={{fontSize: 13, fontWeight: 600}}>Compartido</span>
                    <span className="mono" style={{fontSize: 15, fontWeight: 600}}>{fmt(subt)}</span>
                  </div>
                  <div style={{fontSize: 11, color:'var(--gray-500)', marginTop: 2}}>
                    Repartir entre comensales o cobrar aparte
                  </div>
                </div>
              );
            })()}
          </>
        )}
      </div>

      <div style={{
        padding: '10px 12px',
        background: 'var(--white)',
        borderTop: '1px solid var(--gray-200)',
        flexShrink: 0,
      }}>
        <div className="row-between" style={{padding: '0 4px 8px', fontSize: 12, color: 'var(--gray-500)'}}>
          <span>Restante</span>
          <span className="mono" style={{fontWeight: 600, color: restante > 0 ? 'var(--accent)' : 'var(--green)'}}>
            {fmt(Math.max(0, restante))}
          </span>
        </div>
        <button className="btn btn-primary btn-block btn-lg"
          onClick={() => {
            if (mode === 'productos' && totalSel === 0) return;
            onDone();
          }}>
          {mode === 'productos'
            ? `Cobrar ${fmt(totalSel)} seleccionado`
            : restante <= 0
              ? 'Finalizar cobro'
              : 'Cobrar parte y continuar'}
        </button>
      </div>
    </>
  );
}

// ────── CONFIRMACIÓN DE PAGO ──────────────────────────
function CobroDone({ mesa, total, metodo, cambio, onClose }) {
  return (
    <div className="pda-screen" style={{background:'var(--gray-50)'}}>
      <div style={{
        flex: 1, display:'flex', flexDirection:'column',
        alignItems:'center', justifyContent:'center', padding: '24px',
      }}>
        <div style={{
          width: 88, height: 88, borderRadius: '50%',
          background: 'var(--green-soft)',
          display:'flex', alignItems:'center', justifyContent:'center',
          marginBottom: 18,
          animation: 'pulse-green 1.2s ease',
        }}>
          <Icon name="check" size={44} color="var(--green)" strokeWidth={2.5}/>
        </div>
        <div style={{fontSize: 22, fontWeight: 600, color:'var(--black)', textAlign:'center'}}>
          Cobro completado
        </div>
        <div style={{fontSize: 13, color:'var(--gray-500)', marginTop: 6}}>
          Mesa {mesa.id} cerrada correctamente
        </div>

        <div className="card" style={{
          width: '100%', maxWidth: 320, marginTop: 24,
          padding: '16px',
        }}>
          <div className="row-between" style={{fontSize: 12, color:'var(--gray-500)', marginBottom: 8}}>
            <span>Importe</span>
            <span className="mono" style={{fontSize: 18, fontWeight: 600, color:'var(--black)'}}>{fmt(total)}</span>
          </div>
          <div className="row-between" style={{fontSize: 12, color:'var(--gray-500)', marginBottom: 8}}>
            <span>Método</span>
            <span style={{fontWeight: 500, color:'var(--black)'}}>{metodo}</span>
          </div>
          {cambio != null && cambio > 0 && (
            <div className="row-between" style={{fontSize: 12, color:'var(--gray-500)',
              paddingTop: 8, borderTop: '1px dashed var(--gray-200)'}}>
              <span>Cambio</span>
              <span className="mono" style={{fontSize: 16, fontWeight: 600, color: 'var(--green)'}}>{fmt(cambio)}</span>
            </div>
          )}
          <div style={{
            marginTop: 12, paddingTop: 12, borderTop: '1px solid var(--gray-100)',
            fontFamily:'var(--mono)', fontSize: 10, color:'var(--gray-500)', textAlign:'center',
          }}>
            Ticket #1247 · 27/05 21:42 · MA
          </div>
        </div>
      </div>

      <div style={{
        padding: '12px 14px',
        background:'var(--white)',
        borderTop:'1px solid var(--gray-200)',
        display:'grid', gridTemplateColumns:'1fr 1fr', gap: 8,
        flexShrink: 0,
      }}>
        <button className="btn btn-ghost">
          <Icon name="print" size={14}/> Reimprimir
        </button>
        <button className="btn btn-dark" onClick={onClose}>
          Volver al sala
        </button>
      </div>
    </div>
  );
}

Object.assign(window, {
  ScreenCobro, CobroEfectivo, CobroTarjeta, CobroDividir, CobroDone, MetodoBtn,
});
