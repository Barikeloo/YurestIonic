import { execSync } from 'node:child_process';
import { resolve } from 'node:path';

const projectRoot = resolve(__dirname, '../../..');
const composeService = process.env.E2E_API_SERVICE ?? 'api';

function runArtisan(args: string, label: string): string {
  try {
    return execSync(`docker compose exec -T ${composeService} php artisan ${args}`, {
      cwd: projectRoot,
      stdio: 'pipe',
      timeout: 120_000,
    }).toString();
  } catch (err) {
    const stderr = err instanceof Error && 'stderr' in err ? String((err as { stderr: Buffer }).stderr) : '';
    const stdout = err instanceof Error && 'stdout' in err ? String((err as { stdout: Buffer }).stdout) : '';
    throw new Error(`[e2e] ${label} failed.\nstdout: ${stdout}\nstderr: ${stderr}`);
  }
}

/**
 * Seeds Bar Manolo with a backdated audit-log corpus and runs the
 * archive command to move the > 90 days rows into the histórico.
 *
 * After this helper resolves the DB is in a state where:
 *  - 40 archived audit rows exist for the demo restaurant.
 *  - 5 recent (live) rows are still on the active surface.
 *  - The audit.archived meta-event has been recorded.
 *
 * Idempotent thanks to RetentionDemoSeeder's own wipe step — calling
 * this helper twice produces the same state.
 */
export function seedAndArchiveRetentionDemo(): void {
  runArtisan('db:seed --class=RetentionDemoSeeder --no-interaction', 'retention demo seed');
  runArtisan('audit:archive-old --older-than-days=90 --no-interaction', 'audit:archive-old');
  // Drop the cached archived-stats so the next API call sees the new state.
  runArtisan('cache:clear --no-interaction', 'cache:clear');
}
