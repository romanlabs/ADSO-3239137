<?php

namespace App\Providers;

use App\Application\Ports\CategoryRepository;
use App\Application\Ports\Clock;
use App\Application\Ports\IdGenerator;
use App\Application\Ports\LaptopRepository;
use App\Application\Ports\StudentRepository;
use App\Application\Ports\TransactionRunner;
use App\Application\Ports\UserRepository;
use App\Infrastructure\Persistence\DbCategoryRepository;
use App\Infrastructure\Persistence\DbLaptopRepository;
use App\Infrastructure\Persistence\DbStudentRepository;
use App\Infrastructure\Persistence\DbTransactionRunner;
use App\Infrastructure\Persistence\DbUserRepository;
use App\Infrastructure\Persistence\RandomUuidGenerator;
use App\Infrastructure\Time\SystemClock;
use Illuminate\Support\ServiceProvider;

/**
 * The only composition root (constitution, article III): every port meets its adapter here and nowhere else.
 */
final class PortBindingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Scoped: the laptop adapter remembers the units it read (ADR-002), so it lives exactly one request.
        $this->app->scoped(DbLaptopRepository::class);
        $this->app->bind(LaptopRepository::class, DbLaptopRepository::class);

        $this->app->bind(CategoryRepository::class, DbCategoryRepository::class);
        $this->app->bind(StudentRepository::class, DbStudentRepository::class);
        $this->app->bind(UserRepository::class, DbUserRepository::class);
        $this->app->bind(TransactionRunner::class, DbTransactionRunner::class);
        $this->app->singleton(IdGenerator::class, RandomUuidGenerator::class);
        $this->app->singleton(Clock::class, fn () => new SystemClock((int) config('laptops.clock_offset_days')));
    }
}
