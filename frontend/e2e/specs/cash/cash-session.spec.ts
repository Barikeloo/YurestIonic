import { expect, test } from '@playwright/test';
import { loginByPin } from '../../support/auth';
import { closeCashSession, openCashSession, registerCashMovement } from '../../support/cash';
import { employees } from '../../support/fixtures';

const FLOAT_CENTS = 5000;
const MOVEMENT_CENTS = 2000;

test.describe.serial('cash session', () => {
  test.beforeEach(({}, testInfo) => {
    test.skip(
      testInfo.project.name !== 'chromium',
      'Cash session tests mutate the shared cash_sessions row and run only on chromium',
    );
  });

  test('opens a session with PIN re-auth and closes it back with no discrepancy', async ({ page }) => {
    await loginByPin(page, employees.carlos);
    await expect(page).toHaveURL(/\/app\/caja$/);

    await openCashSession(page, employees.carlos, FLOAT_CENTS);

    await expect(page.locator('.hero-value')).toContainText('50');
    await expect(page.locator('.hero-sub')).toContainText('Fondo: 50,00');

    await closeCashSession(page, FLOAT_CENTS);

    await expect(page.locator('.status-badge')).toContainText('CERRADA');
    await expect(page.locator('.historico')).toBeVisible();
    await expect(page.locator('.session-row').first()).toBeVisible();
  });

  test('records a manual income movement during an active session', async ({ page }) => {
    await loginByPin(page, employees.carlos);
    await openCashSession(page, employees.carlos, FLOAT_CENTS);

    await registerCashMovement(page, MOVEMENT_CENTS);

    await expect(page.locator('app-movimientos-list .movement-row').first()).toBeVisible();
    await expect(page.locator('app-movimientos-list .movement-amount.in').first()).toContainText('+20');
    await expect(page.locator('.hero-sub')).toContainText('+20,00 entradas');

    await closeCashSession(page, FLOAT_CENTS + MOVEMENT_CENTS);
    await expect(page.locator('.status-badge')).toContainText('CERRADA');
  });

  test('starts a closing flow and cancels it, returning to the active state', async ({ page }) => {
    await loginByPin(page, employees.carlos);
    await openCashSession(page, employees.carlos, FLOAT_CENTS);

    await page.getByRole('button', { name: /cerrar caja/i }).click();
    const wizard = page.locator('.wizard-overlay');
    await expect(wizard).toBeVisible();

    await wizard.getByRole('button', { name: /cancelar/i }).click();

    await expect(wizard).toBeHidden();
    await expect(page.locator('.status-badge')).toContainText('ABIERTA');

    await closeCashSession(page, FLOAT_CENTS);
  });
});
