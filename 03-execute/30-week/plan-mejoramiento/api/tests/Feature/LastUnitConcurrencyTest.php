<?php

namespace Tests\Feature;

use App\Application\Ports\Clock;
use App\Application\Ports\IdGenerator;
use App\Application\Ports\UserRepository;
use App\Application\Services\CreateLaptopService;
use App\Application\Services\CreateStudentService;
use App\Application\Services\LendLaptopService;
use App\Domain\Exceptions\ItemAlreadyReturned;
use App\Domain\Exceptions\LaptopNotAvailable;
use App\Domain\Laptop;
use App\Domain\Loan;
use App\Domain\Student;
use App\Domain\User;
use App\Infrastructure\Persistence\DbLaptopRepository;
use App\Infrastructure\Persistence\DbLoanRepository;
use App\Infrastructure\Persistence\DbStudentRepository;
use App\Infrastructure\Persistence\DbTransactionRunner;
use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Tests\Fakes\FixedClock;
use Tests\Support\LaptopReadHook;
use Tests\TestCase;

/**
 * RNF-2 and ADR-002 with two real PostgreSQL connections, as two operators on two PHP processes would have.
 * Nothing runs inside a test transaction: each operator must see what the other one committed, so the rows are
 * committed and removed again in tearDown (the seeded categories stay).
 */
final class LastUnitConcurrencyTest extends TestCase
{
    private const SECOND = 'pgsql_second_operator';

    private const PROGRAMMING = '33333333-3333-4333-8333-333333333333';

    private FixedClock $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--force' => true]);
        config(['database.connections.'.self::SECOND => config('database.connections.pgsql')]);
        $this->clock = new FixedClock('2026-11-16T14:15:00Z');
        $this->app->instance(Clock::class, $this->clock);
        $this->deleteCommittedRows();
    }

    protected function tearDown(): void
    {
        // Close the second operator first, so a transaction left open by a failed test cannot block the cleanup.
        DB::purge(self::SECOND);
        $this->deleteCommittedRows();

        parent::tearDown();
    }

    public function test_two_operators_lending_the_last_unit_register_exactly_one_loan(): void
    {
        $laptop = $this->laptop(units: 1);
        $first = $this->student('Camila Rojas', '1001234567');
        $second = $this->student('Andrés Pérez', '1009876543');
        $operatorA = $this->operator('auxiliar.a');
        $operatorB = $this->operator('auxiliar.b');

        $operatorBLends = fn () => $this->lendingOn(DB::connection(self::SECOND))->lend($second->id, [$laptop->id], $operatorB);
        $lendingA = $this->lendingOn(DB::connection(), afterRead: $operatorBLends);

        try {
            $lendingA->lend($first->id, [$laptop->id], $operatorA);
            $this->fail('The operator who wrote second must get LAPTOP_NOT_AVAILABLE.');
        } catch (LaptopNotAvailable $error) {
            $this->assertSame('LAPTOP_NOT_AVAILABLE', $error->errorCode());
            $this->assertSame([['field' => 'items[0].laptop_id', 'issue' => 'sin unidades']], $error->details());
        }

        $this->assertSame([$second->id], DB::table('laptops.loan')->pluck('student_id')->all());
        $this->assertSame(1, DB::table('laptops.loan_item')->count());
        $this->assertSame(0, DB::table('laptops.laptop')->where('id', $laptop->id)->value('available_units'));
    }

    public function test_the_loser_leaves_no_loan_and_the_winner_keeps_the_unit_when_both_read_one(): void
    {
        $laptop = $this->laptop(units: 1);
        $first = $this->student('Camila Rojas', '1001234567');
        $second = $this->student('Andrés Pérez', '1009876543');
        $operator = $this->operator('auxiliar.a');

        $winner = null;
        $lendingA = $this->lendingOn(DB::connection(), afterRead: function () use (&$winner, $second, $laptop, $operator) {
            $winner = $this->lendingOn(DB::connection(self::SECOND))->lend($second->id, [$laptop->id], $operator);
        });

        $this->assertThrowsLaptopNotAvailable(fn () => $lendingA->lend($first->id, [$laptop->id], $operator));

        $this->assertInstanceOf(Loan::class, $winner);
        $this->assertSame(0, DB::table('laptops.loan')->where('student_id', $first->id)->count());
        $this->assertSame($winner->id, DB::table('laptops.loan')->value('id'));
    }

    public function test_two_operators_returning_the_same_item_add_one_unit(): void
    {
        $laptop = $this->laptop(units: 2);
        $student = $this->student('Camila Rojas', '1001234567');
        $loan = $this->lendingOn(DB::connection())->lend($student->id, [$laptop->id], $this->operator('auxiliar.a'));
        $itemId = $loan->items()[0]->id;
        $now = new DateTimeImmutable('2026-11-20T10:00:00Z');

        // Both operators open the same loan before either of them saves the return.
        [$loansA, $laptopsA, $runnerA] = $this->adaptersOn(DB::connection());
        [$loansB, $laptopsB, $runnerB] = $this->adaptersOn(DB::connection(self::SECOND));
        $loanA = $loansA->find($loan->id);
        $laptopA = $laptopsA->findIncludingRetired($laptop->id);
        $loanB = $loansB->find($loan->id);
        $laptopB = $laptopsB->findIncludingRetired($laptop->id);
        $loanA->returnItem($itemId, $laptopA, $now);
        $loanB->returnItem($itemId, $laptopB, $now);

        $runnerB->run(fn () => $loansB->saveReturn($loanB, $itemId, $laptopB));

        $this->expectException(ItemAlreadyReturned::class);
        try {
            $runnerA->run(fn () => $loansA->saveReturn($loanA, $itemId, $laptopA));
        } finally {
            $this->assertSame(2, DB::table('laptops.laptop')->where('id', $laptop->id)->value('available_units'));
        }
    }

    private function lendingOn(ConnectionInterface $connection, ?callable $afterRead = null): LendLaptopService
    {
        [$loans, $laptops, $transactions] = $this->adaptersOn($connection);

        return $this->app->make(LendLaptopService::class, [
            'students' => $this->app->make(DbStudentRepository::class, ['connection' => $connection]),
            'laptops' => $afterRead === null ? $laptops : new LaptopReadHook($laptops, $afterRead),
            'loans' => $loans,
            'transactions' => $transactions,
        ]);
    }

    /** @return array{0: DbLoanRepository, 1: DbLaptopRepository, 2: DbTransactionRunner} one operator's request */
    private function adaptersOn(ConnectionInterface $connection): array
    {
        $laptops = $this->app->make(DbLaptopRepository::class, ['connection' => $connection]);

        return [
            $this->app->make(DbLoanRepository::class, ['connection' => $connection, 'laptops' => $laptops]),
            $laptops,
            $this->app->make(DbTransactionRunner::class, ['connection' => $connection]),
        ];
    }

    private function laptop(int $units): Laptop
    {
        return $this->app->make(CreateLaptopService::class)->create('ThinkPad E14 Gen 5', 'Lenovo', 'TP-E14-G5', self::PROGRAMMING, $units);
    }

    private function student(string $name, string $document): Student
    {
        return $this->app->make(CreateStudentService::class)->create($name, $document);
    }

    private function operator(string $username): string
    {
        $user = User::createAssistant($this->app->make(IdGenerator::class)->next(), $username, 'not-a-real-hash', 'assistant');
        $this->app->make(UserRepository::class)->add($user);

        return $user->id;
    }

    private function assertThrowsLaptopNotAvailable(callable $action): void
    {
        try {
            $action();
        } catch (LaptopNotAvailable) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail('Expected LaptopNotAvailable.');
    }

    private function deleteCommittedRows(): void
    {
        foreach (['loan_item', 'loan', 'laptop', 'student', 'user'] as $table) {
            DB::table("laptops.{$table}")->delete();
        }
    }
}
