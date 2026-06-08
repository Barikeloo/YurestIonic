<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Audit/Infrastructure/Entrypoint/Console',
        __DIR__.'/../app/Product/Infrastructure/Entrypoint/Console',
    ])
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('audit:archive-old')
            ->weekly()
            ->mondays()
            ->at('02:00')
            ->withoutOverlapping(60);

        $schedule->command('product-photos:delete-expired-tokens')
            ->hourly();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
