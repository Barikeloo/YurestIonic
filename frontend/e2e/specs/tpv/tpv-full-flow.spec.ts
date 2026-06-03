import { expect, test } from '@playwright/test';
import { loginByPin } from '../../support/auth';
import { closeCashSession, openCashSession } from '../../support/cash';
import { employees } from '../../support/fixtures';
import {
  addProductToComanda,
  chargeFromMesa,
  closeAccountFromMesa,
  enterComandaForSelectedTable,
  navigateToMesas,
  openTable,
  payCash,
  readExpectedAmountCents,
  sendComanda,
} from '../../support/tpv';

const FLOAT_CENTS = 5000;

test.describe.serial('TPV full flow', () => {
  test.beforeEach(({}, testInfo) => {
    test.skip(
      testInfo.project.name !== 'stateful',
      'TPV flow tests mutate cash + orders state and run only in the stateful project',
    );
  });

  test('completes the full happy path: open table, send comanda, close account, pay cash', async ({ page }) => {
    await loginByPin(page, employees.carlos);
    await openCashSession(page, employees.carlos, FLOAT_CENTS);

    await navigateToMesas(page);

    await openTable(page, 'Terraza', 'T1', 2, employees.carlos);

    // Opening a table navigates to /app/pedidos; return to mesas to enter the comanda for T1.
    await expect(page).toHaveURL(/\/app\/pedidos/);
    await navigateToMesas(page);
    await page.getByRole('button', { name: 'Terraza', exact: true }).click();
    await page.locator('.mesa').filter({
      has: page.locator('.mesa-name', { hasText: /^T1$/ }),
    }).first().click();
    await enterComandaForSelectedTable(page);

    await addProductToComanda(page, 'Cafés y Desayunos', 'Café solo');
    await addProductToComanda(page, 'Cafés y Desayunos', 'Café solo');
    await sendComanda(page);

    // sendComanda navigates to /app/mesas automatically; pick T1 again.
    await page.waitForURL(/\/app\/mesas/);
    await expect(page.locator('.mesas-page .zone-tabs button').first()).toBeVisible();
    await page.getByRole('button', { name: 'Terraza', exact: true }).click();
    await page.locator('.mesa').filter({
      has: page.locator('.mesa-name', { hasText: /^T1$/ }),
    }).first().click();

    await closeAccountFromMesa(page, employees.carlos);

    // Mesa now has status TO_CHARGE, "Cobrar" button is shown.
    await chargeFromMesa(page, employees.carlos);
    await payCash(page);

    // Verify cash session reflects the payment (expected amount grew beyond initial float).
    await expect(page.locator('.hero-value').first()).not.toContainText('50,00');
    const expectedAfter = await readExpectedAmountCents(page);
    expect(expectedAfter).toBeGreaterThan(FLOAT_CENTS);

    // Mesa T1 is back to libre.
    await navigateToMesas(page);
    await page.getByRole('button', { name: 'Terraza', exact: true }).click();
    const t1 = page.locator('.mesa').filter({
      has: page.locator('.mesa-name', { hasText: /^T1$/ }),
    }).first();
    await expect(t1).not.toHaveClass(/occupied/);
    await expect(t1).not.toHaveClass(/cobrar/);

    // Clean up: close caja so the next spec starts pre-apertura.
    await page.goto('/app/caja');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('.hero-value').first()).not.toContainText('-');
    const expected = await readExpectedAmountCents(page);
    await closeCashSession(page, expected);
    await expect(page.locator('.status-badge')).toContainText('CERRADA');
  });
});
