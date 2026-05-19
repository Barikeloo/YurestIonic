// app.jsx — App principal con navigation stack + tabs + animaciones

const { useState: useStateA, useEffect: useEffectA, useRef: useRefA, useMemo: useMemoA } = React;

// Stack-based navigation with animation
function App() {
  // stack: array de { name, ...props }
  // tabRoots: el "tab" actual cuya raíz se muestra cuando stack queda en root
  const [stack, setStack] = useStateA([{ name: 'login' }]);
  const [direction, setDirection] = useStateA('push'); // push | pop | fade
  const [animating, setAnimating] = useStateA(false);
  const [outgoing, setOutgoing] = useStateA(null); // screen instance leaving
  const [toastMsg, setToastMsg] = useStateA(null);
  const [tabCarrito, setTabCarrito] = useStateA([]); // mantener carrito entre push/pop
  const avisosCount = window.YDATA.AVISOS_COCINA.length;

  const top = stack[stack.length - 1];

  const toast = (msg) => {
    setToastMsg(null);
    requestAnimationFrame(() => setToastMsg(msg));
    setTimeout(() => setToastMsg(null), 2400);
  };

  const animate = (dir, mutator) => {
    setOutgoing({ ...top, _animKey: Math.random() });
    setDirection(dir);
    setAnimating(true);
    mutator();
    setTimeout(() => {
      setOutgoing(null);
      setAnimating(false);
    }, 300);
  };

  const nav = {
    push: (s) => animate('push', () => setStack(prev => [...prev, s])),
    pop:  ()   => {
      if (stack.length <= 1) return;
      animate('pop', () => setStack(prev => prev.slice(0, -1)));
    },
    replace: (s) => animate('fade', () => setStack([s])),
    reset: (s) => animate('fade', () => setStack([s])),
  };

  // Render concrete screen based on top.name
  const renderScreen = (s, props = {}) => {
    const common = { nav, toast, ...props };
    switch (s.name) {
      case 'login':
        return <ScreenLogin {...common}/>;
      case 'tabs':
        return <TabsRoot tab={s.tab || 'mesas'} nav={nav} toast={toast}/>;
      case 'mesa':
        return <ScreenMesa mesa={s.mesa} {...common}/>;
      case 'comanda':
        return <ScreenComanda mesa={s.mesa} {...common}/>;
      case 'resumen-comanda':
        return <ScreenResumenComanda mesa={s.mesa} carrito={s.carrito} setCarrito={s.setCarrito} total={s.total} {...common}/>;
      case 'confirmacion':
        return <ScreenConfirmacion mesa={s.mesa} items={s.items} total={s.total} {...common}/>;
      case 'cobro':
        return <ScreenCobro mesa={s.mesa} total={s.total} {...common}/>;
      case 'cambiar-mesa':
        return <ScreenCambiarMesa mesa={s.mesa} mode="cambiar" {...common}/>;
      case 'juntar-mesa':
        return <ScreenCambiarMesa mesa={s.mesa} mode="juntar" {...common}/>;
      default:
        return <div style={{padding: 20}}>Pantalla "{s.name}" no implementada</div>;
    }
  };

  // Animation class assignment:
  // when push: incoming uses enter-push, outgoing uses exit-push
  // when pop:  incoming uses enter-pop,  outgoing uses exit-pop
  // when fade: incoming uses enter-fade, outgoing uses exit-fade
  const enterClass = `enter-${direction}`;
  const exitClass = `exit-${direction}`;

  return (
    <div className="pda-stack">
      {outgoing && (
        <div key={`out-${outgoing._animKey}`} className={`pda-screen ${exitClass}`}>
          {renderScreen(outgoing)}
        </div>
      )}
      <div key={`in-${stack.length}-${top.name}`} className={`pda-screen ${animating ? enterClass : ''}`}>
        {renderScreen(top)}
      </div>
      <Toast message={toastMsg}/>
    </div>
  );
}

// ─── TabsRoot: container con bottom-nav + screen interior ─────
function TabsRoot({ tab: initialTab, nav, toast }) {
  const [tab, setTab] = useStateA(initialTab || 'mesas');
  const [outgoing, setOutgoing] = useStateA(null);
  const [avisos, setAvisos] = useStateA(window.YDATA.AVISOS_COCINA.length);

  const handleTabChange = (newTab) => {
    if (newTab === tab) return;
    setOutgoing({ tab, _key: Math.random() });
    setTab(newTab);
    setTimeout(() => setOutgoing(null), 220);
  };

  const renderTab = (t) => {
    switch (t) {
      case 'mesas':   return <ScreenMesas nav={nav} toast={toast}/>;
      case 'pedidos': return <ScreenPedidos nav={nav} toast={toast}/>;
      case 'cocina':  return <ScreenCocina nav={nav} toast={toast} onClearAviso={() => setAvisos(a => Math.max(0, a-1))}/>;
      case 'perfil':  return <ScreenPerfil nav={nav} toast={toast} onLogout={() => nav.replace({ name: 'login' })}/>;
      default: return null;
    }
  };

  return (
    <div style={{
      display: 'flex', flexDirection: 'column',
      width: '100%', height: '100%',
      position: 'relative',
    }}>
      <div style={{
        flex: 1, position: 'relative', overflow: 'hidden',
        display: 'flex', flexDirection: 'column',
      }}>
        {outgoing && (
          <div key={`outt-${outgoing._key}`} style={{
            position: 'absolute', inset: 0,
            display: 'flex', flexDirection: 'column',
            animation: 'exitFade 220ms ease both',
          }}>
            {renderTab(outgoing.tab)}
          </div>
        )}
        <div key={`tab-${tab}`} style={{
          position: 'absolute', inset: 0,
          display: 'flex', flexDirection: 'column',
          animation: outgoing ? 'enterFade 220ms ease both' : 'none',
        }}>
          {renderTab(tab)}
        </div>
      </div>
      <BottomNav current={tab} onSelect={handleTabChange}
        avisos={avisos} pedidos={0}/>
    </div>
  );
}

window.App = App;
window.TabsRoot = TabsRoot;

// Render
const root = ReactDOM.createRoot(document.getElementById('app-root'));
root.render(
  <div className="pda-stage">
    <AndroidDevice>
      <App/>
    </AndroidDevice>
  </div>
);
