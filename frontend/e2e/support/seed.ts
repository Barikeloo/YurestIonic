import { execSync } from 'node:child_process';
import { resolve } from 'node:path';

const projectRoot = resolve(__dirname, '../../..');

export function runSaonaDemoSeed(verbose: boolean = false): void {
  const composeService = process.env.E2E_API_SERVICE ?? 'api';
  const seederClass = process.env.E2E_SEEDER_CLASS ?? 'SaonaDemoSeeder';

  try {
    execSync(
      `docker compose exec -T ${composeService} php artisan db:seed --class=${seederClass} --no-interaction`,
      {
        cwd: projectRoot,
        stdio: verbose || process.env.E2E_VERBOSE ? 'inherit' : 'pipe',
        timeout: 180_000,
      },
    );
  } catch (err) {
    const stderr = err instanceof Error && 'stderr' in err ? String((err as { stderr: Buffer }).stderr) : '';
    const stdout = err instanceof Error && 'stdout' in err ? String((err as { stdout: Buffer }).stdout) : '';
    throw new Error(`[e2e] failed to seed ${seederClass}.\nstdout: ${stdout}\nstderr: ${stderr}`);
  }
}
