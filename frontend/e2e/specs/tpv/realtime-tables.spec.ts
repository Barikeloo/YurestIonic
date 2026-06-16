import { expect, test } from '@playwright/test';
import { loginByPin } from '../../support/auth';
import { openCashSession } from '../../support/cash';
import { employees } from '../../support/fixtures';
import {
  addProductToComanda,
  chargeFromMesa,
  closeAccountFromMesa,
  enterComandaForSelectedTable,
  navigateToMesas,
  openTable,
  payCash,
  selectZone,
  sendComanda,
} from '../../support/tpv';

function mesaT1(page: import('@playwright/test').Page) {
  return page.locator('.mesa').filter({
    has: page.locator('.mesa-name', { hasText: /^T1$/ }),
  }).first();
}

test.describe.serial('Real-time table status via Reverb', () => {
  test.beforeEach(({}, testInfo) => {
    test.skip(
      testInfo.project.name !== 'stateful',
      'Real-time tests mutate order state and run only in the stateful project',
    );
  });

  test('opening a table in page A updates table status in page B without reload', async ({ page, context, browser }) => {
    test.setTimeout(0); // pause() espera interacción del usuario

    // --- Pestaña A: operador que abre la mesa ---
    await loginByPin(page, employees.carlos);
    await openCashSession(page, employees.carlos, 5000);

    // --- Pestaña B: observador en otra sesión ---
    const ctxB = await browser.newContext();
    const pageB = await ctxB.newPage();
    await loginByPin(pageB, employees.laura);

    // Capturar mensajes WebSocket de Reverb en pestaña B
    const wsEvents: string[] = [];
    pageB.on('websocket', (ws) => {
      ws.on('framereceived', ({ payload }) => {
        const msg = typeof payload === 'string' ? payload : Buffer.from(payload as Buffer).toString();
        if (msg.includes('order.status_changed')) {
          wsEvents.push(msg);
        }
      });
    });

    // B navega a mesas y espera conexión WebSocket
    await navigateToMesas(pageB);
    await pageB.waitForTimeout(2000);

    // Estado inicial en B: T1 de Terraza debe estar libre
    await pageB.getByRole('button', { name: 'Terraza', exact: true }).click();
    await expect(mesaT1(pageB)).not.toHaveClass(/occupied/);

    // --- A abre la mesa T1 ---
    await navigateToMesas(page);
    await openTable(page, 'Terraza', 'T1', 2, employees.carlos);

    // Tras abrir la mesa la app navega a Pedidos; volver a Mesas para acceder a la comanda
    await navigateToMesas(page);
    await selectZone(page, 'Terraza');
    await mesaT1(page).click();

    // --- A añade una comanda con producto (total > 0) ---
    await enterComandaForSelectedTable(page);
    await addProductToComanda(page, 'Bebidas Frías', 'Agua mineral 50cl');
    await sendComanda(page);
    // sendComanda navega automáticamente a /app/mesas tras el envío

    // --- B debe actualizarse sola en ≤5s, sin reload ---
    await expect(mesaT1(pageB)).toHaveClass(/occupied/, { timeout: 5000 });

    // Verificar que llegó el evento WebSocket
    expect(wsEvents.length).toBeGreaterThan(0);
    expect(wsEvents[0]).toContain('order.created');

    await ctxB.close();
  });

  test('marking order to charge in page A updates table in page B without reload', async ({ page, context, browser }) => {
    test.setTimeout(0); // pause() espera interacción del usuario

    // --- A: T1 debe estar ocupada (del test anterior) ---
    await loginByPin(page, employees.carlos);
    await navigateToMesas(page);
    await selectZone(page, 'Terraza');

    // --- B: observador ---
    const ctxB = await browser.newContext();
    const pageB = await ctxB.newPage();
    await loginByPin(pageB, employees.laura);

    const wsEvents: string[] = [];
    pageB.on('websocket', (ws) => {
      ws.on('framereceived', ({ payload }) => {
        const msg = typeof payload === 'string' ? payload : Buffer.from(payload as Buffer).toString();
        if (msg.includes('order.status_changed')) {
          wsEvents.push(msg);
        }
      });
    });

    await navigateToMesas(pageB);
    await pageB.waitForTimeout(2000);
    await pageB.getByRole('button', { name: 'Terraza', exact: true }).click();

    // T1 debe estar ocupada (viene del test anterior)
    await expect(mesaT1(pageB)).toHaveClass(/occupied/);

    // --- A: seleccionar T1 y cerrar cuenta ---
    await mesaT1(page).click();
    await closeAccountFromMesa(page, employees.carlos);

    // --- B debe mostrar el tag COBRAR en ≤5s ---
    await expect(mesaT1(pageB).locator('.mesa-cobrar-tag')).toBeVisible({ timeout: 5000 });
    expect(wsEvents.some(e => e.includes('order.marked_to_charge'))).toBe(true);

    // --- A: cobrar la mesa (pago en efectivo) ---
    await chargeFromMesa(page, employees.carlos);
    await payCash(page);

    // --- B debe mostrar T1 como libre en ≤8s (order.invoiced via Reverb) ---
    await expect(mesaT1(pageB)).not.toHaveClass(/occupied/, { timeout: 8000 });
    expect(wsEvents.some(e => e.includes('order.invoiced'))).toBe(true);

    // Pausa para inspeccionar ambas pestañas — pulsa "Resume" en el inspector para continuar
    await page.pause();

    await ctxB.close();
  });
});
