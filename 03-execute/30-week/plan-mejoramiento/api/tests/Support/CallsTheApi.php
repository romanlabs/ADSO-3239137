<?php

namespace Tests\Support;

use App\Application\Ports\Clock;
use App\Application\Services\ProvisionAdminService;
use Illuminate\Testing\TestResponse;
use Tests\Fakes\FixedClock;

/**
 * Helpers for tests that go through HTTP with the real composition root and PostgreSQL. Only the clock is fixed,
 * so a test can move time to make items overdue.
 */
trait CallsTheApi
{
    protected const ADMIN_USERNAME = 'admin';

    protected const ADMIN_PASSWORD = 'admin-password-only-for-tests';

    protected const ASSISTANT_PASSWORD = 'assistant-password-for-tests';

    protected const PROGRAMMING = '33333333-3333-4333-8333-333333333333';

    protected const DESIGN = '22222222-2222-4222-8222-222222222222';

    protected FixedClock $clock;

    protected function startApi(string $now = '2026-11-16T14:15:00Z'): void
    {
        $this->clock = new FixedClock($now);
        $this->app->instance(Clock::class, $this->clock);
        $this->app->make(ProvisionAdminService::class)->provision(self::ADMIN_USERNAME, self::ADMIN_PASSWORD);
    }

    /**
     * Every HTTP request of the running system is a new process, so request-scoped adapters (ADR-002) start empty.
     * Inside one test they are forgotten before each call to behave the same way.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app->forgetScopedInstances();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    protected function tokenFor(string $username, string $password): string
    {
        return $this->postJson('/api/v1/auth/login', ['username' => $username, 'password' => $password])
            ->assertOk()
            ->json('access_token');
    }

    protected function adminToken(): string
    {
        return $this->tokenFor(self::ADMIN_USERNAME, self::ADMIN_PASSWORD);
    }

    protected function assistantToken(string $username = 'camila'): string
    {
        $this->send($this->adminToken(), 'POST', '/users', [
            'username' => $username, 'password' => self::ASSISTANT_PASSWORD, 'role' => 'assistant',
        ])->assertCreated();

        return $this->tokenFor($username, self::ASSISTANT_PASSWORD);
    }

    /** @param array<string, mixed> $body */
    protected function send(string $token, string $method, string $path, array $body = []): TestResponse
    {
        return $this->json($method, "/api/v1{$path}", $body, ["Authorization" => "Bearer {$token}"]);
    }

    protected function createLaptop(string $token, string $modelCode, int $totalUnits = 3, string $name = 'ThinkPad E14 Gen 5'): string
    {
        return $this->send($token, 'POST', '/laptops', [
            'name' => $name, 'brand' => 'Lenovo', 'model_code' => $modelCode,
            'category_id' => self::PROGRAMMING, 'total_units' => $totalUnits,
        ])->assertCreated()->json('id');
    }

    protected function createStudent(string $token, string $documentNumber, string $fullName = 'Laura Gómez'): string
    {
        return $this->send($token, 'POST', '/students', ['full_name' => $fullName, 'document_number' => $documentNumber])
            ->assertCreated()
            ->json('id');
    }

    protected function lend(string $token, string $studentId, string ...$laptopIds): TestResponse
    {
        return $this->send($token, 'POST', '/loans', [
            'student_id' => $studentId,
            'items' => array_map(fn (string $id) => ['laptop_id' => $id], $laptopIds),
        ]);
    }

    protected function assertErrorBody(TestResponse $response, int $status, string $code): void
    {
        $response->assertStatus($status)->assertJsonPath('code', $code);
        $this->assertSame(['code', 'message', 'details'], array_keys($response->json()));
        $this->assertIsString($response->json('message'));
        $this->assertNotSame('', $response->json('message'));
        $this->assertIsArray($response->json('details'));
    }
}
