import { defineConfig, devices } from '@playwright/test';

const port = Number(process.env.E2E_PORT ?? 4200);
const baseURL = process.env.E2E_BASE_URL ?? `http://127.0.0.1:${port}`;
const chromiumExecutablePath = process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH ?? process.env.CHROME_BIN;

export default defineConfig({
  testDir: './e2e/specs',
  fullyParallel: true,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [['list'], ['html', { outputFolder: 'e2e/reports/html', open: 'never' }]],
  outputDir: 'e2e/reports/artifacts',
  use: {
    baseURL,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    launchOptions: chromiumExecutablePath ? { executablePath: chromiumExecutablePath } : undefined,
  },
  webServer: process.env.E2E_SKIP_WEB_SERVER
    ? undefined
    : {
        command: `npx ng serve --host 127.0.0.1 --port ${port}`,
        url: baseURL,
        reuseExistingServer: !process.env.CI,
        timeout: 120_000,
      },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'mobile-chrome',
      use: { ...devices['Pixel 7'] },
    },
  ],
});
