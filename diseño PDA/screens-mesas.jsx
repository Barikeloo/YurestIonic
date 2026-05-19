// screens-mesas.jsx — Login + Mesas + Detalle mesa + merge mode + transferir cuenta
// Adapted from YurestIonic real frontend

const { useState: useStateM, useEffect: useEffectM, useMemo: useMemoM, useRef: useRefM } = React;

// ════════════════════════════════════════════════════════
// LOGIN — PIN del operario
// ════════════════════════════════════════════════════════
function ScreenLogin({ nav }) {
  const { CURRENT_USER, RESTAURANTE } = window.YDATA;
  const [pin, setPin] = useStateM('');
  const [err, setErr] = useStateM(false);
  const onKey = (k) => {
    setErr(false);
    if (k === 'back') return setPin(p => p.slice(0, -1));
    if (k === 'C') return setPin('');
    if (pin.length >= 4) return;
    const next = pin + k;
    setPin(next);
    if (next.length === 4) {
      setTimeout(() => {
        if (next === CURRENT_USER.pin) nav.replace({ name: 'tabs', tab: 'mesas' });
        else { setErr(true); setTimeout(() => setPin(''), 350); }
      }, 150);
    }
  };
  const dots = [0,1,2,3].map(i => (
    <div key={i} style={{
      width: 14, height: 14, borderRadius: '50%',
      background: i < pin.length ? (err ? 'var(--accent)' : 'var(--black)') : 'transparent',
      border: `1.5px solid ${err ? 'var(--accent)' : 'var(--gray-300)'}`,
      transition: 'all .15s',
    }}/>
  ));
  return (
    <div className="pda-screen" style={{background:'var(--white)'}}>
      {/* Brand header */}
      <div style={{
        padding: '36px 24px 26px', textAlign: 'center',
        background: 'var(--black)', color: 'var(--white)', flexShrink: 0,
      }}>
        <div style={{
          width: 50, height: 50, borderRadius: 14,
          background: 'var(--accent)',
          margin: '0 auto 14px',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          boxShadow: '0 8px 24px rgba(255,77,77,.4)',
        }}>
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2.4" strokeLinecap="round">
            <path d="M3 11l8-7 8 7v9a2 2 0 01-2 2h-4v-7H9v7H5a2 2 0 01-2-2v-9z"/>
          </svg>
        </div>
        <div style={{fontSize: 10.5, fontWeight: 600, letterSpacing: 3,
          textTransform: 'uppercase', color: 'var(--accent-light)', marginBottom: 8}}>
          YUREST · PDA
        </div>
        <div style={{fontSize: 20, fontWeight: 300, letterSpacing: '-.4px', lineHeight: 1.2}}>
          {RESTAURANTE.name.split(' ')[0]}{' '}
          <strong style={{fontWeight: 600}}>{RESTAURANTE.name.split(' ').slice(1).join(' ')}</strong>
        </div>
        <div style={{fontSize: 11, color: 'rgba(255,255,255,.5)', marginTop: 4}}>
          {window.YDATA.DEVICE.name} · Turno cena
        </div>
      </div>

      <div style={{
        flex: 1, display: 'flex', flexDirection: 'column',
        alignItems: 'center', justifyContent: 'center',
        padding: '24px 24px 12px',
      }}>
        <UserAvatar name={CURRENT_USER.name} initials={CURRENT_USER.initials} size={56}/>
        <div style={{fontSize: 15, fontWeight: 600, color: 'var(--black)', marginTop: 10}}>
          {CURRENT_USER.name}
        </div>
        <div style={{fontSize: 11, color: 'var(--gray-500)', marginBottom: 20}}>
          {CURRENT_USER.role === 'admin' ? 'Administrador' :
           CURRENT_USER.role === 'supervisor' ? 'Supervisor/a' : 'Operador/a'}
          {' · Sala'}
        </div>

        <div style={{display: 'flex', gap: 14, marginBottom: 6,
          animation: err ? 'shake .35s' : 'none'}}>{dots}</div>
        <div style={{fontSize: 11, color: err ? 'var(--accent)' : 'var(--gray-500)',
          minHeight: 14, marginBottom: 14}}>
          {err ? 'PIN incorrecto' : `Introduce tu PIN (${CURRENT_USER.pin})`}
        </div>

        <div style={{width: 232}}>
          <div style={{display:'grid', gridTemplateColumns:'repeat(3,1fr)', gap: 8}}>
            {['1','2','3','4','5','6','7','8','9','C','0','back'].map(k => (
              <button key={k} onClick={() => onKey(k)} style={{
                height: 52, borderRadius: 14,
                border: '1.5px solid var(--gray-200)',
                background: k === 'C' ? 'var(--gray-100)' : 'var(--white)',
                fontFamily: 'var(--mono)', fontSize: 21, fontWeight: 500,
                color: 'var(--black)', cursor: 'pointer',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
              }}>
                {k === 'back' ? <Icon name="back" size={17}/> :
                 k === 'C' ? <span style={{fontSize: 12, color:'var(--gray-500)'}}>BORRAR</span> :
                 k}
              </button>
            ))}
          </div>
        </div>

        <button style={{
          marginTop: 14,
          display: 'inline-flex', alignItems: 'center', gap: 6,
          background: 'transparent', border: 'none',
          color: 'var(--gray-500)', fontSize: 12, fontWeight: 500,
          cursor: 'pointer',
        }} onClick={() => nav.push({ name: 'developer-login' })}>
          <Icon name="settings" size={14}/>
          Modo developer
        </button>
      </div>
    </div>
  );
}

// ════════════════════════════════════════════════════════
// MESAS — listado por zona + side-sheet con detalle
// ════════════════════════════════════════════════════════
function ScreenMesas({ nav, toast }) {
  const { ZONES, TABLES, OrderStatus, RESTAURANTE } = window.YDATA;
  const [zone, setZone] = useStateM('z-terraza');
  const [selectedId, setSelectedId] = useStateM(null);
  const [mergeMode, setMergeMode] = useStateM(false);
  const [mergeSelected, setMergeSelected] = useStateM([]);
  const [menu, setMenu] = useStateM(null); // tableMenuTable
  const [openModal, setOpenModal] = useStateM(null); // mesa to open
  const [closeAccountModal, setCloseAccountModal] = useStateM(null);
  const [editDinersModal, setEditDinersModal] = useStateM(null);
  const [transferModal, setTransferModal] = useStateM(null);
  const [pinModal, setPinModal] = useStateM(null); // { action, payload }

  const filtered = TABLES.filter(t => t.zone_id === zone);
  // Group merged tables
  const merged = useMemoM(() => {
    const m = {};
    TABLES.forEach(t => {
      if (t.merged_table_group_id) {
        if (!m[t.merged_table_group_id]) m[t.merged_table_group_id] = [];
        m[t.merged_table_group_id].push(t);
      }
    });
    return m;
  }, []);

  const stats = {
    libres: filtered.filter(t => !t.occupied).length,
    ocupadas: filtered.filter(t => t.occupied && t.status === OrderStatus.OPEN).length,
    cobrar: filtered.filter(t => t.status === OrderStatus.TO_CHARGE).length,
    total: filtered.length,
  };

  const selectedTable = selectedId ? TABLES.find(t => t.id === selectedId) : null;

  const toggleMergeSelection = (id) => {
    setMergeSelected(s => s.includes(id) ? s.filter(x => x !== id) : [...s, id]);
  };

  const confirmMerge = () => {
    if (mergeSelected.length < 2) return;
    toast(`Mesas ${mergeSelected.map(id => TABLES.find(t=>t.id===id)?.name).join(' + ')} fusionadas`, 'success');
    setMergeMode(false);
    setMergeSelected([]);
  };

  // PIN-protected actions
  const requirePin = (action, payload) => {
    setPinModal({ action, payload });
  };
  const onAuthenticated = () => {
    const { action, payload } = pinModal;
    setPinModal(null);
    if (action === 'open-mesa') {
      setOpenModal(payload);
    } else if (action === 'close-account') {
      setCloseAccountModal(payload);
    } else if (action === 'cobrar') {
      nav.push({ name: 'cobrar', mesa: payload, total: payload.remaining_total });
    }
  };

  return (
    <>
      <div className="tb">
        <div style={{
          width: 32, height: 32, borderRadius: 9,
          background: 'var(--black)', color: 'var(--white)',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          fontSize: 10, fontWeight: 700, fontFamily: 'var(--mono)',
        }}>RV</div>
        <div className="tb-title">
          <span className="tb-title-main">{RESTAURANTE.name}</span>
          <span className="tb-title-sub">Lun 27 May · 21:42 · Turno cena</span>
        </div>
        <button className={`tb-action ${mergeMode ? 'solid' : ''}`}
          onClick={() => { setMergeMode(m => !m); setMergeSelected([]); }}
          title="Juntar mesas">
          <Icon name="merge" size={16}/>
        </button>
      </div>

      {/* Stats strip */}
      <div style={{
        display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 6,
        padding: '8px 12px',
        background: 'var(--white)',
        borderBottom: '1px solid var(--gray-200)',
        flexShrink: 0,
      }}>
        <StatPill label="Libres"   value={stats.libres}   dotClass="status-libre"/>
        <StatPill label="Ocupadas" value={stats.ocupadas} dotClass="status-ocupada"/>
        <StatPill label="A cobrar" value={stats.cobrar}   dotClass="status-cobrar"/>
        <StatPill label="Total"    value={stats.total}/>
      </div>

      {/* Zone tabs */}
      <div style={{
        display: 'flex', gap: 6, padding: '10px 12px',
        background: 'var(--white)',
        borderBottom: '1px solid var(--gray-200)',
        overflowX: 'auto', flexShrink: 0,
      }}>
        {ZONES.map(z => (
          <button key={z.id}
            className={`chip ${zone === z.id ? 'active' : ''}`}
            onClick={() => setZone(z.id)}
          >{z.name}</button>
        ))}
      </div>

      {/* Merge mode bar */}
      {mergeMode && (
        <div className="merge-bar">
          <Icon name="merge" size={18}/>
          <div style={{flex: 1}}>
            <div style={{fontSize: 12.5, fontWeight: 600}}>Modo juntar mesas</div>
            <div style={{fontSize: 10.5, opacity: .8}}>
              {mergeSelected.length === 0 ? 'Toca las mesas que quieras unir' :
               `${mergeSelected.length} mesa${mergeSelected.length===1?'':'s'} seleccionada${mergeSelected.length===1?'':'s'}`}
            </div>
          </div>
          <button onClick={() => { setMergeMode(false); setMergeSelected([]); }}
            style={{background:'rgba(255,255,255,.18)', color:'var(--white)',
              border: 'none', padding:'6px 10px', borderRadius: 8,
              fontSize: 11, fontWeight: 600, cursor:'pointer'}}>
            Cancelar
          </button>
          <button disabled={mergeSelected.length < 2} onClick={confirmMerge}
            style={{background:'var(--white)', color:'var(--accent)',
              border: 'none', padding:'6px 12px', borderRadius: 8,
              fontSize: 11, fontWeight: 700, cursor: 'pointer',
              opacity: mergeSelected.length < 2 ? .5 : 1}}>
            Fusionar
          </button>
        </div>
      )}

      <div className="content" style={{padding: '12px'}}>
        <div style={{display:'grid', gridTemplateColumns:'repeat(3,1fr)', gap: 8}}>
          {filtered.map(t => {
            // Skip non-primary merged
            if (t.merged_table_group_id && merged[t.merged_table_group_id][0].id !== t.id) return null;
            const isMerged = !!t.merged_table_group_id;
            const groupTables = isMerged ? merged[t.merged_table_group_id] : null;
            return (
              <MesaCard key={t.id} t={t} groupTables={groupTables}
                selected={selectedId === t.id}
                mergeMode={mergeMode}
                mergeSelected={mergeSelected.includes(t.id)}
                onClick={() => {
                  if (mergeMode) toggleMergeSelection(t.id);
                  else setSelectedId(t.id);
                }}
              />
            );
          })}
        </div>
      </div>

      {/* Bottom detail sheet (Selected mesa) */}
      {selectedTable && !mergeMode && (
        <Sheet open={true} onClose={() => setSelectedId(null)}
          full={selectedTable.occupied}
          title={`${selectedTable.name} · ${ZONES.find(z=>z.id===selectedTable.zone_id)?.name}`}
          subtitle={selectedTable.occupied
            ? `${selectedTable.diners} comensales · abierta ${selectedTable.opened_at} · ${selectedTable.camarero}`
            : selectedTable.reserva ? `Reservada · ${selectedTable.reserva}` : `Capacidad ${selectedTable.capacidad}`}
        >
          <MesaDetail mesa={selectedTable}
            onNuevaComanda={() => { setSelectedId(null); nav.push({ name: 'comanda', mesa: selectedTable }); }}
            onAbrirMesa={() => requirePin('open-mesa', selectedTable)}
            onCerrarCuenta={() => requirePin('close-account', selectedTable)}
            onCobrar={() => requirePin('cobrar', selectedTable)}
            onMenuAction={(action) => {
              if (action === 'editar-comensales') setEditDinersModal(selectedTable);
              else if (action === 'separar') toast('Mesa separada', 'success');
              else if (action === 'juntar') { setMergeMode(true); setMergeSelected([selectedTable.id]); setSelectedId(null); }
              else if (action === 'traspasar') setTransferModal(selectedTable);
            }}
            toast={toast}
          />
        </Sheet>
      )}

      <PinAuthModal open={!!pinModal}
        title={
          pinModal?.action === 'open-mesa' ? 'Abrir mesa' :
          pinModal?.action === 'close-account' ? 'Cerrar cuenta' :
          pinModal?.action === 'cobrar' ? 'Cobrar' : 'Verificar'
        }
        subtitle="Verifica tu PIN para continuar"
        onClose={() => setPinModal(null)}
        onAuthenticated={onAuthenticated}
      />

      <Sheet open={!!openModal} onClose={() => setOpenModal(null)}
        title="Abrir mesa"
        subtitle={openModal ? `${openModal.name} · ${ZONES.find(z=>z.id===openModal.zone_id)?.name}` : ''}>
        {openModal && <AbrirMesaForm mesa={openModal}
          onConfirm={(diners) => {
            setOpenModal(null);
            toast(`Mesa ${openModal.name} abierta · ${diners} comensales`, 'success');
            nav.push({ name: 'comanda', mesa: {...openModal, diners} });
          }}
        />}
      </Sheet>

      <Sheet open={!!closeAccountModal} onClose={() => setCloseAccountModal(null)}
        title="Cerrar cuenta"
        subtitle={closeAccountModal ? `${closeAccountModal.name} · Total ${formatCentsEur(closeAccountModal.remaining_total)}` : ''}>
        <CerrarCuentaForm mesa={closeAccountModal}
          onConfirm={(closer) => {
            setCloseAccountModal(null);
            toast(`Cuenta cerrada por ${closer.name.split(' ')[0]} · pendiente de cobro`, 'success');
          }}
        />
      </Sheet>

      <Sheet open={!!editDinersModal} onClose={() => setEditDinersModal(null)}
        title="Editar comensales"
        subtitle={editDinersModal?.name}>
        {editDinersModal && <EditDinersForm mesa={editDinersModal}
          onConfirm={(n) => {
            setEditDinersModal(null);
            toast(`Comensales actualizados a ${n}`, 'success');
          }}
        />}
      </Sheet>

      <Sheet open={!!transferModal} onClose={() => setTransferModal(null)}
        title="Traspasar cuenta"
        subtitle={`Desde ${transferModal?.name}`}>
        {transferModal && <TransferForm fromMesa={transferModal}
          onConfirm={(toMesa) => {
            setTransferModal(null);
            toast(`Cuenta traspasada de ${transferModal.name} → ${toMesa.name}`, 'success');
          }}
        />}
      </Sheet>
    </>
  );
}

function StatPill({ label, value, dotClass }) {
  return (
    <div style={{background:'var(--gray-50)', borderRadius: 10, padding: '7px 9px'}}>
      <div style={{display:'flex', alignItems:'center', gap: 5}}>
        {dotClass && <span className={`status-dot ${dotClass}`}/>}
        <span style={{fontSize: 17, fontWeight: 600, fontFamily: 'var(--mono)', color: 'var(--black)'}}>{value}</span>
      </div>
      <div style={{fontSize: 10, color: 'var(--gray-500)', fontWeight: 500, marginTop: 1}}>{label}</div>
    </div>
  );
}

function MesaCard({ t, groupTables, selected, mergeMode, mergeSelected, onClick }) {
  const { OrderStatus } = window.YDATA;
  const isOccupied = t.occupied && t.status === OrderStatus.OPEN;
  const isCobrar = t.status === OrderStatus.TO_CHARGE;
  const isReservada = !t.occupied && t.reserva;
  const isMerged = !!groupTables && groupTables.length > 1;
  const classes = [
    'mesa-card',
    isOccupied && 'occupied',
    isCobrar && 'cobrar',
    isReservada && 'reservada',
    selected && 'selected',
    mergeMode && 'merge-mode',
    mergeSelected && 'merge-selected',
    isMerged && 'merged-group',
  ].filter(Boolean).join(' ');
  const totalCents = isMerged ? groupTables.reduce((s, x) => s + (x.remaining_total || 0), 0) : t.remaining_total;
  const totalDiners = isMerged ? groupTables.reduce((s, x) => s + (x.diners || 0), 0) : t.diners;
  const name = isMerged ? groupTables.map(x => x.name).join('+') : t.name;
  return (
    <button onClick={onClick} className={classes}>
      <div style={{
        fontFamily: 'var(--mono)', fontSize: name.length > 4 ? 14 : 20,
        fontWeight: 600, lineHeight: 1,
      }}>{name}</div>
      {!t.occupied && !isReservada && (
        <div style={{fontSize: 10, marginTop: 5, opacity: .65, fontWeight: 500}}>
          {t.capacidad} pax
        </div>
      )}
      {(isOccupied || isCobrar) && (
        <>
          <div style={{fontSize: 11, fontFamily:'var(--mono)', marginTop: 4, opacity: .7}}>
            {formatCentsEur(totalCents)}
          </div>
          <div style={{fontSize: 9, opacity: .55, marginTop: 1}}>
            {totalDiners} pax · {t.opened_at}
          </div>
        </>
      )}
      {isCobrar && (
        <div style={{
          position:'absolute', top: 4, right: 4,
          fontSize: 8, fontWeight: 700, letterSpacing: '.5px',
          background: 'rgba(255,255,255,.22)', color: 'var(--white)',
          padding: '2px 5px', borderRadius: 4,
        }}>COBRAR</div>
      )}
      {isReservada && (
        <>
          <div style={{fontSize: 9, color: 'var(--amber)', fontWeight: 700, letterSpacing: '.5px', marginTop: 4}}>
            RESERVADA
          </div>
          <div style={{fontSize: 9, color: 'var(--gray-600)', marginTop: 1, textAlign:'center'}}>
            {t.reserva?.split('·')[0]?.trim()}
          </div>
        </>
      )}
      {(isOccupied || isCobrar) && t.camarero && (
        <div style={{
          position:'absolute', top: 4, left: 4,
          fontSize: 8, fontWeight: 700,
          background: 'rgba(255,255,255,.22)',
          width: 16, height: 16, borderRadius: 8,
          display: 'flex', alignItems: 'center', justifyContent: 'center',
        }}>{t.camarero}</div>
      )}
      {mergeMode && mergeSelected && (
        <div style={{
          position: 'absolute', top: 4, right: 4,
          width: 18, height: 18, borderRadius: 9,
          background: 'var(--white)', color: 'var(--accent)',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
        }}>
          <Icon name="check" size={12} strokeWidth={3}/>
        </div>
      )}
      {isMerged && (
        <div style={{
          position: 'absolute', bottom: 4, left: '50%', transform: 'translateX(-50%)',
          background: 'rgba(255,255,255,.18)',
          padding: '1px 6px', borderRadius: 6,
          fontSize: 8.5, fontWeight: 700, letterSpacing: '.4px',
          display: 'flex', alignItems: 'center', gap: 3,
        }}>
          <Icon name="merge" size={8}/> FUSIONADA
        </div>
      )}
    </button>
  );
}

// ════════════════════════════════════════════════════════
// Detail sheet inner — pedido + acciones
// ════════════════════════════════════════════════════════
function MesaDetail({ mesa, onNuevaComanda, onAbrirMesa, onCerrarCuenta, onCobrar, onMenuAction, toast }) {
  const { OrderStatus, PEDIDO_T3_LINES, ZONES } = window.YDATA;
  const lines = mesa.occupied ? PEDIDO_T3_LINES : [];
  const subtotal = lines.reduce((s, l) => s + (l.price + (l.modifiers||[]).reduce((a,m)=>a+m.price,0)) * l.quantity, 0);
  const ivaTotal = subtotal * 0.10; // mock simplificado
  const total = subtotal;

  const [menuOpen, setMenuOpen] = useStateM(false);
  const [lineDetail, setLineDetail] = useStateM(null);

  if (!mesa.occupied) {
    return (
      <div style={{padding: '0 0 8px'}}>
        <div className="card" style={{padding: 14, marginBottom: 14, textAlign: 'center'}}>
          <div style={{fontSize: 36, marginBottom: 6}}>🍽️</div>
          <div style={{fontSize: 14, fontWeight: 600, color: 'var(--black)'}}>Mesa libre</div>
          <div style={{fontSize: 12, color: 'var(--gray-500)', marginTop: 3}}>
            Capacidad {mesa.capacidad} {mesa.capacidad === 1 ? 'comensal' : 'comensales'}
          </div>
          {mesa.reserva && (
            <div style={{marginTop: 10, padding:'8px 10px', background:'var(--amber-soft)', borderRadius: 10}}>
              <div style={{fontSize: 10, fontWeight: 700, color:'var(--amber)', letterSpacing: '.5px'}}>RESERVADA</div>
              <div style={{fontSize: 11.5, color:'var(--gray-700)', marginTop: 2}}>{mesa.reserva}</div>
            </div>
          )}
        </div>
        <button className="btn btn-primary btn-block btn-lg" onClick={onAbrirMesa}>
          <Icon name="lock" size={14}/> Abrir mesa
          <span style={{opacity: .55, fontSize: 10, marginLeft: 4}}>requiere PIN</span>
        </button>
      </div>
    );
  }

  return (
    <div style={{margin: '-4px 0 8px'}}>
      {/* Big total card */}
      <div className="card card-dark" style={{
        padding: '14px 16px', marginBottom: 12, position: 'relative',
      }}>
        <div className="row-between" style={{marginBottom: 6}}>
          <span style={{fontSize: 10, opacity: .55, letterSpacing: 1.2, textTransform: 'uppercase', fontWeight: 600}}>
            Total mesa
          </span>
          <span style={{fontSize: 10, opacity: .55, fontFamily: 'var(--mono)'}}>
            #{mesa.order_id?.replace('o-', '') || '----'}
          </span>
        </div>
        <div className="mono" style={{fontSize: 30, fontWeight: 300, letterSpacing: '-1.2px'}}>
          {formatCentsEur(total)}
        </div>
        <div className="row" style={{marginTop: 8, gap: 12, fontSize: 11, opacity: .7}}>
          <span style={{display:'inline-flex', alignItems:'center', gap:4}}>
            <Icon name="note" size={11}/> {lines.length} líneas
          </span>
          <span style={{display:'inline-flex', alignItems:'center', gap:4}}>
            <Icon name="users" size={11}/> {mesa.diners} comensales
          </span>
          <span style={{display:'inline-flex', alignItems:'center', gap:4}}>
            <Icon name="clock" size={11}/> {mesa.opened_at}
          </span>
        </div>
        <button onClick={() => setMenuOpen(true)} style={{
          position: 'absolute', top: 8, right: 8,
          width: 30, height: 30, borderRadius: 8,
          background: 'rgba(255,255,255,.1)', border: 'none',
          color: 'var(--white)', cursor: 'pointer',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
        }}>
          <Icon name="more" size={16}/>
        </button>
      </div>

      {/* Status banner */}
      {mesa.status === OrderStatus.TO_CHARGE && (
        <div style={{
          background: 'var(--blue-soft)', borderRadius: 10,
          padding: '8px 12px', marginBottom: 10,
          display: 'flex', alignItems: 'center', gap: 8,
          fontSize: 12, color: 'var(--blue)', fontWeight: 600,
        }}>
          <Icon name="cash" size={14}/>
          Cuenta cerrada — pendiente de cobro
        </div>
      )}

      {/* Action buttons */}
      {mesa.status === OrderStatus.TO_CHARGE ? (
        <button className="btn btn-primary btn-block btn-lg" onClick={onCobrar} style={{marginBottom: 10}}>
          <Icon name="cash" size={16}/> Cobrar {formatCentsEur(total)}
          <span style={{opacity: .55, fontSize: 10, marginLeft: 4}}>requiere PIN</span>
        </button>
      ) : (
        <>
          <button className="btn btn-primary btn-block btn-lg" onClick={onNuevaComanda} style={{marginBottom: 8}}>
            <Icon name="plus" size={16}/> Nueva comanda
          </button>
          <div style={{display:'grid', gridTemplateColumns:'1fr 1fr', gap: 8, marginBottom: 12}}>
            <button className="btn btn-ghost" onClick={onCerrarCuenta}>
              <Icon name="cash" size={13}/> Cerrar cuenta
            </button>
            <button className="btn btn-ghost" onClick={() => toast('Ticket impreso')}>
              <Icon name="print" size={13}/> Imprimir
            </button>
          </div>
        </>
      )}

      {/* Líneas */}
      <div className="section-title">Líneas del pedido</div>
      <div className="card" style={{padding: 0, overflow: 'hidden', marginBottom: 12}}>
        {lines.map((l, i) => (
          <LineRow key={l.id} l={l}
            last={i === lines.length - 1}
            onClick={() => setLineDetail(l)}
          />
        ))}
      </div>

      {/* Totales */}
      <div className="card" style={{padding: '12px 14px'}}>
        <div className="row-between" style={{fontSize: 12, color: 'var(--gray-500)', marginBottom: 4}}>
          <span>Subtotal sin IVA</span>
          <span className="mono">{formatCentsEur(subtotal * 0.9)}</span>
        </div>
        <div className="row-between" style={{fontSize: 12, color: 'var(--gray-500)', marginBottom: 8}}>
          <span>Impuestos</span>
          <span className="mono">{formatCentsEur(subtotal * 0.1)}</span>
        </div>
        <div className="row-between" style={{fontSize: 15, fontWeight: 600,
          paddingTop: 8, borderTop: '1px solid var(--gray-200)'}}>
          <span>Total</span>
          <span className="mono">{formatCentsEur(total)}</span>
        </div>
      </div>

      {/* Menu dropdown */}
      <Sheet open={menuOpen} onClose={() => setMenuOpen(false)}
        title={`Opciones de ${mesa.name}`} subtitle="Acciones de mesa">
        <SheetItem icon="users" label="Editar comensales" sub={`Actual: ${mesa.diners}`}
          onClick={() => { setMenuOpen(false); onMenuAction('editar-comensales'); }}/>
        {mesa.merged_table_group_id && (
          <SheetItem icon="unmerge" label="Separar mesa" sub="Romper la fusión"
            onClick={() => { setMenuOpen(false); onMenuAction('separar'); }}/>
        )}
        <SheetItem icon="merge" label="Juntar con otra mesa" sub="Unificar cuentas"
          onClick={() => { setMenuOpen(false); onMenuAction('juntar'); }}/>
        <SheetItem icon="transfer" label="Traspasar cuenta" sub="Mover el pedido a otra mesa"
          onClick={() => { setMenuOpen(false); onMenuAction('traspasar'); }}/>
        <SheetItem icon="note" label="Imprimir cuenta provisional"
          onClick={() => { setMenuOpen(false); toast('Imprimiendo cuenta provisional…'); }}/>
        <SheetItem icon="trash" label="Cancelar pedido completo" sub="Requiere supervisor" danger
          onClick={() => { setMenuOpen(false); toast('Solicitando autorización supervisor'); }}/>
      </Sheet>

      {/* Line detail modal */}
      <Sheet open={!!lineDetail} onClose={() => setLineDetail(null)}
        title={lineDetail?.product_name}
        subtitle={lineDetail ? `${lineDetail.quantity}× · ${formatCentsEur(lineDetail.price * lineDetail.quantity)}` : ''}>
        {lineDetail && <LineDetailContent line={lineDetail}/>}
      </Sheet>
    </div>
  );
}

function LineRow({ l, last, onClick }) {
  const hasModifiers = (l.modifiers || []).length > 0;
  const statusTag = l.status === 'servido' ? {tone:'green', t:'Servido'}
                  : l.status === 'enviado' ? {tone:'blue', t:'En cocina'}
                  : {tone:'amber', t:'Pendiente'};
  return (
    <button onClick={onClick} style={{
      width: '100%',
      display: 'grid', gridTemplateColumns: '22px 1fr auto',
      gap: 10, padding: '11px 14px',
      borderBottom: last ? 'none' : '1px solid var(--gray-100)',
      background: 'var(--white)', border: 0,
      cursor: 'pointer', textAlign: 'left',
      fontFamily: 'var(--font)',
    }}>
      <div className="mono" style={{
        fontSize: 13, fontWeight: 600, color: 'var(--black)',
        textAlign: 'right', paddingTop: 1,
      }}>{l.quantity}</div>
      <div style={{minWidth: 0}}>
        <div style={{fontSize: 13, fontWeight: 500, color: 'var(--black)', lineHeight: 1.25}}>
          {l.product_name}
        </div>
        <div className="row" style={{gap: 5, marginTop: 3, flexWrap:'wrap'}}>
          <Tag tone={statusTag.tone}>{statusTag.t}</Tag>
          {l.variant_name && <Tag tone="gray">{l.variant_name}</Tag>}
          {l.punto && <Tag tone="red">{l.punto}</Tag>}
          {hasModifiers && (
            <span style={{fontSize: 10.5, color: 'var(--gray-500)'}}>
              +{l.modifiers.length} {l.modifiers.length === 1 ? 'extra' : 'extras'}
            </span>
          )}
        </div>
        {l.nota && (
          <div style={{fontSize: 10.5, color: 'var(--gray-500)', marginTop: 2, fontStyle: 'italic'}}>
            "{l.nota}"
          </div>
        )}
      </div>
      <div style={{textAlign: 'right'}}>
        <div className="mono" style={{fontSize: 13, fontWeight: 500, color: 'var(--black)', whiteSpace: 'nowrap'}}>
          {formatCentsEur((l.price + (l.modifiers||[]).reduce((a,m)=>a+m.price,0)) * l.quantity)}
        </div>
      </div>
    </button>
  );
}

function LineDetailContent({ line }) {
  const accompaniments = (line.modifiers || []).filter(m => m.kind === 'accompaniment');
  const extras = (line.modifiers || []).filter(m => m.kind === 'extra');
  const modPrice = (line.modifiers || []).reduce((s, m) => s + m.price, 0);
  return (
    <>
      <div className="row" style={{gap: 10, marginBottom: 14, alignItems: 'center'}}>
        <div className="mono" style={{
          background: 'var(--gray-100)', borderRadius: 10,
          padding: '6px 10px', fontWeight: 700, fontSize: 16,
          color: 'var(--black)',
        }}>{line.quantity}×</div>
        <div style={{flex: 1}}>
          <div style={{fontSize: 15, fontWeight: 600, color: 'var(--black)'}}>{line.product_name}</div>
          {line.variant_name && (
            <div style={{fontSize: 11.5, color: 'var(--gray-500)', marginTop: 2}}>{line.variant_name}</div>
          )}
        </div>
      </div>

      {accompaniments.length > 0 && (
        <div style={{marginBottom: 12}}>
          <div className="section-title">Acompañamientos</div>
          {accompaniments.map(m => (
            <ModRow key={m.id} m={m}/>
          ))}
        </div>
      )}

      {extras.length > 0 && (
        <div style={{marginBottom: 12}}>
          <div className="section-title">Extras</div>
          {extras.map(m => (
            <ModRow key={m.id} m={m}/>
          ))}
        </div>
      )}

      {line.punto && (
        <div style={{marginBottom: 12}}>
          <div className="section-title">Punto de cocción</div>
          <Tag tone="red" size="md">{line.punto}</Tag>
        </div>
      )}

      <div className="card" style={{padding: 12, background: 'var(--gray-50)', border: 'none'}}>
        <div className="row-between" style={{fontSize: 12, color: 'var(--gray-500)', marginBottom: 4}}>
          <span>Precio unitario</span>
          <span className="mono">{formatCentsEur(line.price)}</span>
        </div>
        {modPrice > 0 && (
          <div className="row-between" style={{fontSize: 12, color: 'var(--gray-500)', marginBottom: 4}}>
            <span>Modificadores</span>
            <span className="mono">+{formatCentsEur(modPrice)}</span>
          </div>
        )}
        <div className="row-between" style={{fontSize: 14, fontWeight: 600,
          paddingTop: 6, borderTop: '1px solid var(--gray-200)', marginTop: 4}}>
          <span>Total</span>
          <span className="mono">{formatCentsEur((line.price + modPrice) * line.quantity)}</span>
        </div>
      </div>
    </>
  );
}

function ModRow({ m }) {
  return (
    <div style={{
      display: 'flex', justifyContent: 'space-between', padding: '6px 0',
      borderBottom: '1px solid var(--gray-100)',
    }}>
      <span style={{fontSize: 12.5, color: 'var(--black)'}}>{m.name}</span>
      <span className="mono" style={{
        fontSize: 12, fontWeight: 600,
        color: m.price === 0 ? 'var(--gray-400)' : 'var(--accent)',
      }}>
        {m.price === 0 ? 'Gratis' : '+' + formatCentsEur(m.price)}
      </span>
    </div>
  );
}

// ════════════════════════════════════════════════════════
// Abrir mesa form (con stepper grande + chips)
// ════════════════════════════════════════════════════════
function AbrirMesaForm({ mesa, onConfirm }) {
  const [diners, setDiners] = useStateM(2);
  return (
    <div>
      <div className="section-title" style={{margin: '0 0 8px'}}>Número de comensales</div>
      <DinersStepperLarge value={diners} onChange={setDiners} min={1} max={20}/>
      <div className="diners-quick">
        {[1,2,3,4,5,6,7,8].map(n => (
          <button key={n}
            className={`qchip ${diners === n ? 'active' : ''}`}
            onClick={() => setDiners(n)}>{n}</button>
        ))}
      </div>
      <button className="btn btn-primary btn-block btn-lg"
        style={{marginTop: 16}} onClick={() => onConfirm(diners)}>
        Abrir mesa con {diners} {diners === 1 ? 'comensal' : 'comensales'}
      </button>
    </div>
  );
}

function CerrarCuentaForm({ mesa, onConfirm }) {
  const { USERS_QUICK } = window.YDATA;
  const [closer, setCloser] = useStateM(USERS_QUICK[0]);
  return (
    <div>
      <div style={{
        background: 'var(--blue-soft)', borderRadius: 10,
        padding: '10px 12px', marginBottom: 14,
        fontSize: 11.5, color: 'var(--blue)', lineHeight: 1.4,
        display: 'flex', alignItems: 'flex-start', gap: 8,
      }}>
        <Icon name="info" size={14} color="var(--blue)" strokeWidth={2.5}/>
        Se marcará la cuenta como pendiente de cobro. Selecciona quién la cobra.
      </div>
      <div className="section-title" style={{margin:'0 0 8px'}}>¿Quién cobra?</div>
      <div className="user-chip-grid">
        {USERS_QUICK.map(u => (
          <button key={u.user_uuid}
            className={`user-chip ${closer.user_uuid === u.user_uuid ? 'selected' : ''}`}
            onClick={() => setCloser(u)}>
            <UserAvatar name={u.name} initials={u.initials} size={36}/>
            <span className="user-chip-name">{u.name.split(' ')[0]}</span>
          </button>
        ))}
      </div>
      <button className="btn btn-primary btn-block btn-lg"
        style={{marginTop: 16}} onClick={() => onConfirm(closer)}>
        Confirmar cierre
      </button>
    </div>
  );
}

function EditDinersForm({ mesa, onConfirm }) {
  const [v, setV] = useStateM(mesa.diners || 2);
  const paidCount = 0;
  return (
    <div>
      <div style={{
        background: 'var(--amber-soft)', borderRadius: 10,
        padding: '10px 12px', marginBottom: 14,
        fontSize: 11.5, color: 'var(--amber)', lineHeight: 1.4,
        display: 'flex', alignItems: 'flex-start', gap: 8,
      }}>
        <Icon name="warning" size={14} color="var(--amber)" strokeWidth={2.5}/>
        {paidCount > 0 ?
          `No puedes reducir por debajo de ${paidCount} (ya pagados).` :
          'No hay pagos parciales; puedes ajustar libremente.'}
      </div>
      <div className="section-title" style={{margin:'0 0 8px'}}>Número de comensales</div>
      <DinersStepperLarge value={v} onChange={setV} min={Math.max(1, paidCount)} max={20}/>
      <div className="diners-quick">
        {[1,2,3,4,5,6,7,8].map(n => (
          <button key={n}
            className={`qchip ${v === n ? 'active' : ''}`}
            disabled={n < paidCount}
            onClick={() => setV(n)}>{n}</button>
        ))}
      </div>
      <button className="btn btn-primary btn-block btn-lg"
        style={{marginTop: 16}} onClick={() => onConfirm(v)}>
        Guardar
      </button>
    </div>
  );
}

function TransferForm({ fromMesa, onConfirm }) {
  const { TABLES, ZONES } = window.YDATA;
  const [zone, setZone] = useStateM(fromMesa.zone_id);
  const [sel, setSel] = useStateM(null);
  const libres = TABLES.filter(t => t.zone_id === zone && !t.occupied && t.id !== fromMesa.id);
  return (
    <div>
      <div style={{
        background: 'var(--blue-soft)', borderRadius: 10,
        padding: '10px 12px', marginBottom: 14,
        fontSize: 11.5, color: 'var(--blue)', lineHeight: 1.4,
        display:'flex', gap: 8, alignItems: 'flex-start',
      }}>
        <Icon name="transfer" size={14} color="var(--blue)" strokeWidth={2.5}/>
        Mueve la cuenta de {fromMesa.name} a una mesa libre. Las líneas y pagos se conservan.
      </div>
      <div style={{display:'flex', gap: 6, marginBottom: 10, overflowX:'auto'}}>
        {ZONES.map(z => (
          <button key={z.id}
            className={`chip ${zone === z.id ? 'active' : ''}`}
            onClick={() => setZone(z.id)}>{z.name}</button>
        ))}
      </div>
      <div style={{display:'grid', gridTemplateColumns:'repeat(3,1fr)', gap: 8}}>
        {libres.map(t => (
          <button key={t.id} className="mesa-card" onClick={() => setSel(t.id)}
            style={{
              borderColor: sel === t.id ? 'var(--accent)' : undefined,
              borderWidth: sel === t.id ? '2px' : undefined,
              boxShadow: sel === t.id ? '0 0 0 3px var(--accent-soft)' : undefined,
              minHeight: 70,
            }}>
            <div className="mono" style={{fontSize: 18, fontWeight: 600}}>{t.name}</div>
            <div style={{fontSize: 10, marginTop: 4, color:'var(--gray-500)'}}>{t.capacidad} pax</div>
          </button>
        ))}
      </div>
      {libres.length === 0 && (
        <div style={{textAlign:'center', padding: '24px 8px', color:'var(--gray-400)', fontSize:13}}>
          Sin mesas libres en esta zona
        </div>
      )}
      <button className="btn btn-primary btn-block btn-lg"
        style={{marginTop: 16}}
        disabled={!sel}
        onClick={() => onConfirm(TABLES.find(t => t.id === sel))}>
        {sel ? `Traspasar a ${TABLES.find(t => t.id === sel).name}` : 'Selecciona una mesa'}
      </button>
    </div>
  );
}

Object.assign(window, {
  ScreenLogin, ScreenMesas, MesaCard, MesaDetail, LineRow, LineDetailContent, ModRow,
  AbrirMesaForm, CerrarCuentaForm, EditDinersForm, TransferForm, StatPill,
});
