import { expect, test } from '@playwright/test';
import { loginAsAdmin } from '../../support/auth';

// These tests create / edit / delete real scheduled_reports rows, so they run
// in the serial "stateful" project and clean up after themselves. The table
// only renders the report-type label (not the name), so rows are anchored by
// the unique recipient email this suite uses.
const RECIPIENT = `e2e-${Date.now()}@yurest.test`;

test.describe.serial('Finanzas · Informes programados', () => {
  test.beforeEach(async ({ page }, testInfo) => {
    test.skip(
      testInfo.project.name !== 'stateful',
      'Scheduled report tests mutate state and only run in the stateful project',
    );

    await loginAsAdmin(page);
    await page.goto('/finanzas');
    await page.waitForLoadState('networkidle');

    await page.locator('.tab-btn', { hasText: 'Informes' }).click();
    await page.locator('.seg-btn', { hasText: 'Programados' }).click();
    await expect(page.getByRole('button', { name: /\+ Nueva programación/i })).toBeVisible();
  });

  test('creates a scheduled report through the modal', async ({ page }) => {
    await page.getByRole('button', { name: /\+ Nueva programación/i }).click();

    const modal = page.locator('.preview-modal');
    await expect(modal.locator('.preview-title')).toHaveText('Nueva programación');

    await modal.locator('input[placeholder="Resumen diario"]').fill('E2E daily report');
    await modal.locator('input[type="time"]').fill('07:30');
    await modal.locator('input[type="email"]').first().fill(RECIPIENT);

    await Promise.all([
      page.waitForResponse((r) => r.url().includes('/admin/reports/scheduled') && r.request().method() === 'POST' && r.ok()),
      modal.getByRole('button', { name: /^Crear$/ }).click(),
    ]);

    const row = page.locator('.data-table tbody tr', { hasText: RECIPIENT });
    await expect(row).toBeVisible();
    await expect(row.locator('.toggle--on')).toBeVisible();
    await expect(row).toContainText('Resumen diario');
  });

  test('toggles the report to inactive', async ({ page }) => {
    const row = page.locator('.data-table tbody tr', { hasText: RECIPIENT });
    await expect(row).toBeVisible();

    await Promise.all([
      page.waitForResponse((r) => /\/admin\/reports\/scheduled\/.+\/toggle/.test(r.url()) && r.ok()),
      row.locator('.toggle').click(),
    ]);

    await expect(row).toHaveClass(/row-inactive/);
  });

  test('edits the report type', async ({ page }) => {
    const row = page.locator('.data-table tbody tr', { hasText: RECIPIENT });
    await row.locator('.row-actions button').first().click(); // pencil

    const modal = page.locator('.preview-modal');
    await expect(modal.locator('.preview-title')).toHaveText('Editar programación');

    await modal.locator('select').first().selectOption('products');

    await Promise.all([
      page.waitForResponse((r) => /\/admin\/reports\/scheduled\/[^/]+$/.test(r.url()) && r.request().method() === 'PUT' && r.ok()),
      modal.getByRole('button', { name: /^Guardar$/ }).click(),
    ]);

    await expect(row).toContainText('Ventas por producto');
  });

  test('sends the report now', async ({ page }) => {
    const row = page.locator('.data-table tbody tr', { hasText: RECIPIENT });
    const sendBtn = row.locator('.row-actions button').nth(1); // download icon

    const [response] = await Promise.all([
      page.waitForResponse((r) => /\/admin\/reports\/scheduled\/.+\/send/.test(r.url())),
      sendBtn.click(),
    ]);

    expect(response.ok()).toBeTruthy();
  });

  test('deletes the report', async ({ page }) => {
    page.on('dialog', (dialog) => dialog.accept());

    const row = page.locator('.data-table tbody tr', { hasText: RECIPIENT });
    const deleteBtn = row.locator('.row-actions button').nth(2); // x icon

    await Promise.all([
      page.waitForResponse((r) => r.url().includes('/admin/reports/scheduled') && r.request().method() === 'DELETE' && r.ok()),
      deleteBtn.click(),
    ]);

    await expect(page.locator('.data-table tbody tr', { hasText: RECIPIENT })).toHaveCount(0);
  });
});
