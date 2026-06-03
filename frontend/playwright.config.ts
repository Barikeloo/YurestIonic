import { defineConfig, devices } from '@playwright/test';
import type { PlaywrightTestConfig } from '@playwright/test';

const port = Number(process.env.E2E_PORT ?? 4200);
const baseURL = process.env.E2E_BASE_URL ?? `http://localhost:${port}`;
const chromiumExecutablePath = process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH ?? process.env.CHROME_BIN;

type UseOptions = NonNullable<PlaywrightTestConfig['use']>;

const trace = (process.env.E2E_TRACE as UseOptions['trace']) ?? 'on-first-retry';
const screenshot = (process.env.E2E_SCREENSHOT as UseOptions['screenshot']) ?? 'only-on-failure';
const video = (process.env.E2E_VIDEO as UseOptions['video']) ?? 'retain-on-failure';

export default defineConfig({
  testDir: './e2e/specs',
  globalSetup: require.resolve('./e2e/support/global-setup.ts'),
  fullyParallel: true,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [['list'], ['html', { outputFolder: 'e2e/reports/html', open: 'never' }]],
  outputDir: 'e2e/reports/artifacts',
  use: {
    baseURL,
    trace,
    screenshot,
    video,
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
