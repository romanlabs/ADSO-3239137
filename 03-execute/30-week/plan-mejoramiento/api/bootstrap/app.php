<?php

use App\Domain\Exceptions\DomainError;
use App\Infrastructure\Console\BootstrapAdminCommand;
use App\Infrastructure\Http\ErrorRenderer;
use App\Infrastructure\Http\Middleware\Authenticate;
use App\Infrastructure\Http\Middleware\RequireRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: 'api/v1',
    )
    ->withCommands([BootstrapAdminCommand::class])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.token' => Authenticate::class,
            'role' => RequireRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Business errors are expected answers, not incidents: they are not written to the log.
        $exceptions->dontReport(DomainError::class);

        // Contract §1: one error body for everything, rendered by a single class.
        $exceptions->render(fn (Throwable $error) => app(ErrorRenderer::class)->render($error));
    })->create();
