<?php

namespace Tests\Support;

use App\Application\Ports\Clock;
use App\Application\Ports\IdGenerator;
use App\Application\Ports\UserRepository;
use App\Application\Services\CreateLaptopService;
use App\Application\Services\CreateStudentService;
use App\Application\Services\LendLaptopService;
use App\Application\Services\ListLoansService;
use App\Application\Services\MostRequestedReportService;
use App\Application\Services\OverdueService;
use App\Application\Services\ReturnItemService;
use App\Application\Services\UpdateLaptopService;
use App\Domain\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\DB;
use Tests\Fakes\FixedClock;

/**
 * Same surface as InMemoryApplication, but every service comes from the real composition root and talks to
 * PostgreSQL. Only the clock is replaced, so the scenarios can move time.
 */
final class PostgresApplication
{
    public readonly FixedClock $clock;

    public readonly string $operatorId;

    public function __construct(private Container $container, string $now = '2026-11-16T14:15:00Z')
    {
        $this->clock = new FixedClock($now);
        $container->instance(Clock::class, $this->clock);

        // loan.loaned_by_user_id is a foreign key, so the operator must exist.
        $operator = User::createAssistant($container->make(IdGenerator::class)->next(), 'operator', 'not-a-real-hash', 'assistant');
        $container->make(UserRepository::class)->add($operator);
        $this->operatorId = $operator->id;
    }

    public function renameCategory(string $id, string $name): void
    {
        DB::table('laptops.category')->where('id', $id)->update(['name' => $name]);
    }

    public function createLaptop(): CreateLaptopService
    {
        return $this->container->make(CreateLaptopService::class);
    }

    public function updateLaptop(): UpdateLaptopService
    {
        return $this->container->make(UpdateLaptopService::class);
    }

    public function createStudent(): CreateStudentService
    {
        return $this->container->make(CreateStudentService::class);
    }

    public function lend(): LendLaptopService
    {
        return $this->container->make(LendLaptopService::class);
    }

    public function returnItem(): ReturnItemService
    {
        return $this->container->make(ReturnItemService::class);
    }

    public function overdue(): OverdueService
    {
        return $this->container->make(OverdueService::class);
    }

    public function listLoans(): ListLoansService
    {
        return $this->container->make(ListLoansService::class);
    }

    public function mostRequested(): MostRequestedReportService
    {
        return $this->container->make(MostRequestedReportService::class);
    }
}
