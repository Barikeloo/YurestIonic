import { expect, test } from '@playwright/test';
import { loginAsAdmin } from '../../support/auth';
import { seedAndArchiveRetentionDemo } from '../../support/audit';

test.describe.serial('Histórico panel', () => {
  test.beforeAll(({}, testInfo) => {
    test.skip(
      testInfo.project.name !== 'stateful',
      'Histórico tests mutate audit_logs state and only run in the stateful project',
    );
    seedAndArchiveRetentionDemo();
  });

  test.beforeEach(({}, testInfo) => {
    test.skip(
      testInfo.project.name !== 'stateful',
      'Histórico tests mutate audit_logs state and only run in the stateful project',
    );
  });

  test('renders the KPI cards and monthly chart for the archived corpus', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/registro-auditoria/historico');
    await page.waitForLoadState('networkidle');

    await expect(page.getByRole('heading', { name: /Histórico de Auditoría/i })).toBeVisible();

    // 4 KPI cards expected with non-zero values.
    const cards = page.locator('.kpi-card');
    await expect(cards).toHaveCount(4);

    // Hero card (Total archivado) shows >= 40 (the seeder inserts 40).
    const heroValue = await page.locator('.kpi-primary .kpi-value').first().textContent();
    const heroNumber = Number((heroValue ?? '').replace(/\D/g, ''));
    expect(heroNumber).toBeGreaterThanOrEqual(40);

    // Monthly chart has at least one bar with a count badge.
    const bars = page.locator('.chart-body .bar-fill');
    await expect(bars.first()).toBeVisible();
    expect(await bars.count()).toBeGreaterThan(0);
  });

  test('applying a preset shows the banner, marks the trigger active, and clears with Quitar', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/registro-auditoria/historico');
    await page.waitForLoadState('networkidle');

    // No active filter initially.
    await expect(page.locator('.active-range-banner')).toHaveCount(0);

    // Open the dropdown and apply "Último año" — a window wide enough that
    // the panel will still have data (avoid depending on whether the
    // current preset narrows the corpus to zero).
    await page.getByRole('button', { name: /Rango temporal/i }).click();
    await page.locator('.range-menu').getByRole('button', { name: /Último año/i }).click();
    await page.waitForLoadState('networkidle');

    // Banner appears with the preset label and the trigger goes red.
    await expect(page.locator('.active-range-banner')).toBeVisible();
    await expect(page.locator('.active-range-banner')).toContainText(/Último año/i);
    await expect(page.locator('.range-dropdown .btn-ghost.active')).toBeVisible();

    // Quitar removes the banner and resets the trigger.
    await page.locator('.active-range-banner .clear-range-btn').click();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('.active-range-banner')).toHaveCount(0);
    await expect(page.locator('.range-dropdown .btn-ghost.active')).toHaveCount(0);
  });

  test('applying Último mes with no recent archives surfaces the empty state', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/registro-auditoria/historico');
    await page.waitForLoadState('networkidle');

    await page.getByRole('button', { name: /Rango temporal/i }).click();
    await page.locator('.range-menu').getByRole('button', { name: /Último mes/i }).click();
    await page.waitForLoadState('networkidle');

    // All retention demo rows are > 90 days old (they had to be, to qualify
    // for archival). The Último mes window therefore produces zero archived
    // rows and the panel must switch to its empty state.
    await expect(page.locator('.empty-state')).toBeVisible();
    await expect(page.locator('.empty-state .empty-title')).toContainText(/no hay nada/i);
  });

  test('clicking a category row navigates to the filtered registry', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/registro-auditoria/historico');
    await page.waitForLoadState('networkidle');

    // Find the first category button and click it.
    const catBtn = page.locator('.cat-btn').first();
    await expect(catBtn).toBeVisible();

    await Promise.all([
      page.waitForURL(/registro-auditoria\?historico=1.*category=/),
      catBtn.click(),
    ]);

    // Confirm we landed on the registry page.
    await expect(page).toHaveURL(/registro-auditoria/);
  });

  test('clicking a user row navigates to the filtered registry', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/registro-auditoria/historico');
    await page.waitForLoadState('networkidle');

    // Find the first user button and click it.
    const userBtn = page.locator('.user-btn').first();
    await expect(userBtn).toBeVisible();

    await Promise.all([
      page.waitForURL(/registro-auditoria\?historico=1.*userId=/),
      userBtn.click(),
    ]);

    // Confirm we landed on the registry page.
    await expect(page).toHaveURL(/registro-auditoria/);
  });

  test('exporting CSV triggers a download with the expected filename pattern', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/registro-auditoria/historico');
    await page.waitForLoadState('networkidle');

    await page.getByRole('button', { name: /^\s*Exportar\s*$/i }).click();
    await expect(page.locator('.export-menu')).toBeVisible();

    const [download] = await Promise.all([
      page.waitForEvent('download'),
      page.locator('.export-menu a:has-text("CSV")').first().click(),
    ]);

    expect(download.suggestedFilename()).toMatch(/^audit-log-\d{4}-\d{2}-\d{2}-\d{6}\.csv$/);

    const path = await download.path();
    expect(path).toBeTruthy();
    // The CSV contains at least the BOM (3 bytes) + header line, so it must
    // be larger than a trivial empty file.
    const { statSync } = await import('node:fs');
    expect(statSync(path!).size).toBeGreaterThan(100);
  });

  test('chain verification card becomes visible and clicking run-verify changes the state', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/registro-auditoria/historico');
    await page.waitForLoadState('networkidle');

    // The verify card is visible.
    const verifyCard = page.locator('.verify-card');
    await expect(verifyCard).toBeVisible();

    // If the card is already in a terminal state (from a previous test
    // in this serial run), just check "Verificar de nuevo" is visible.
    const currentState = await verifyCard.getAttribute('data-state');
    if (currentState !== 'idle') {
      await expect(verifyCard.getByRole('button', { name: /Verificar de nuevo/i })).toBeVisible();
      return;
    }

    // Click the "Verificar cadena" button.
    await verifyCard.getByRole('button', { name: /Verificar cadena/i }).click();

    // Wait for the state to leave idle (loading state appears briefly then resolves).
    await expect(verifyCard).not.toHaveAttribute('data-state', 'idle', { timeout: 15_000 });

    // After verify, the button text changes to "Verificar de nuevo".
    await expect(verifyCard.getByRole('button', { name: /Verificar de nuevo/i })).toBeVisible({ timeout: 10_000 });
  });

  test('deep-link from histórico activates the toggle on the live registry', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/registro-auditoria/historico');
    await page.waitForLoadState('networkidle');

    await page.getByRole('button', { name: /Abrir registro/i }).first().click();
    await page.waitForURL(/registro-auditoria\?historico=1/);

    // The Mostrar histórico toggle must be in its active state.
    await expect(page.locator('.include-archived-toggle .toggle-switch.active')).toBeVisible();
  });
});
