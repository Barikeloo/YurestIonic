// shared.jsx — Componentes primitivos compartidos
const { useState, useEffect, useRef } = React;

const fmt = (c) =>
  (c / 100).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
const fmtNum = (c) =>
  (c / 100).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const ROLES = {
  camarero: { label: 'Camarero', color: '#5a5a5a', canDiscount: false, canManualPrice: false, canCancelSale: false, canMovement50: false, canForceClose: false, canCreditNote: false },
  supervisor: { label: 'Supervisor', color: '#1a9e5a', canDiscount: true, canManualPrice: true, canCancelSale: true, canMovement50: true, canForceClose: false, canCreditNote: false },
  admin: { label: 'Admin', color: '#ff4d4d', canDiscount: true, canManualPrice: true, canCancelSale: true, canMovement50: true, canForceClose: true, canCreditNote: true },
};

// ── NumPad ──────────────────────────────────────────────
function NumPad({ value = 0, onChange }) {
  const tap = (k) => {
    if (k === 'C') return onChange(0);
    if (k === '⌫') return onChange(Math.floor(value / 10));
    const next = value * 10 + parseInt(k);
    if (next <= 9999999) onChange(next);
  };
  const keys = ['1','2','3','4','5','6','7','8','9','C','0','⌫'];
  return (
    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3,1fr)', gap: 8 }}>
      {keys.map((k) => (
        <button key={k} onClick={() => tap(k)} style={{
          height: 68, borderRadius: 12,
          border: '1.5px solid #e8e8e8',
          background: k === 'C' ? '#ffecec' : k === '⌫' ? '#f4f4f4' : '#ffffff',
          color: k === 'C' ? '#ff4d4d' : '#0d0d0d',
          fontSize: 22, fontFamily: "'DM Mono', monospace", fontWeight: 600,
          cursor: 'pointer', boxShadow: '0 1px 2px rgba(0,0,0,0.06)',
          transition: 'transform 0.07s, background 0.1s',
        }}
          onMouseDown={e => { e.currentTarget.style.transform = 'scale(0.95)'; }}
          onMouseUp={e => { e.currentTarget.style.transform = 'scale(1)'; }}
        >{k}</button>
      ))}
    </div>
  );
}

// ── Amount Display ───────────────────────────────────────
function AmountDisplay({ cents, label, large, color }) {
  const int = Math.floor(cents / 100);
  const dec = String(cents % 100).padStart(2, '0');
  return (
    <div style={{ textAlign: 'center', marginBottom: 12 }}>
      {label && <div style={{ fontSize: 12, color: '#7a7a7a', fontFamily: 'DM Sans, sans-serif', marginBottom: 4 }}>{label}</div>}
      <div style={{
        fontSize: large ? 54 : 34, fontFamily: "'DM Mono', monospace",
        fontWeight: 700, color: color || '#0d0d0d', lineHeight: 1, letterSpacing: -2,
      }}>
        {int.toLocaleString('es-ES')}
        <span style={{ fontSize: large ? '0.5em' : '0.6em', letterSpacing: 0 }}>,{dec}</span>
        <span style={{ fontSize: large ? '0.35em' : '0.4em', color: '#a0a0a0', fontWeight: 400, marginLeft: 4 }}>€</span>
      </div>
    </div>
  );
}

// ── Modal ────────────────────────────────────────────────
function Modal({ show, onClose, title, children, wide, noPad }) {
  if (!show) return null;
  return (
    <div style={{
      position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.42)', zIndex: 300,
      display: 'flex', alignItems: 'center', justifyContent: 'center',
      backdropFilter: 'blur(3px)',
    }} onClick={onClose}>
      <div style={{
        background: '#fff', borderRadius: 20,
        width: wide ? 900 : 520, maxWidth: '96vw', maxHeight: '92vh',
        overflow: 'hidden', display: 'flex', flexDirection: 'column',
        boxShadow: '0 32px 80px rgba(0,0,0,0.22)',
      }} onClick={e => e.stopPropagation()}>
        <div style={{
          padding: '18px 24px', borderBottom: '1px solid #e8e8e8',
          display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexShrink: 0,
        }}>
          <span style={{ fontSize: 17, fontWeight: 700, fontFamily: 'DM Sans, sans-serif', color: '#0d0d0d' }}>{title}</span>
          <button onClick={onClose} style={{ border: 'none', background: 'none', fontSize: 24, color: '#a0a0a0', cursor: 'pointer', lineHeight: 1, padding: '0 2px' }}>×</button>
        </div>
        <div style={{ overflowY: 'auto', flex: 1, padding: noPad ? 0 : 24 }}>{children}</div>
      </div>
    </div>
  );
}

// ── Segment Control ──────────────────────────────────────
function Segment({ options, value, onChange }) {
  return (
    <div style={{ display: 'flex', background: '#f4f4f4', borderRadius: 10, padding: 3, gap: 2 }}>
      {options.map(o => (
        <button key={o.value} onClick={() => onChange(o.value)} style={{
          flex: 1, padding: '8px 10px', borderRadius: 8, border: 'none',
          background: value === o.value ? '#fff' : 'transparent',
          color: value === o.value ? '#0d0d0d' : '#7a7a7a',
          fontFamily: 'DM Sans, sans-serif', fontSize: 13,
          fontWeight: value === o.value ? 600 : 400,
          cursor: 'pointer',
          boxShadow: value === o.value ? '0 1px 3px rgba(0,0,0,0.1)' : 'none',
          transition: 'all 0.12s', whiteSpace: 'nowrap',
        }}>{o.label}</button>
      ))}
    </div>
  );
}

// ── Badge ────────────────────────────────────────────────
function Badge({ text, color = '#ff4d4d', fill }) {
  return (
    <span style={{
      display: 'inline-flex', alignItems: 'center', padding: '2px 8px',
      borderRadius: 20, fontSize: 11, fontWeight: 700, fontFamily: 'DM Sans, sans-serif',
      background: fill ? color : color + '1a', color: fill ? '#fff' : color,
      whiteSpace: 'nowrap',
    }}>{text}</span>
  );
}

// ── Button ───────────────────────────────────────────────
function Btn({ children, onClick, variant = 'fill', color = '#ff4d4d', size = 'md', disabled, block, style: sx = {} }) {
  const vs = {
    fill: { background: color, color: '#fff', border: 'none' },
    outline: { background: 'transparent', color, border: `1.5px solid ${color}` },
    ghost: { background: 'transparent', color, border: 'none' },
    gray: { background: '#f4f4f4', color: '#0d0d0d', border: 'none' },
    success: { background: '#1a9e5a', color: '#fff', border: 'none' },
    danger: { background: '#ff4d4d', color: '#fff', border: 'none' },
  };
  const ss = {
    sm: { padding: '6px 14px', fontSize: 12, height: 32 },
    md: { padding: '10px 20px', fontSize: 14, height: 42 },
    lg: { padding: '14px 28px', fontSize: 16, height: 52 },
  };
  return (
    <button disabled={disabled} onClick={onClick} style={{
      ...(vs[variant] || vs.fill), ...(ss[size] || ss.md), ...sx,
      borderRadius: 10, fontFamily: 'DM Sans, sans-serif', fontWeight: 600,
      cursor: disabled ? 'not-allowed' : 'pointer', opacity: disabled ? 0.4 : 1,
      display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 6,
      width: block ? '100%' : 'auto', transition: 'all 0.12s', whiteSpace: 'nowrap',
    }}>{children}</button>
  );
}

// ── Card ─────────────────────────────────────────────────
function Card({ children, style: sx = {}, onClick, padding = 16 }) {
  return (
    <div onClick={onClick} style={{
      background: '#fff', borderRadius: 14,
      boxShadow: '0 1px 3px rgba(0,0,0,0.07)',
      border: '1px solid #e8e8e8', padding,
      cursor: onClick ? 'pointer' : 'default',
      transition: 'box-shadow 0.12s', ...sx,
    }}
      onMouseEnter={onClick ? e => { e.currentTarget.style.boxShadow = '0 4px 14px rgba(0,0,0,0.12)'; } : undefined}
      onMouseLeave={onClick ? e => { e.currentTarget.style.boxShadow = '0 1px 3px rgba(0,0,0,0.07)'; } : undefined}
    >{children}</div>
  );
}

// ── KPI Card ─────────────────────────────────────────────
function KpiCard({ label, value, sub, color, mono = true }) {
  return (
    <Card style={{ flex: 1, minWidth: 0 }}>
      <div style={{ fontSize: 10, color: '#a0a0a0', fontFamily: 'DM Sans', textTransform: 'uppercase', letterSpacing: '0.6px', marginBottom: 6 }}>{label}</div>
      <div style={{ fontSize: 22, fontFamily: mono ? "'DM Mono', monospace" : 'DM Sans, sans-serif', fontWeight: 700, color: color || '#0d0d0d', lineHeight: 1 }}>{value}</div>
      {sub && <div style={{ fontSize: 11, color: '#a0a0a0', marginTop: 4, fontFamily: 'DM Sans' }}>{sub}</div>}
    </Card>
  );
}

// ── Toggle ───────────────────────────────────────────────
function Toggle({ value, onChange, color = '#1a9e5a' }) {
  return (
    <div onClick={() => onChange(!value)} style={{
      width: 40, height: 22, borderRadius: 11,
      background: value ? color : '#d3d3d3',
      cursor: 'pointer', position: 'relative', transition: 'background 0.2s', flexShrink: 0,
    }}>
      <div style={{
        position: 'absolute', top: 2, left: value ? 20 : 2, width: 18, height: 18,
        borderRadius: '50%', background: '#fff',
        transition: 'left 0.2s', boxShadow: '0 1px 3px rgba(0,0,0,0.2)',
      }} />
    </div>
  );
}

Object.assign(window, { NumPad, AmountDisplay, Modal, Segment, Badge, Btn, Card, KpiCard, Toggle, fmt, fmtNum, ROLES });
