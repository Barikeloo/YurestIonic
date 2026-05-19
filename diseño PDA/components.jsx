// components.jsx — Shared UI for PDA Yurest
// • Iconos SVG, TopBar, BottomNav, Sheet, AmountDisplay, NumpadCents,
//   PinAuthModal, Stepper, Toast, helpers (formatCents, fmt, fmtTime)

const { useState: useStateC, useEffect: useEffectC, useRef: useRefC } = React;

// ─── Helpers ──────────────────────────────────────
function formatCents(cents) {
  if (cents == null) return '0,00';
  const v = (cents / 100).toFixed(2).replace('.', ',');
  return v;
}
function formatCentsEur(cents) { return formatCents(cents) + '€'; }
function fmt(amountEur) { return amountEur.toFixed(2).replace('.', ',') + '€'; }
function fmtTime(min) {
  if (min == null) return '';
  if (min < 60) return `${min} min`;
  const h = Math.floor(min / 60);
  const m = min % 60;
  return `${h}h ${m}m`;
}
function timeFromString(openedAt) {
  // openedAt = "21:14" or ISO
  if (!openedAt) return null;
  let openedDate;
  if (openedAt.includes('T')) openedDate = new Date(openedAt);
  else {
    const [h, m] = openedAt.split(':').map(Number);
    const d = new Date(); d.setHours(h, m, 0, 0);
    openedDate = d;
  }
  const minutes = Math.floor((Date.now() - openedDate.getTime()) / 60000);
  return Math.max(0, minutes);
}

// ─── Icons ───────────────────────────────────────
function Icon({ name, size = 20, color = 'currentColor', strokeWidth = 2 }) {
  const common = {
    width: size, height: size, viewBox: '0 0 24 24',
    fill: 'none', stroke: color, strokeWidth, strokeLinecap: 'round', strokeLinejoin: 'round',
  };
  const P = {
    mesas: <><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></>,
    pedidos: <><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></>,
    cocina: <><path d="M6 2l3 6h6l3-6"/><path d="M5 8v12a2 2 0 002 2h10a2 2 0 002-2V8"/><line x1="9" y1="13" x2="15" y2="13"/></>,
    perfil: <><circle cx="12" cy="8" r="4"/><path d="M4 22a8 8 0 0116 0"/></>,
    caja: <><rect x="2" y="6" width="20" height="14" rx="2"/><line x1="12" y1="10" x2="12" y2="16"/><line x1="9" y1="13" x2="15" y2="13"/></>,
    more: <><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></>,
    horizontalDots: <><circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/></>,
    back: <polyline points="15 18 9 12 15 6"/>,
    forward: <polyline points="9 18 15 12 9 6"/>,
    plus: <><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></>,
    minus: <line x1="5" y1="12" x2="19" y2="12"/>,
    close: <><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></>,
    search: <><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></>,
    filter: <><line x1="4" y1="6" x2="20" y2="6"/><line x1="7" y1="12" x2="17" y2="12"/><line x1="10" y1="18" x2="14" y2="18"/></>,
    bell: <><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></>,
    user: <><circle cx="12" cy="7" r="4"/><path d="M5.5 21a6.5 6.5 0 0113 0"/></>,
    users: <><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></>,
    cash: <><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><line x1="6" y1="10" x2="6" y2="14"/><line x1="18" y1="10" x2="18" y2="14"/></>,
    card: <><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></>,
    bizum: <><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 2"/></>,
    split: <><path d="M3 6h18"/><path d="M3 12h18"/><path d="M3 18h18"/></>,
    note: <><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></>,
    edit: <><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></>,
    trash: <><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></>,
    check: <polyline points="20 6 9 17 4 12"/>,
    chevronRight: <polyline points="9 18 15 12 9 6"/>,
    chevronDown: <polyline points="6 9 12 15 18 9"/>,
    chevronUp: <polyline points="18 15 12 9 6 15"/>,
    print: <><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></>,
    send: <><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></>,
    clock: <><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></>,
    swap: <><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 014-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 01-4 4H3"/></>,
    merge: <><path d="M8 6l-4 4 4 4"/><path d="M16 6l4 4-4 4"/><line x1="4" y1="10" x2="20" y2="10"/></>,
    unmerge: <><path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></>,
    transfer: <><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><line x1="12" y1="11" x2="16" y2="11"/><line x1="12" y1="16" x2="16" y2="16"/><line x1="8" y1="11" x2="8.01" y2="11"/><line x1="8" y1="16" x2="8.01" y2="16"/></>,
    flame: <path d="M8.5 14.5A2.5 2.5 0 0011 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 11-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 002.5 2.5z"/>,
    refresh: <><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></>,
    settings: <><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"/></>,
    logout: <><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></>,
    lock: <><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></>,
    fingerprint: <><path d="M12 3a9 9 0 019 9v3"/><path d="M3 12a9 9 0 0115-6.7"/><path d="M7 20a4 4 0 01-1-2.7"/><path d="M19 20a8 8 0 01-7-4"/><path d="M11 20a4 4 0 01-4-4v-1a5 5 0 0110 0v1a3 3 0 003 3"/></>,
    eye: <><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></>,
    info: <><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></>,
    warning: <><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></>,
    package: <><path d="M16.5 9.4l-9-5.19"/><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></>,
    arrowDown: <><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></>,
    arrowUp: <><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></>,
    gear: <><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"/></>,
    chart: <><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></>,
  };
  return <svg {...common}>{P[name] || null}</svg>;
}

// ─── TopBar ──────────────────────────────────────
function TopBar({ title, subtitle, onBack, actions, dark }) {
  return (
    <div className={`tb ${dark ? 'tb-dark' : ''}`}>
      {onBack && (
        <button className="tb-back" onClick={onBack} aria-label="Volver">
          <Icon name="back" size={20}/>
        </button>
      )}
      <div className="tb-title">
        <span className="tb-title-main">{title}</span>
        {subtitle && <span className="tb-title-sub">{subtitle}</span>}
      </div>
      {actions}
    </div>
  );
}

// ─── BottomNav (4 tabs: Mesas, Pedidos, Caja, Más) ──
function BottomNav({ current, onSelect, badges = {} }) {
  const items = [
    { id: 'mesas',   label: 'Mesas',   icon: 'mesas' },
    { id: 'pedidos', label: 'Pedidos', icon: 'pedidos' },
    { id: 'caja',    label: 'Caja',    icon: 'caja' },
    { id: 'mas',     label: 'Más',     icon: 'more' },
  ];
  return (
    <div className="tabbar">
      {items.map(it => (
        <button key={it.id}
          className={`tab-item ${current === it.id ? 'active' : ''}`}
          onClick={() => onSelect(it.id)}
        >
          <div className="tab-icon-wrap">
            <Icon name={it.icon} size={22} strokeWidth={current === it.id ? 2.2 : 1.8}/>
          </div>
          <span>{it.label}</span>
          {badges[it.id] ? <span className="tab-badge">{badges[it.id]}</span> : null}
        </button>
      ))}
    </div>
  );
}

// ─── Sheet (bottom modal) ───────────────────────
function Sheet({ open, onClose, title, subtitle, children, footer, full }) {
  if (!open) return null;
  return (
    <div className="sheet-backdrop" onClick={onClose}>
      <div className={`sheet ${full ? 'sheet-full' : ''}`} onClick={e => e.stopPropagation()}>
        <div className="sheet-handle"/>
        {(title || subtitle) && (
          <div className="sheet-header">
            <div className="row-between">
              <div className="col" style={{minWidth:0,flex:1}}>
                {title && <div className="sheet-title">{title}</div>}
                {subtitle && <div className="sheet-sub">{subtitle}</div>}
              </div>
              <button className="tb-back" onClick={onClose}>
                <Icon name="close" size={18}/>
              </button>
            </div>
          </div>
        )}
        <div className="sheet-body">{children}</div>
        {footer && <div className="sheet-footer">{footer}</div>}
      </div>
    </div>
  );
}

// ─── AmountDisplay (céntimos → 12,50 €) ─────────
function AmountDisplay({ cents, label, large, color, suffix = '€' }) {
  const s = (cents / 100).toFixed(2);
  const [intPart, decPart] = s.split('.');
  return (
    <div style={{
      textAlign: 'center',
      padding: large ? '8px 0' : '4px 0',
    }}>
      {label && (
        <div style={{
          fontSize: 10, color: 'var(--gray-500)', fontWeight: 500,
          letterSpacing: '.5px', textTransform: 'uppercase',
          marginBottom: 4,
        }}>{label}</div>
      )}
      <div style={{
        fontFamily: 'var(--mono)',
        fontSize: large ? 38 : 22,
        fontWeight: large ? 300 : 500,
        letterSpacing: '-1.5px',
        lineHeight: 1,
        color: color || (cents > 0 ? 'var(--black)' : 'var(--gray-300)'),
      }}>
        {(cents || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '').slice(0,-2) ? intPart : intPart}
        <span style={{opacity: .55, fontSize: '.6em', marginLeft: 1}}>,{decPart}</span>
        <span style={{opacity: .35, fontSize: '.55em', marginLeft: 4}}>{suffix}</span>
      </div>
    </div>
  );
}

// ─── NumpadCents (teclado para introducir céntimos) ──
function NumpadCents({ value = 0, onChange, danger }) {
  const accent = danger ? 'var(--accent)' : 'var(--black)';
  const press = (k) => {
    if (k === 'C') return onChange(0);
    if (k === 'back') return onChange(Math.floor(value / 10));
    if (k === '00') return onChange(value * 100);
    const next = value * 10 + Number(k);
    if (next > 99999999) return;
    onChange(next);
  };
  const keys = ['1','2','3','4','5','6','7','8','9','C','0','back'];
  return (
    <div style={{
      display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)',
      gap: 6,
    }}>
      {keys.map(k => (
        <button key={k} onClick={() => press(k)} style={{
          height: 48, borderRadius: 12,
          border: '1.5px solid var(--gray-200)',
          background: k === 'C' ? 'var(--gray-100)' : 'var(--white)',
          fontFamily: 'var(--mono)', fontSize: 20, fontWeight: 500,
          color: k === 'C' ? 'var(--gray-600)' : accent,
          cursor: 'pointer',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          transition: 'background .1s, transform .08s',
        }}
        onTouchStart={() => {}}
        >
          {k === 'back' ? <Icon name="back" size={16}/> :
           k === 'C' ? <span style={{fontSize: 14, fontWeight: 600}}>C</span> :
           k}
        </button>
      ))}
    </div>
  );
}

// ─── Stepper +/- ────────────────────────────────
function Stepper({ value, onChange, min = 0, max = 99, size = 'md', disabled }) {
  const sz = size === 'sm' ? { btn: 28, font: 13, w: 32 }
           : size === 'lg' ? { btn: 40, font: 17, w: 44 }
           : { btn: 32, font: 14, w: 36 };
  return (
    <div style={{
      display: 'inline-flex', alignItems: 'center',
      background: 'var(--gray-100)', borderRadius: 100, padding: 2,
      opacity: disabled ? .5 : 1,
    }}>
      <button onClick={() => !disabled && value > min && onChange(value - 1)} style={{
        width: sz.btn, height: sz.btn, borderRadius: '50%',
        border: 'none', background: 'var(--white)',
        display: 'flex', alignItems: 'center', justifyContent: 'center',
        cursor: 'pointer', opacity: value <= min ? .4 : 1,
      }}><Icon name="minus" size={14}/></button>
      <div style={{
        width: sz.w, textAlign: 'center',
        fontFamily: 'var(--mono)', fontSize: sz.font, fontWeight: 600, color: 'var(--black)',
      }}>{value}</div>
      <button onClick={() => !disabled && value < max && onChange(value + 1)} style={{
        width: sz.btn, height: sz.btn, borderRadius: '50%',
        border: 'none', background: 'var(--accent)', color: 'var(--white)',
        display: 'flex', alignItems: 'center', justifyContent: 'center',
        cursor: 'pointer',
      }}><Icon name="plus" size={14}/></button>
    </div>
  );
}

// ─── DinersStepper grande (Stepper para abrir mesa) ───
function DinersStepperLarge({ value, onChange, min = 1, max = 20, disabled }) {
  return (
    <div className="diners-stepper-lg">
      <button className="ds-btn" disabled={disabled || value <= min}
        onClick={() => onChange(value - 1)}>−</button>
      <div className="ds-value">{value}</div>
      <button className="ds-btn ds-btn-plus" disabled={disabled || value >= max}
        onClick={() => onChange(value + 1)}>+</button>
    </div>
  );
}

// ─── PinAuthModal ───────────────────────────────
function PinAuthModal({ open, title, subtitle, onClose, onAuthenticated }) {
  const [pin, setPin] = useStateC('');
  const [err, setErr] = useStateC(false);
  const correct = window.YDATA.CURRENT_USER.pin;
  useEffectC(() => { if (open) { setPin(''); setErr(false); } }, [open]);
  if (!open) return null;
  const press = (k) => {
    if (err) setErr(false);
    if (k === 'back') return setPin(p => p.slice(0,-1));
    if (k === 'C') return setPin('');
    if (pin.length >= 4) return;
    const next = pin + k;
    setPin(next);
    if (next.length === 4) {
      setTimeout(() => {
        if (next === correct) onAuthenticated({ user: window.YDATA.CURRENT_USER });
        else { setErr(true); setTimeout(() => setPin(''), 350); }
      }, 150);
    }
  };
  const dots = [0,1,2,3].map(i => (
    <div key={i} style={{
      width: 12, height: 12, borderRadius: '50%',
      background: i < pin.length ? (err ? 'var(--accent)' : 'var(--black)') : 'transparent',
      border: `1.5px solid ${err ? 'var(--accent)' : 'var(--gray-300)'}`,
      transition: 'all .15s',
    }}/>
  ));
  return (
    <div className="sheet-backdrop" onClick={onClose}>
      <div className="pin-modal" onClick={e => e.stopPropagation()}>
        <button className="pin-close" onClick={onClose}>
          <Icon name="close" size={18}/>
        </button>
        <div className="pin-icon-wrap">
          <Icon name="lock" size={24} color="var(--accent)"/>
        </div>
        <div className="pin-title">{title}</div>
        {subtitle && <div className="pin-sub">{subtitle}</div>}
        <div className="pin-user">
          <div className="pin-user-avatar">{window.YDATA.CURRENT_USER.initials}</div>
          <span>{window.YDATA.CURRENT_USER.name}</span>
        </div>
        <div style={{display:'flex', gap: 12, marginBottom: 6,
          animation: err ? 'shake .35s' : 'none'}}>{dots}</div>
        <div style={{fontSize: 11, color: err ? 'var(--accent)' : 'var(--gray-500)',
          minHeight: 14, marginBottom: 12, fontWeight: 500}}>
          {err ? 'PIN incorrecto' : 'Introduce tu PIN'}
        </div>
        <div style={{width: 240}}>
          <NumpadCents value={pin === '' ? 0 : Number(pin)} onChange={() => {}}/>
          {/* Override with PIN-specific numpad */}
          <div style={{
            position: 'relative', marginTop: -212,
            display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 6,
          }}>
            {['1','2','3','4','5','6','7','8','9','C','0','back'].map(k => (
              <button key={k} onClick={() => press(k)} style={{
                height: 48, borderRadius: 12,
                border: '1.5px solid var(--gray-200)',
                background: k === 'C' ? 'var(--gray-100)' : 'var(--white)',
                fontFamily: 'var(--mono)', fontSize: 20, fontWeight: 500,
                color: 'var(--black)',
                cursor: 'pointer',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
              }}>
                {k === 'back' ? <Icon name="back" size={16}/> :
                 k === 'C' ? 'C' : k}
              </button>
            ))}
          </div>
        </div>
        <div style={{fontSize: 11, color: 'var(--gray-400)', marginTop: 14}}>
          Sesión de 5 min · PIN del operador
        </div>
      </div>
    </div>
  );
}

// ─── Toast ──────────────────────────────────────
function Toast({ message, icon = 'check', tone = 'default' }) {
  if (!message) return null;
  const bg = tone === 'error' ? 'var(--accent)'
           : tone === 'success' ? 'var(--green)'
           : 'var(--black)';
  return (
    <div className="toast" key={message} style={{background: bg}}>
      <Icon name={icon} size={15}/>
      {message}
    </div>
  );
}

// ─── SheetItem (lista en sheets) ───────────────
function SheetItem({ icon, label, sub, onClick, danger, right, disabled }) {
  return (
    <button onClick={onClick} disabled={disabled} style={{
      width: '100%', display: 'flex', alignItems: 'center', gap: 12,
      padding: '12px 4px', border: 'none', background: 'transparent',
      borderBottom: '1px solid var(--gray-100)',
      cursor: disabled ? 'not-allowed' : 'pointer',
      textAlign: 'left', fontFamily: 'var(--font)',
      opacity: disabled ? .5 : 1,
    }}>
      <div style={{
        width: 36, height: 36, borderRadius: 10,
        background: danger ? 'var(--accent-soft)' : 'var(--gray-100)',
        color: danger ? 'var(--accent)' : 'var(--gray-700)',
        display: 'flex', alignItems: 'center', justifyContent: 'center',
        flexShrink: 0,
      }}>
        <Icon name={icon} size={16}/>
      </div>
      <div style={{flex: 1, minWidth: 0}}>
        <div style={{fontSize: 14, fontWeight: 500, color: danger ? 'var(--accent)' : 'var(--black)'}}>
          {label}
        </div>
        {sub && <div style={{fontSize: 11.5, color: 'var(--gray-500)', marginTop: 2}}>{sub}</div>}
      </div>
      {right || <Icon name="chevronRight" size={14} color="var(--gray-300)"/>}
    </button>
  );
}

// ─── Tag chip ──────────────────────────────────
function Tag({ children, tone = 'gray', size = 'sm' }) {
  return <span className={`tag tag-${tone} tag-${size}`}>{children}</span>;
}

// ─── Card ──────────────────────────────────────
function Card({ children, onClick, dark, className = '', padding = 14, style = {} }) {
  return (
    <div onClick={onClick}
      className={`card ${dark ? 'card-dark' : ''} ${onClick ? 'tap' : ''} ${className}`}
      style={{padding, ...style}}
    >{children}</div>
  );
}

// ─── User avatar (with color hash) ──────────────
function UserAvatar({ name, initials, size = 36 }) {
  const colors = ['#FF4D4D','#1A6FE8','#1A9E5A','#E89E1A','#8B5CF6','#EC4899','#06B6D4','#84CC16'];
  const hash = (name || '').split('').reduce((s, c) => s + c.charCodeAt(0), 0);
  const bg = colors[hash % colors.length];
  return (
    <div style={{
      width: size, height: size, borderRadius: '50%',
      background: bg, color: '#fff',
      display: 'flex', alignItems: 'center', justifyContent: 'center',
      fontFamily: 'var(--mono)', fontSize: size * .38, fontWeight: 600,
      flexShrink: 0,
    }}>{initials}</div>
  );
}

Object.assign(window, {
  Icon, TopBar, BottomNav, Sheet, AmountDisplay, NumpadCents,
  Stepper, DinersStepperLarge, PinAuthModal, Toast, SheetItem, Tag, Card,
  UserAvatar,
  formatCents, formatCentsEur, fmt, fmtTime, timeFromString,
});
