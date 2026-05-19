// screens-tabs.jsx — Pedidos histórico, Cocina (avisos), Perfil

const { useState: useStateD, useMemo: useMemoD, useEffect: useEffectD } = React;

// ════════════════════════════════════════════════════════
// PEDIDOS — histórico de comandas enviadas en la sesión
// ════════════════════════════════════════════════════════
function ScreenPedidos({ nav, toast }) {
  const { PEDIDOS_INICIAL } = window.YDATA;
  const [filter, setFilter] = useStateD('todos');
  const [search, setSearch] = useStateD('');

  const filtros = [
    { id: 'todos', label: 'Todos' },
    { id: 'cocina', label: 'En cocina' },
    { id: 'parcial', label: 'Parciales' },
    { id: 'servido', label: 'Servidos' },
    { id: 'pagado', label: 'Pagados' },
  ];

  const filtered = PEDIDOS_INICIAL.filter(p => {
    if (filter === 'pagado' && !p.pagado) return false;
    if (filter === 'cocina' && p.estado !== 'cocina') return false;
    if (filter === 'parcial' && p.estado !== 'parcial') return false;
    if (filter === 'servido' && p.estado !== 'servido') return false;
    if (search && !p.id.includes(search) && !p.mesa.includes(search.toUpperCase())) return false;
    return true;
  });

  const stats = {
    total: PEDIDOS_INICIAL.length,
    pagados: PEDIDOS_INICIAL.filter(p => p.pagado).length,
    importe: PEDIDOS_INICIAL.reduce((s,p) => s + p.total, 0),
  };

  return (
    <>
      <div className="tb">
        <div className="tb-title">
          <span className="tb-title-main">Mis pedidos</span>
          <span className="tb-title-sub">Turno cena · {stats.total} comandas · {fmt(stats.importe)}</span>
        </div>
        <button className="tb-action"><Icon name="filter" size={18}/></button>
      </div>

      {/* Search */}
      <div style={{padding:'10px 12px', background:'var(--white)',
        borderBottom:'1px solid var(--gray-200)', flexShrink: 0}}>
        <div style={{display:'flex', alignItems:'center', gap: 8,
          background:'var(--gray-100)', borderRadius: 10, padding:'8px 12px'}}>
          <Icon name="search" size={14} color="var(--gray-400)"/>
          <input value={search} onChange={e => setSearch(e.target.value)}
            placeholder="Buscar nº ticket o mesa…"
            style={{flex:1, border:'none', background:'transparent',
              outline:'none', fontFamily:'var(--font)', fontSize: 13, color:'var(--black)'}}/>
        </div>
      </div>

      {/* Filter chips */}
      <div style={{display:'flex', gap: 6, padding:'10px 12px',
        background:'var(--white)', borderBottom:'1px solid var(--gray-200)',
        overflowX:'auto', flexShrink: 0}}>
        {filtros.map(f => (
          <button key={f.id} className={`chip ${filter === f.id ? 'active' : ''}`}
            onClick={() => setFilter(f.id)}>{f.label}</button>
        ))}
      </div>

      <div className="content" style={{padding: '12px'}}>
        {filtered.length === 0 ? (
          <div style={{textAlign:'center', padding:'40px 20px', color:'var(--gray-400)'}}>
            <div style={{fontSize: 32, marginBottom: 8}}>📋</div>
            <div style={{fontSize:13}}>Sin pedidos que mostrar</div>
          </div>
        ) : (
          <div style={{display:'flex', flexDirection:'column', gap: 8}}>
            {filtered.map(p => (
              <PedidoCard key={p.id} p={p}
                onClick={() => toast(`Pedido ${p.id} · ${p.mesa}`)}/>
            ))}
          </div>
        )}
      </div>
    </>
  );
}

function PedidoCard({ p, onClick }) {
  const estados = {
    cocina:  { c: 'tag-amber', t: 'En cocina', icon: 'flame' },
    parcial: { c: 'tag-blue',  t: 'Parcial', icon: 'clock' },
    servido: { c: 'tag-green', t: 'Servido', icon: 'check' },
  };
  const e = estados[p.estado];
  return (
    <button onClick={onClick} className="card tap" style={{
      padding: '12px 14px',
      display: 'flex', alignItems: 'center', gap: 12,
      textAlign: 'left',
    }}>
      <div style={{
        width: 40, height: 40, borderRadius: 10,
        background: 'var(--gray-100)',
        display: 'flex', alignItems: 'center', justifyContent: 'center',
        fontFamily: 'var(--mono)', fontSize: 13, fontWeight: 600,
        color: 'var(--black)',
        flexShrink: 0,
      }}>{p.mesa}</div>
      <div style={{flex: 1, minWidth: 0}}>
        <div className="row" style={{gap: 6}}>
          <span className="mono" style={{fontSize: 12, fontWeight: 600, color:'var(--black)'}}>{p.id}</span>
          <span className={`tag ${e.c}`}>
            <Icon name={e.icon} size={9}/> {e.t}
          </span>
          {p.pagado && <span className="tag tag-dark">Pagado · {p.metodo}</span>}
        </div>
        <div style={{fontSize: 11, color: 'var(--gray-500)', marginTop: 3}}>
          {p.zona} · {p.hora} · {p.lineas} líneas
        </div>
      </div>
      <div style={{textAlign: 'right'}}>
        <div className="mono" style={{fontSize: 14, fontWeight: 600, color: 'var(--black)'}}>{fmt(p.total)}</div>
        <Icon name="chevronRight" size={14} color="var(--gray-300)"/>
      </div>
    </button>
  );
}

// ════════════════════════════════════════════════════════
// COCINA — avisos en tiempo real
// ════════════════════════════════════════════════════════
function ScreenCocina({ nav, toast, onClearAviso }) {
  const { AVISOS_COCINA } = window.YDATA;
  const [filter, setFilter] = useStateD('todos');
  const [dismissed, setDismissed] = useStateD([]);

  const filters = [
    { id: 'todos', label: 'Todos', count: AVISOS_COCINA.length },
    { id: 'listo', label: 'Listos', count: AVISOS_COCINA.filter(a=>a.tipo==='listo').length },
    { id: 'demora', label: 'Demoras', count: AVISOS_COCINA.filter(a=>a.tipo==='demora').length },
    { id: 'rotura', label: 'Roturas', count: AVISOS_COCINA.filter(a=>a.tipo==='rotura').length },
  ];

  const visible = AVISOS_COCINA.filter(a =>
    !dismissed.includes(a.id) && (filter === 'todos' || a.tipo === filter));

  return (
    <>
      <div className="tb">
        <div className="tb-title">
          <span className="tb-title-main row" style={{gap:8}}>
            Cocina
            {AVISOS_COCINA.length > 0 && (
              <span style={{
                background: 'var(--accent)', color:'var(--white)',
                fontSize: 10, fontWeight: 700, fontFamily: 'var(--mono)',
                padding: '2px 6px', borderRadius: 100,
              }}>{AVISOS_COCINA.length} avisos</span>
            )}
          </span>
          <span className="tb-title-sub">Notificaciones de cocina y barra</span>
        </div>
        <button className="tb-action" onClick={() => toast('Avisos actualizados')}>
          <Icon name="refresh" size={18}/>
        </button>
      </div>

      <div style={{display:'flex', gap: 6, padding:'10px 12px',
        background:'var(--white)', borderBottom:'1px solid var(--gray-200)',
        overflowX:'auto', flexShrink: 0}}>
        {filters.map(f => (
          <button key={f.id} className={`chip ${filter === f.id ? 'active' : ''}`}
            onClick={() => setFilter(f.id)}>
            {f.label} <span style={{opacity:.6, marginLeft: 4, fontFamily:'var(--mono)'}}>{f.count}</span>
          </button>
        ))}
      </div>

      <div className="content" style={{padding: '12px'}}>
        {visible.length === 0 ? (
          <div style={{textAlign:'center', padding:'40px 20px', color:'var(--gray-400)'}}>
            <div style={{fontSize:32, marginBottom: 8}}>✨</div>
            <div style={{fontSize:14, fontWeight:500, color:'var(--gray-600)'}}>Sin avisos</div>
            <div style={{fontSize:12, marginTop:4}}>Todo bajo control</div>
          </div>
        ) : (
          <div style={{display:'flex', flexDirection:'column', gap: 10}}>
            {visible.map(a => (
              <AvisoCard key={a.id} a={a}
                onAck={() => {
                  setDismissed(prev => [...prev, a.id]);
                  onClearAviso && onClearAviso();
                  toast('Aviso atendido');
                }}
                onGoMesa={() => {
                  const m = window.YDATA.MESAS.find(x => x.id === a.mesa);
                  if (m) nav.push({ name: 'mesa', mesa: m });
                }}
              />
            ))}
          </div>
        )}
      </div>
    </>
  );
}

function AvisoCard({ a, onAck, onGoMesa }) {
  const config = {
    listo:  { bg: 'var(--green-soft)', color: 'var(--green)', icon: 'check', label: 'PLATO LISTO' },
    demora: { bg: 'var(--amber-soft)', color: 'var(--amber)', icon: 'clock', label: 'DEMORA' },
    rotura: { bg: 'var(--accent-soft)', color: 'var(--accent)', icon: 'warning', label: 'ROTURA STOCK' },
  };
  const c = config[a.tipo];
  return (
    <div className="card" style={{
      padding: 0, overflow: 'hidden',
      borderColor: a.urgente ? c.color : 'var(--gray-200)',
      borderWidth: a.urgente ? '2px' : '1.5px',
      animation: a.urgente && a.tipo === 'listo' ? 'glow-green 2s ease infinite' : 'none',
    }}>
      <div style={{
        background: c.bg, color: c.color,
        padding: '8px 12px',
        display:'flex', alignItems:'center', gap: 8,
        fontSize: 11, fontWeight: 700, letterSpacing: '.5px',
      }}>
        <Icon name={c.icon} size={14} strokeWidth={2.5}/>
        {c.label}
        {a.urgente && a.tipo === 'listo' && <span style={{
          marginLeft: 'auto',
          display:'inline-flex', alignItems:'center', gap: 4,
          fontSize: 10, opacity: .8,
        }}>
          <span style={{width: 6, height: 6, borderRadius: 3, background: c.color,
            animation: 'pulse-dot 1s infinite'}}/>
          AHORA
        </span>}
      </div>
      <div style={{padding: '12px 14px'}}>
        <div className="row" style={{gap: 10}}>
          <div style={{
            width: 40, height: 40, borderRadius: 10,
            background: 'var(--black)', color:'var(--white)',
            display:'flex', alignItems:'center', justifyContent:'center',
            fontFamily:'var(--mono)', fontSize: 13, fontWeight: 700,
            flexShrink: 0,
          }}>{a.mesa}</div>
          <div style={{flex: 1, minWidth: 0}}>
            <div style={{fontSize: 13.5, fontWeight: 600, color:'var(--black)', lineHeight: 1.3}}>
              {a.plato}
            </div>
            <div style={{fontSize: 11, color:'var(--gray-500)', marginTop: 2}}>
              Mesa {a.mesa} · {a.hora}
            </div>
          </div>
        </div>
        <div className="row" style={{marginTop: 12, gap: 8}}>
          <button className="btn btn-ghost btn-sm" style={{flex: 1}} onClick={onGoMesa}>
            Ver mesa
          </button>
          <button className="btn btn-primary btn-sm" style={{flex: 1}} onClick={onAck}>
            <Icon name="check" size={13}/> Atendido
          </button>
        </div>
      </div>
      <style>{`
        @keyframes glow-green {
          0%, 100% { box-shadow: 0 0 0 0 var(--green-soft); }
          50% { box-shadow: 0 0 0 6px var(--green-soft); }
        }
        @keyframes pulse-dot {
          0%, 100% { opacity: 1; transform: scale(1); }
          50% { opacity: .4; transform: scale(1.6); }
        }
      `}</style>
    </div>
  );
}

// ════════════════════════════════════════════════════════
// PERFIL — usuario, turno, caja, ajustes
// ════════════════════════════════════════════════════════
function ScreenPerfil({ nav, toast, onLogout }) {
  const { CAMARERO } = window.YDATA;
  const stats = [
    { label: 'Comandas', value: '24', sub: 'Esta sesión' },
    { label: 'Facturado', value: '634€', sub: 'Bruto turno' },
    { label: 'Mesas', value: '12', sub: 'Atendidas' },
    { label: 'Tiempo', value: '3h 42m', sub: 'En turno' },
  ];

  return (
    <>
      <div className="tb">
        <div className="tb-title">
          <span className="tb-title-main">Mi perfil</span>
          <span className="tb-title-sub">Sesión y configuración</span>
        </div>
      </div>

      <div className="content" style={{padding: '14px'}}>
        {/* User card */}
        <div className="card" style={{
          background: 'var(--black)', color: 'var(--white)', borderColor: 'var(--black)',
          padding: '18px',
          marginBottom: 16,
        }}>
          <div className="row" style={{gap: 14}}>
            <div style={{
              width: 56, height: 56, borderRadius: '50%',
              background: 'var(--accent)', color:'var(--white)',
              display:'flex', alignItems:'center', justifyContent:'center',
              fontFamily:'var(--mono)', fontSize: 19, fontWeight: 600,
              flexShrink: 0,
              boxShadow: '0 6px 18px rgba(255,77,77,.35)',
            }}>{CAMARERO.id}</div>
            <div style={{flex: 1, minWidth: 0}}>
              <div style={{fontSize: 16, fontWeight: 600}}>{CAMARERO.nombre}</div>
              <div style={{fontSize: 11.5, opacity: .65, marginTop: 2}}>{CAMARERO.rol}</div>
              <div style={{fontSize: 11, opacity: .55, marginTop: 4,
                display:'inline-flex', alignItems:'center', gap: 5,
                background:'rgba(255,255,255,.08)',
                padding:'3px 8px', borderRadius: 100,
              }}>
                <span style={{width:6, height:6, borderRadius:3, background:'var(--green)'}}/>
                {CAMARERO.turno}
              </div>
            </div>
          </div>
        </div>

        {/* Stats */}
        <div className="section-title">Tu turno en números</div>
        <div style={{display:'grid', gridTemplateColumns:'1fr 1fr', gap: 8, marginBottom: 16}}>
          {stats.map(s => (
            <div key={s.label} className="card" style={{padding: '12px 14px'}}>
              <div className="mono" style={{fontSize: 22, fontWeight: 500, color:'var(--black)', letterSpacing: '-.5px'}}>
                {s.value}
              </div>
              <div style={{fontSize: 11.5, color:'var(--gray-700)', fontWeight: 500, marginTop: 4}}>
                {s.label}
              </div>
              <div style={{fontSize: 10, color:'var(--gray-400)', marginTop: 1}}>
                {s.sub}
              </div>
            </div>
          ))}
        </div>

        {/* Caja */}
        <div className="section-title">Caja</div>
        <div className="card" style={{padding: 0, overflow:'hidden', marginBottom: 16}}>
          <SheetItem icon="cash" label="Estado de caja" sub="Abierta · 18:00 · Saldo 247,50€"
            onClick={() => toast('Estado de caja')}/>
          <SheetItem icon="print" label="Imprimir arqueo Z" sub="Cierre parcial"
            onClick={() => toast('Imprimiendo arqueo…')}/>
          <SheetItem icon="settings" label="Movimientos manuales" sub="Entradas y salidas"
            onClick={() => toast('Abrir movimientos')}/>
        </div>

        {/* Settings */}
        <div className="section-title">Ajustes</div>
        <div className="card" style={{padding: 0, overflow:'hidden', marginBottom: 16}}>
          <SheetItem icon="bell" label="Notificaciones" sub="Sonidos y avisos"
            onClick={() => toast('Notificaciones')}/>
          <SheetItem icon="print" label="Impresoras emparejadas" sub="3 conectadas"
            onClick={() => toast('Impresoras')}/>
          <SheetItem icon="refresh" label="Sincronizar" sub="Última: hace 12s"
            onClick={() => toast('Sincronizando…')}/>
          <SheetItem icon="info" label="Acerca de" sub="Yurest PDA v2.4.1"
            onClick={() => toast('Yurest PDA v2.4.1')}/>
        </div>

        <button className="btn btn-block" style={{
          color:'var(--accent)', borderColor: 'var(--accent-soft)',
          background:'var(--accent-soft)', fontWeight: 600,
        }} onClick={onLogout}>
          <Icon name="logout" size={16}/> Cerrar sesión
        </button>

        <div style={{textAlign:'center', fontSize: 10, color:'var(--gray-400)', marginTop: 16}}>
          Yurest PDA · Restaurante Voraz · ES-A47221
        </div>
      </div>
    </>
  );
}

Object.assign(window, {
  ScreenPedidos, ScreenCocina, ScreenPerfil, PedidoCard, AvisoCard,
});
