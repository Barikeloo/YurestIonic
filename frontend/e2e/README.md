# End-to-End Tests

Tests E2E con Playwright, ejecutados contra el stack real (Angular + Laravel + MySQL) y seedeados con `SaonaDemoSeeder` antes de cada suite.

## Estructura

```
e2e/
├── specs/
│   ├── auth/           ← link device, login email/PIN, logout (read-only)
│   ├── cash/           ← apertura, movimiento, cierre de caja (mutativo)
│   ├── smoke/          ← navegación pública (read-only)
│   └── tpv/            ← flujo central mesa → comanda → cobro + auditoría (mutativo)
├── support/
│   ├── auth.ts         ← linkDevice, loginByPin, loginAsAdmin, pressPin
│   ├── browser-state.ts← clearDeviceLink
│   ├── cash.ts         ← openCashSession, closeCashSession, registerCashMovement
│   ├── fixtures.ts     ← constantes del seeder (restaurante, empleados, PINs)
│   ├── global-setup.ts ← re-seedea SaonaDemoSeeder antes de la suite
│   ├── seed.ts         ← runSaonaDemoSeed() reutilizable
│   └── tpv.ts          ← navegación mesas, comanda, cobro, lectura de caja
└── reports/            ← artefactos (gitignored)
    ├── artifacts/      ← vídeos, screenshots, traces por test
    └── html/           ← HTML report
```

## Requisitos

Docker corriendo con backend en `:8000` y frontend en `:4200`:

```bash
make start
```

Los browsers de Playwright se descargan a `~/Library/Caches/ms-playwright/` la primera vez:

```bash
cd frontend && npx playwright install chromium
```

## Comandos

Desde la raíz del repo:

```bash
make test-e2e            # corre toda la suite (≈2 min)
make test-e2e-headed     # ver el navegador
make test-e2e-ui         # modo interactivo
make test-e2e-videos     # fuerza vídeo + trace + screenshot en todos los tests
make test-e2e-fast       # salta el reseed (asume estado limpio)
make test-e2e-report     # abre el HTML report en http://localhost:9323
```

Equivalentes en `frontend/`:

```bash
E2E_SKIP_WEB_SERVER=1 E2E_BASE_URL=http://localhost:4200 npx playwright test
```

## Diseño de proyectos

`playwright.config.ts` define tres proyectos con dependencias:

| Project | Specs | Paralelismo | Notas |
|---|---|---|---|
| `stateful` | `cash/**`, `tpv/**` | `fullyParallel: false`, workers=1 | Mutaciones serias contra una sola caja por device |
| `chromium` | `auth/**`, `smoke/**` | paralelo | Read-only; depende de `stateful` |
| `mobile-chrome` | `auth/**`, `smoke/**` | paralelo | Pixel 7; depende de `stateful` |

La dependencia obliga a que `stateful` corra primero. `workers: 1` global evita que dos workers diferentes escriban a la misma fila de `cash_sessions`.

## Variables de entorno

| Variable | Default | Para qué |
|---|---|---|
| `E2E_BASE_URL` | `http://localhost:{port}` | El backend solo permite CORS desde `localhost` (no `127.0.0.1`) |
| `E2E_PORT` | `4200` | Puerto del frontend |
| `E2E_SKIP_WEB_SERVER` | `unset` | Salta el `ng serve` que Playwright lanzaría — úsalo si ya tienes Docker corriendo |
| `E2E_SKIP_SEED` | `unset` | Salta el reseed del global-setup; útil para iterar rápido |
| `E2E_SEEDER_CLASS` | `SaonaDemoSeeder` | Otro seeder si lo necesitas |
| `E2E_API_SERVICE` | `api` | Nombre del servicio docker-compose para el backend |
| `E2E_VERBOSE` | `unset` | Pipea stdout del seeder |
| `E2E_VIDEO` | `retain-on-failure` | `on` para guardar siempre |
| `E2E_TRACE` | `on-first-retry` | `on` para trace interactivo en todos los tests |
| `E2E_SCREENSHOT` | `only-on-failure` | `on` para guardar siempre |
| `PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH` | — | Ruta a un Chromium externo si no quieres descargarlo |

## Ver vídeos y traces

Después de un run, abre el reporte HTML:

```bash
make test-e2e-report
# → http://localhost:9323
```

Cada test enseña vídeo + screenshot + trace (botón "Trace") cuando están grabados. El trace es lo más útil para debug — timeline interactivo con DOM, red y consola en cada paso.

Para grabar artefactos siempre (no solo en fallo):

```bash
make test-e2e-videos
```

## Seeder Saona — qué te deja

`SaonaDemoSeeder` borra y reinserta los datos del restaurante "Bar Manolo":

- **Restaurante**: Bar Manolo (`barmanolo@gmail.com` / contraseña `12345678`)
- **6 usuarios con PIN**: Manolo (admin, PIN **1234**), María (supervisor, **2345**), Carlos/Laura/Javier/Sofía (operators, **3456-6789**)
- **3 IVAs** (4 / 10 / 21 %)
- **4 zonas** (Terraza, Salón Principal, Barra, Reservado) y **~28 mesas**
- **Catálogo amplio** con modificadores y variantes
- **Device pre-linkeado** `seed-device-001` con quick-access para los 5 no-admin

Los UUIDs son estables entre reseeds (updateOrInsert con email como clave única).

## Troubleshooting

**Falla `docker compose exec api ...` en el global-setup**
Asegúrate de que `make start` está corriendo y el contenedor `api` está sano.

**CORS bloquea el login (`Failed to fetch`)**
Estás usando `127.0.0.1:4200`; el backend solo permite `localhost:4200`. Usa `E2E_BASE_URL=http://localhost:4200`.

**Click intercepted por `<app-pin-auth-modal>`**
El backend pide PIN re-auth para acciones NORMAL/CRITICAL. Los helpers `openCashSession`, `openTable`, `closeAccountFromMesa`, `chargeFromMesa` aceptan empleado opcional y meten el PIN si el modal aparece.

**Numpad no responde como espero**
El componente `OpenCashModal` pre-rellena `initialAmountCents = 15000`. `enterAmountWithNumpad` pulsa "C" antes de teclear para limpiar.

**Locator no encuentra botón con texto exacto**
Las plantillas Angular ponen whitespace alrededor de `{{ key }}`. Usa `getByRole('button', { name: 'X', exact: true })` que normaliza el accessible name.

**Tras logout la URL escala con `?returnUrl=…` recursivo**
Bug pre-existente en el interceptor; las aserciones usan `/\/login(\?|$)/` para tolerarlo.
