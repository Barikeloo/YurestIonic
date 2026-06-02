# End-to-End Tests

Los tests E2E viven en `frontend/e2e` y se ejecutan con Playwright.

## Estructura

- `specs/`: escenarios ejecutables agrupados por flujo.
- `support/`: helpers compartidos para estado de navegador, mocks y fixtures.
- `reports/`: artefactos generados por Playwright, ignorables en commits.

## Comandos

Desde `frontend/`:

```bash
npm run e2e
npm run e2e:headed
npm run e2e:ui
```

Desde la raíz del repo, con Docker:

```bash
make test-e2e
```

Por defecto Playwright levanta Angular en `http://127.0.0.1:4200`. Si ya tienes el frontend levantado:

```bash
E2E_SKIP_WEB_SERVER=1 E2E_BASE_URL=http://localhost:4200 npm run e2e
```

Los primeros tests usan mocks HTTP para cubrir navegación y estados de UI sin depender de datos seed. Los flujos críticos completos pueden añadirse como specs separadas contra backend + seeders cuando queramos validar el sistema entero.
