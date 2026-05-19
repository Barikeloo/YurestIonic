// screens-comanda.jsx — Nueva comanda, Modificadores, Resumen, Confirmación, Cambio/Juntar mesas

const { useState: useStateC, useMemo: useMemoC, useEffect: useEffectC } = React;

// ════════════════════════════════════════════════════════
// NUEVA COMANDA — selector de productos + carrito flotante
// ════════════════════════════════════════════════════════
function ScreenComanda({ nav, mesa, toast }) {
  const { FAMILIAS, PRODUCTOS } = window.YDATA;
  const [fam, setFam] = useStateC('todo');
  const [search, setSearch] = useStateC('');
  const [carrito, setCarrito] = useStateC([]); // [{prod, qty, modificadores}]
  const [modProd, setModProd] = useStateC(null);
  const [carritoOpen, setCarritoOpen] = useStateC(false);
  const [numpadOpen, setNumpadOpen] = useStateC(false);

  const productos = PRODUCTOS.filter(p =>
    (fam === 'todo' || p.fam === fam) &&
    (!search || p.name.toLowerCase().includes(search.toLowerCase()) || p.code.includes(search))
  );

  const total = carrito.reduce((s, c) => s + c.prod.precio * c.qty, 0);
  const totalQty = carrito.reduce((s, c) => s + c.qty, 0);

  const addProduct = (p, extra = {}) => {
    setCarrito(prev => {
      // Si no tiene modificadores, sumar cantidad de línea existente
      if (!extra.nota && !extra.tamano && !extra.comensal) {
        const idx = prev.findIndex(x => x.prod.id === p.id && !x.nota && !x.tamano && !x.comensal);
        if (idx >= 0) {
          const next = [...prev];
          next[idx] = {...next[idx], qty: next[idx].qty + 1};
          return next;
        }
      }
      return [...prev, { id: Date.now()+Math.random(), prod: p, qty: 1, ...extra }];
    });
    toast(`+ ${p.name}`);
  };

  return (
    <>
      <TopBar
        title="Nueva comanda"
        subtitle={`Mesa ${mesa.id} · ${mesa.zona.charAt(0).toUpperCase()+mesa.zona.slice(1)}`}
        onBack={() => nav.pop()}
        actions={<>
          <button className="tb-action" onClick={() => setNumpadOpen(true)}>
            <Icon name="search" size={18}/>
          </button>
        </>}
      />

      {/* Family chips */}
      <div style={{
        display:'flex', gap: 6, padding: '10px 12px',
        background: 'var(--white)',
        borderBottom: '1px solid var(--gray-200)',
        overflowX: 'auto',
        flexShrink: 0,
      }}>
        {FAMILIAS.map(f => (
          <button key={f.id}
            className={`chip ${fam === f.id ? 'active' : ''}`}
            onClick={() => setFam(f.id)}
          >{f.name}</button>
        ))}
      </div>

      {/* Search */}
      <div style={{
        padding: '10px 12px', flexShrink: 0,
        background: 'var(--white)',
        borderBottom: '1px solid var(--gray-200)',
      }}>
        <div style={{
          display: 'flex', alignItems: 'center', gap: 8,
          background: 'var(--gray-100)',
          borderRadius: 10, padding: '8px 12px',
        }}>
          <Icon name="search" size={14} color="var(--gray-400)"/>
          <input
            value={search}
            onChange={e => setSearch(e.target.value)}
            placeholder="Buscar producto o código…"
            style={{
              flex: 1, border: 'none', background: 'transparent',
              outline: 'none', fontFamily: 'var(--font)', fontSize: 13,
              color: 'var(--black)',
            }}
          />
          <button onClick={() => setNumpadOpen(true)} style={{
            background: 'var(--white)', border: '1px solid var(--gray-200)',
            padding: '4px 8px', borderRadius: 6,
            fontFamily: 'var(--mono)', fontSize: 10, fontWeight: 600,
            color: 'var(--gray-600)', cursor: 'pointer',
          }}>123</button>
        </div>
      </div>

      <div className="content" style={{padding: '12px', paddingBottom: carrito.length ? 84 : 12}}>
        <div style={{display:'grid', gridTemplateColumns:'repeat(2, 1fr)', gap: 8}}>
          {productos.map(p => (
            <ProductCard key={p.id} p={p}
              count={carrito.filter(c => c.prod.id === p.id).reduce((s,c)=>s+c.qty,0)}
              onTap={() => addProduct(p)}
              onLongPress={() => setModProd(p)}
            />
          ))}
        </div>
        {productos.length === 0 && (
          <div style={{textAlign:'center', padding:'40px 20px', color: 'var(--gray-400)'}}>
            <div style={{fontSize: 32, marginBottom: 8}}>🔎</div>
            <div style={{fontSize: 13}}>Sin resultados</div>
          </div>
        )}
      </div>

      {/* Carrito flotante */}
      {carrito.length > 0 && (
        <div style={{
          position: 'absolute', bottom: 14, left: 14, right: 14,
          background: 'var(--black)', color: 'var(--white)',
          borderRadius: 14,
          padding: '12px 16px',
          display: 'flex', alignItems: 'center', gap: 12,
          boxShadow: '0 8px 24px rgba(0,0,0,.25)',
          animation: 'fab-in .25s cubic-bezier(.22,.9,.32,1) both',
          cursor: 'pointer',
        }} onClick={() => nav.push({ name: 'resumen-comanda', mesa, carrito, addProduct, setCarrito })}>
          <div style={{
            background: 'var(--accent)',
            minWidth: 32, height: 32, borderRadius: 16,
            padding: '0 8px',
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            fontWeight: 700, fontFamily: 'var(--mono)', fontSize: 14,
          }}>{totalQty}</div>
          <div style={{flex: 1, minWidth: 0}}>
            <div style={{fontSize: 13, fontWeight: 600}}>Revisar comanda</div>
            <div style={{fontSize: 10.5, opacity: .65}}>
              {carrito.length} {carrito.length === 1 ? 'línea' : 'líneas'} · Mesa {mesa.id}
            </div>
          </div>
          <div className="mono" style={{fontSize: 17, fontWeight: 600}}>{fmt(total)}</div>
          <Icon name="forward" size={16}/>
        </div>
      )}

      {/* Modificadores sheet */}
      <Sheet open={!!modProd} onClose={() => setModProd(null)}
        title={modProd?.name}
        subtitle={modProd ? `${fmt(modProd.precio)} · IVA ${modProd.iva}%` : ''}>
        {modProd && <ModificadoresPanel
          p={modProd}
          onAdd={(extra) => { addProduct(modProd, extra); setModProd(null); }}
        />}
      </Sheet>

      {/* Numpad de código rápido */}
      <Sheet open={numpadOpen} onClose={() => setNumpadOpen(false)} title="Código rápido"
        subtitle="Introduce el código del producto">
        <NumpadCodigo onConfirm={(code) => {
          const p = PRODUCTOS.find(p => p.code === code);
          if (p) { addProduct(p); setNumpadOpen(false); }
          else toast('Código no encontrado');
        }}/>
      </Sheet>

      <style>{`@keyframes fab-in { from {transform: translateY(20px); opacity: 0;} to {transform: translateY(0); opacity: 1;} }`}</style>
    </>
  );
}

function ProductCard({ p, count, onTap, onLongPress }) {
  const [pressing, setPressing] = useStateC(false);
  // long press handler
  const timer = React.useRef(null);
  const start = () => {
    setPressing(true);
    timer.current = setTimeout(() => {
      setPressing(false);
      onLongPress && onLongPress();
      timer.current = null;
    }, 450);
  };
  const end = (fire) => {
    setPressing(false);
    if (timer.current) {
      clearTimeout(timer.current);
      timer.current = null;
      if (fire) onTap();
    }
  };

  return (
    <button
      className="card"
      onMouseDown={start} onMouseUp={() => end(true)} onMouseLeave={() => end(false)}
      onTouchStart={start} onTouchEnd={() => end(true)} onTouchCancel={() => end(false)}
      style={{
        padding: '10px 8px 10px',
        display: 'grid',
        gridTemplateRows: '32px 32px 18px 16px',
        rowGap: 4,
        justifyItems: 'center', alignItems: 'center',
        textAlign: 'center',
        position: 'relative',
        transform: pressing ? 'scale(.97)' : 'scale(1)',
        borderColor: pressing ? 'var(--accent)' : 'var(--gray-200)',
        boxShadow: pressing ? '0 0 0 3px var(--accent-soft)' : 'none',
        transition: 'all .12s',
        cursor: 'pointer',
        background: 'var(--white)',
      }}>
      <div style={{fontSize: 26, lineHeight: 1}}>{p.emoji}</div>
      <div style={{
        fontSize: 11.5, fontWeight: 500, color: 'var(--black)', lineHeight: 1.2,
        display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical',
        overflow: 'hidden',
        width: '100%',
        wordBreak: 'break-word',
      }}>
        {p.name}
      </div>
      <div className="mono" style={{fontSize: 13, fontWeight: 600, color: 'var(--accent)', lineHeight: 1}}>
        {fmt(p.precio)}
      </div>
      <div style={{display:'flex', gap:3, alignItems:'center', minHeight: 16}}>
        {p.alergenos && p.alergenos.length > 0 && p.alergenos.slice(0,4).map(a => (
          <span key={a} style={{
            fontSize: 8, fontWeight: 700,
            background: 'var(--amber-soft)', color: 'var(--amber)',
            padding: '2px 4px', borderRadius: 4,
            fontFamily: 'var(--mono)',
            lineHeight: 1,
          }}>{a}</span>
        ))}
      </div>
      {count > 0 && (
        <div style={{
          position: 'absolute', top: 6, right: 6,
          background: 'var(--accent)', color: 'var(--white)',
          width: 20, height: 20, borderRadius: 10,
          fontSize: 11, fontWeight: 700, fontFamily: 'var(--mono)',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
        }}>{count}</div>
      )}
    </button>
  );
}

// ════════════════════════════════════════════════════════
// Modificadores panel (inside sheet)
// ════════════════════════════════════════════════════════
function ModificadoresPanel({ p, onAdd }) {
  const { ALERG_LABEL } = window.YDATA;
  const [tamano, setTamano] = useStateC(p.tamanos ? p.tamanos[0] : null);
  const [puntoCoccion, setPuntoCoccion] = useStateC(null);
  const [comensal, setComensal] = useStateC(0);
  const [qty, setQty] = useStateC(1);
  const [nota, setNota] = useStateC('');
  const notasSugeridas = ['Sin cebolla', 'Sin gluten', 'Para llevar', 'Sin sal', 'Aparte', 'Caliente', 'Frío'];
  const tieneCarne = p.fam === 'principales' && /solomillo|hamburguesa/i.test(p.name);

  const precioTotal = (p.precio + (tamano?.p || 0)) * qty;

  return (
    <div style={{display:'flex', flexDirection:'column', gap: 14}}>

      {/* Alergens visible */}
      {p.alergenos && p.alergenos.length > 0 && (
        <div>
          <div className="section-title" style={{margin: 0, marginBottom: 6}}>Alérgenos</div>
          <div className="row" style={{flexWrap:'wrap', gap: 6}}>
            {p.alergenos.map(a => (
              <span key={a} className="tag tag-amber">
                <span style={{fontFamily:'var(--mono)'}}>{a}</span> {ALERG_LABEL[a]}
              </span>
            ))}
          </div>
        </div>
      )}

      {/* Tamaño */}
      {p.tamanos && (
        <div>
          <div className="section-title" style={{margin: 0, marginBottom: 6}}>Tamaño</div>
          <div className="row" style={{gap: 6, flexWrap: 'wrap'}}>
            {p.tamanos.map(t => (
              <button key={t.n}
                className={`chip ${tamano?.n === t.n ? 'active' : ''}`}
                onClick={() => setTamano(t)}>
                {t.n} {t.p > 0 && <span className="mono">+{fmt(t.p)}</span>}
              </button>
            ))}
          </div>
        </div>
      )}

      {/* Punto de cocción */}
      {tieneCarne && (
        <div>
          <div className="section-title" style={{margin: 0, marginBottom: 6}}>Punto</div>
          <div className="row" style={{gap: 6, flexWrap: 'wrap'}}>
            {['Poco hecho','Al punto','Hecho','Muy hecho'].map(pp => (
              <button key={pp}
                className={`chip ${puntoCoccion === pp ? 'active' : ''}`}
                onClick={() => setPuntoCoccion(pp)}>{pp}</button>
            ))}
          </div>
        </div>
      )}

      {/* Comensal */}
      <div>
        <div className="section-title" style={{margin: 0, marginBottom: 6}}>Comensal</div>
        <div className="row" style={{gap: 6, flexWrap: 'wrap'}}>
          <button className={`chip ${comensal === 0 ? 'active' : ''}`} onClick={() => setComensal(0)}>
            Compartido
          </button>
          {[1,2,3,4,5,6].map(n => (
            <button key={n}
              className={`chip ${comensal === n ? 'active' : ''}`}
              onClick={() => setComensal(n)}>
              C{n}
            </button>
          ))}
        </div>
      </div>

      {/* Nota libre */}
      <div>
        <div className="section-title" style={{margin: 0, marginBottom: 6}}>Nota</div>
        <input value={nota} onChange={e => setNota(e.target.value)}
          placeholder="Ej. sin pan, alergia leve, niño…"
          style={{
            width: '100%', padding: '10px 12px',
            border: '1.5px solid var(--gray-200)',
            borderRadius: 10, fontFamily: 'var(--font)', fontSize: 13,
            outline: 'none', marginBottom: 6,
          }}/>
        <div className="row" style={{flexWrap:'wrap', gap: 5}}>
          {notasSugeridas.map(n => (
            <button key={n} className="chip chip-outline" onClick={() => setNota(n)}>
              {n}
            </button>
          ))}
        </div>
      </div>

      {/* Footer: qty + add */}
      <div style={{
        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
        padding: '12px 0 0',
        borderTop: '1px solid var(--gray-100)',
      }}>
        <Stepper value={qty} onChange={setQty} min={1} size="lg"/>
        <button className="btn btn-primary btn-lg"
          onClick={() => onAdd({
            qty, comensal, nota: nota || null,
            tamano: tamano?.n !== 'Normal' ? tamano?.n : null,
            punto: puntoCoccion,
          })}
          style={{minWidth: 160}}>
          Añadir · <span className="mono">{fmt(precioTotal)}</span>
        </button>
      </div>
    </div>
  );
}

// ════════════════════════════════════════════════════════
// Numpad para código rápido
// ════════════════════════════════════════════════════════
function NumpadCodigo({ onConfirm }) {
  const [code, setCode] = useStateC('');
  const onKey = (k) => {
    if (k === 'back') return setCode(c => c.slice(0,-1));
    if (k === 'ok') return onConfirm(code);
    if (code.length >= 5) return;
    setCode(c => c + k);
  };
  return (
    <div>
      <div style={{
        background: 'var(--gray-50)', borderRadius: 12,
        padding: '14px 16px',
        fontFamily: 'var(--mono)', fontSize: 26, fontWeight: 600,
        color: 'var(--black)',
        textAlign: 'center', letterSpacing: 4,
        marginBottom: 12,
        minHeight: 30,
      }}>{code || <span style={{color:'var(--gray-300)'}}>•••</span>}</div>
      <div style={{
        display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)',
        gap: 8,
      }}>
        {['1','2','3','4','5','6','7','8','9','back','0','ok'].map(k => (
          <button key={k} onClick={() => onKey(k)} style={{
            height: 52,
            borderRadius: 12,
            border: '1.5px solid var(--gray-200)',
            background: k === 'ok' ? 'var(--accent)' :
                        k === 'back' ? 'var(--gray-100)' : 'var(--white)',
            color: k === 'ok' ? 'var(--white)' : 'var(--black)',
            fontFamily: 'var(--mono)',
            fontSize: 19, fontWeight: 500,
            cursor: 'pointer',
            display: 'flex', alignItems: 'center', justifyContent: 'center',
          }}>
            {k === 'back' ? <Icon name="back" size={16}/> :
             k === 'ok' ? <Icon name="check" size={18}/> : k}
          </button>
        ))}
      </div>
    </div>
  );
}

// ════════════════════════════════════════════════════════
// RESUMEN COMANDA — revisar antes de enviar a cocina
// ════════════════════════════════════════════════════════
function ScreenResumenComanda({ nav, mesa, carrito, setCarrito, toast }) {
  const [items, setItems] = useStateC(carrito);

  // Group by comensal
  const byComensal = useMemoC(() => {
    const m = {};
    items.forEach(it => {
      const k = it.comensal || 0;
      if (!m[k]) m[k] = [];
      m[k].push(it);
    });
    return m;
  }, [items]);

  const comensalKeys = Object.keys(byComensal).sort((a,b) =>
    a === '0' ? 1 : b === '0' ? -1 : Number(a) - Number(b));

  const total = items.reduce((s,c) => s + c.prod.precio * c.qty, 0);
  const totalQty = items.reduce((s,c) => s + c.qty, 0);

  const updateQty = (id, delta) => {
    setItems(prev => prev.map(it => it.id === id ? {...it, qty: Math.max(0, it.qty + delta)} : it).filter(it => it.qty > 0));
  };

  return (
    <>
      <TopBar
        title="Revisar comanda"
        subtitle={`Mesa ${mesa.id} · ${totalQty} productos`}
        onBack={() => { setCarrito && setCarrito(items); nav.pop(); }}
      />

      <div className="content" style={{padding: '12px'}}>
        {items.length === 0 ? (
          <div style={{textAlign:'center', padding:'40px 20px', color:'var(--gray-400)'}}>
            <div style={{fontSize: 32, marginBottom: 8}}>🛒</div>
            <div style={{fontSize: 14, fontWeight: 500, color:'var(--gray-600)'}}>Carrito vacío</div>
          </div>
        ) : (
          comensalKeys.map(k => (
            <div key={k} style={{marginBottom: 12}}>
              <div className="section-title row" style={{justifyContent:'space-between', marginRight: 0}}>
                <span>{k === '0' ? 'Compartido / mesa' : `Comensal ${k}`}</span>
                <span style={{fontFamily:'var(--mono)', textTransform:'none', letterSpacing: 0}}>
                  {byComensal[k].length} ítems
                </span>
              </div>
              <div className="card" style={{padding: 0, overflow:'hidden'}}>
                {byComensal[k].map((it, i, arr) => (
                  <div key={it.id} style={{
                    display: 'grid', gridTemplateColumns: 'auto 1fr auto',
                    gap: 10, padding: '12px 12px',
                    alignItems: 'center',
                    borderBottom: i === arr.length-1 ? 'none' : '1px solid var(--gray-100)',
                  }}>
                    <div style={{fontSize: 22, lineHeight: 1}}>{it.prod.emoji}</div>
                    <div style={{minWidth: 0}}>
                      <div style={{fontSize: 13, fontWeight: 500, color: 'var(--black)', lineHeight: 1.25}}>
                        {it.prod.name}
                      </div>
                      <div className="row" style={{gap: 5, marginTop: 4, flexWrap: 'wrap'}}>
                        {it.tamano && <span className="tag tag-gray">{it.tamano}</span>}
                        {it.punto && <span className="tag tag-red">{it.punto}</span>}
                        {it.nota && (
                          <span style={{fontSize: 10.5, color: 'var(--gray-500)', fontStyle:'italic'}}>
                            "{it.nota}"
                          </span>
                        )}
                      </div>
                    </div>
                    <div style={{display:'flex', flexDirection:'column', alignItems:'flex-end', gap: 6}}>
                      <Stepper value={it.qty} onChange={v => setItems(prev => prev.map(p => p.id===it.id?{...p,qty:v}:p).filter(p=>p.qty>0))} size="sm"/>
                      <div className="mono" style={{fontSize: 12, fontWeight: 500}}>
                        {fmt(it.prod.precio * it.qty)}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ))
        )}

        {/* Resumen */}
        {items.length > 0 && (
          <div className="card" style={{padding: '14px', marginTop: 4}}>
            <div className="row-between" style={{fontSize: 12, color:'var(--gray-500)', marginBottom: 4}}>
              <span>Productos</span>
              <span className="mono">{totalQty}</span>
            </div>
            <div className="row-between" style={{fontSize: 12, color:'var(--gray-500)', marginBottom: 8}}>
              <span>Subtotal sin IVA</span>
              <span className="mono">{fmt(total * .89)}</span>
            </div>
            <div className="row-between" style={{fontSize: 15, fontWeight: 600,
              paddingTop: 8, borderTop: '1px solid var(--gray-200)'}}>
              <span>Total</span>
              <span className="mono">{fmt(total)}</span>
            </div>
          </div>
        )}
      </div>

      {/* Footer */}
      <div style={{
        padding: '10px 12px',
        background: 'var(--white)',
        borderTop: '1px solid var(--gray-200)',
        display: 'flex', gap: 8,
        flexShrink: 0,
      }}>
        <button className="btn btn-ghost"
          onClick={() => nav.pop()}>
          <Icon name="plus" size={14}/> Más productos
        </button>
        <button className="btn btn-primary" style={{flex: 1}}
          disabled={items.length === 0}
          onClick={() => nav.replace({ name: 'confirmacion', mesa, items, total })}>
          <Icon name="send" size={14}/> Enviar a cocina
        </button>
      </div>
    </>
  );
}

// ════════════════════════════════════════════════════════
// CONFIRMACIÓN / impresión
// ════════════════════════════════════════════════════════
function ScreenConfirmacion({ nav, mesa, items, total, toast }) {
  const [step, setStep] = useStateC(0); // 0: sending, 1: done

  useEffectC(() => {
    const t = setTimeout(() => setStep(1), 1100);
    return () => clearTimeout(t);
  }, []);

  const printers = [
    { name: 'Cocina caliente', count: items.filter(i => ['principales','entrantes'].includes(i.prod.fam)).reduce((s,i)=>s+i.qty,0) },
    { name: 'Barra', count: items.filter(i => ['bebidas','cervezas','vinos','cafe'].includes(i.prod.fam)).reduce((s,i)=>s+i.qty,0) },
    { name: 'Postres', count: items.filter(i => i.prod.fam === 'postres').reduce((s,i)=>s+i.qty,0) },
  ].filter(p => p.count > 0);

  return (
    <div className="pda-screen" style={{background:'var(--gray-50)'}}>
      <div style={{
        flex: 1, display: 'flex', flexDirection: 'column',
        alignItems: 'center', justifyContent: 'center',
        padding: '20px 24px',
      }}>
        <div style={{
          width: 84, height: 84, borderRadius: '50%',
          background: step === 1 ? 'var(--green-soft)' : 'var(--accent-soft)',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          marginBottom: 18,
          animation: step === 1 ? 'pulse-green 1.2s ease' : 'spin 1s linear infinite',
          transition: 'all .3s',
        }}>
          {step === 1
            ? <Icon name="check" size={42} color="var(--green)" strokeWidth={2.5}/>
            : <Icon name="send" size={36} color="var(--accent)" strokeWidth={2}/>
          }
        </div>
        <div style={{fontSize: 20, fontWeight: 600, color: 'var(--black)', textAlign: 'center'}}>
          {step === 0 ? 'Enviando comanda…' : '¡Comanda enviada!'}
        </div>
        <div style={{fontSize: 13, color: 'var(--gray-500)', textAlign:'center', marginTop: 6}}>
          {step === 0 ? 'Imprimiendo en las áreas correspondientes' :
            `Mesa ${mesa.id} · ${items.reduce((s,i)=>s+i.qty,0)} productos · ${fmt(total)}`}
        </div>

        {step === 1 && (
          <div style={{
            width: '100%', maxWidth: 320, marginTop: 24,
            display: 'flex', flexDirection: 'column', gap: 8,
            animation: 'enterFade .3s ease both',
          }}>
            {printers.map(pr => (
              <div key={pr.name} className="card" style={{
                padding: '12px 14px',
                display: 'flex', alignItems: 'center', gap: 12,
              }}>
                <div style={{
                  width: 36, height: 36, borderRadius: 10,
                  background: 'var(--green-soft)', color: 'var(--green)',
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                }}>
                  <Icon name="print" size={16}/>
                </div>
                <div style={{flex: 1}}>
                  <div style={{fontSize: 13, fontWeight: 500, color: 'var(--black)'}}>{pr.name}</div>
                  <div style={{fontSize: 11, color: 'var(--gray-500)'}}>{pr.count} {pr.count === 1 ? 'producto' : 'productos'}</div>
                </div>
                <Icon name="check" size={16} color="var(--green)" strokeWidth={2.5}/>
              </div>
            ))}
          </div>
        )}
      </div>

      {step === 1 && (
        <div style={{
          padding: '12px 14px 14px',
          background: 'var(--white)',
          borderTop: '1px solid var(--gray-200)',
          display: 'grid', gridTemplateColumns: '1fr 1fr',
          gap: 8,
          animation: 'enterFade .35s ease both',
          animationDelay: '.1s',
          flexShrink: 0,
        }}>
          <button className="btn btn-ghost" onClick={() => nav.replace({ name: 'tabs', tab: 'mesas' })}>
            Ver mesas
          </button>
          <button className="btn btn-dark" onClick={() => nav.pop() /* back to mesa */ }>
            Volver a la mesa
          </button>
        </div>
      )}

      <style>{`
        @keyframes spin { from { transform: rotate(0); } to { transform: rotate(360deg); } }
        @keyframes pulse-green {
          0% { transform: scale(.6); }
          50% { transform: scale(1.1); }
          100% { transform: scale(1); }
        }
      `}</style>
    </div>
  );
}

// ════════════════════════════════════════════════════════
// CAMBIAR MESA / JUNTAR MESAS
// ════════════════════════════════════════════════════════
function ScreenCambiarMesa({ nav, mesa, mode, toast }) {
  // mode: 'cambiar' | 'juntar'
  const { ZONAS, MESAS } = window.YDATA;
  const [zona, setZona] = useStateC(mesa.zona);
  const [sel, setSel] = useStateC([]);

  const isJuntar = mode === 'juntar';
  const candidatas = MESAS.filter(m => m.id !== mesa.id && (isJuntar ? m.estado === 'ocupada' : m.estado === 'libre'));
  const filtradas = candidatas.filter(m => m.zona === zona);

  const toggle = (id) => {
    if (isJuntar) {
      setSel(prev => prev.includes(id) ? prev.filter(x => x !== id) : [...prev, id]);
    } else {
      setSel([id]);
    }
  };

  return (
    <>
      <TopBar
        title={isJuntar ? 'Juntar mesas' : 'Cambiar mesa'}
        subtitle={`Desde Mesa ${mesa.id} (${mesa.zona})`}
        onBack={() => nav.pop()}
      />

      <div style={{
        padding: '14px 14px 0',
        flexShrink: 0,
      }}>
        <div className="card" style={{
          background: 'var(--blue-soft)', borderColor: 'var(--blue-soft)',
          padding: '10px 12px',
          display: 'flex', alignItems: 'center', gap: 10,
        }}>
          <Icon name="info" size={16} color="var(--blue)"/>
          <div style={{fontSize: 11.5, color: 'var(--blue)', lineHeight: 1.4}}>
            {isJuntar
              ? 'Selecciona una o más mesas para unificar la cuenta con la actual.'
              : 'Selecciona una mesa libre para mover el pedido actual.'}
          </div>
        </div>
      </div>

      <div style={{
        display:'flex', gap: 6, padding: '12px',
        overflowX: 'auto',
        flexShrink: 0,
      }}>
        {ZONAS.map(z => (
          <button key={z.id}
            className={`chip ${zona === z.id ? 'active' : ''}`}
            onClick={() => setZona(z.id)}
          >{z.name}</button>
        ))}
      </div>

      <div className="content" style={{padding: '0 12px 12px'}}>
        <div style={{display:'grid', gridTemplateColumns:'repeat(3, 1fr)', gap: 8}}>
          {filtradas.map(m => (
            <button key={m.id} onClick={() => toggle(m.id)} className="card" style={{
              padding: '14px 8px',
              minHeight: 80,
              display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center',
              borderColor: sel.includes(m.id) ? 'var(--accent)' : 'var(--gray-200)',
              borderWidth: sel.includes(m.id) ? '2px' : '1.5px',
              boxShadow: sel.includes(m.id) ? '0 0 0 3px var(--accent-soft)' : 'none',
              cursor: 'pointer',
              position: 'relative',
              background: isJuntar && m.estado === 'ocupada' ? 'var(--black)' :
                          (!isJuntar && m.estado === 'libre') ? 'var(--white)' : 'var(--white)',
              color: isJuntar && m.estado === 'ocupada' ? 'var(--white)' : 'var(--black)',
            }}>
              <div className="mono" style={{fontSize: 20, fontWeight: 600, lineHeight: 1}}>{m.id}</div>
              <div style={{fontSize: 10, marginTop: 6, opacity: .7}}>
                {isJuntar
                  ? `${m.comensales} pax · ${fmt(m.total)}`
                  : `${m.capacidad} pax`}
              </div>
              {sel.includes(m.id) && (
                <div style={{
                  position: 'absolute', top: 4, right: 4,
                  width: 18, height: 18, borderRadius: 9,
                  background: 'var(--accent)', color: 'var(--white)',
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                }}>
                  <Icon name="check" size={12} strokeWidth={3}/>
                </div>
              )}
            </button>
          ))}
        </div>
        {filtradas.length === 0 && (
          <div style={{textAlign:'center', padding: '32px 20px', color:'var(--gray-400)', fontSize: 13}}>
            Sin mesas {isJuntar ? 'ocupadas' : 'libres'} en esta zona
          </div>
        )}
      </div>

      <div style={{
        padding: '10px 12px',
        background: 'var(--white)',
        borderTop: '1px solid var(--gray-200)',
        flexShrink: 0,
      }}>
        <button className="btn btn-primary btn-block btn-lg"
          disabled={sel.length === 0}
          onClick={() => {
            toast(isJuntar
              ? `Mesa ${mesa.id} juntada con ${sel.join(', ')}`
              : `Pedido movido de ${mesa.id} → ${sel[0]}`);
            nav.pop();
            nav.pop();
          }}>
          {isJuntar
            ? `Juntar ${sel.length} ${sel.length === 1 ? 'mesa' : 'mesas'}`
            : `Mover a Mesa ${sel[0] || '…'}`}
        </button>
      </div>
    </>
  );
}

Object.assign(window, {
  ScreenComanda, ScreenResumenComanda, ScreenConfirmacion, ScreenCambiarMesa,
  ProductCard, ModificadoresPanel, NumpadCodigo,
});
