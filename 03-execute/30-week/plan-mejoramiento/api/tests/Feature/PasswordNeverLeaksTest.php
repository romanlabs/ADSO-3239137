<?php

namespace Tests\Feature;

use App\Application\Ports\Clock;
use App\Application\Services\CreateAssistantService;
use App\Application\Services\LoginService;
use App\Domain\Exceptions\InvalidCredentials;
use App\Domain\Exceptions\UsernameTaken;
use App\Infrastructure\Security\JwtTokenIssuer;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\Fakes\FixedClock;
use Tests\Support\CallsTheApi;
use Tests\TestCase;
use Throwable;

/**
 * RNF-3 and constitution article IX with the real Argon2id hasher, the real JWT issuer and PostgreSQL: the plain
 * password never reaches the database, a log line, a stack trace, an error message or anything handed back to a caller.
 * The last test checks the same through the HTTP responses (T-16).
 */
final class PasswordNeverLeaksTest extends TestCase
{
    use CallsTheApi;
    use RefreshDatabase;

    /** Short enough that a PHP stack trace would print it whole instead of cutting it at 15 characters. */
    private const PASSWORD = 'Tr0ub4dor&3xyz';

    private string $logFile;

    /** @var list<string> */
    private array $boundValues = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->logFile = tempnam(sys_get_temp_dir(), 'leak-check-');
        config([
            'logging.channels.leak_check' => ['driver' => 'single', 'path' => $this->logFile, 'level' => 'debug'],
            'logging.default' => 'leak_check',
        ]);
        $this->app->instance(Clock::class, new FixedClock('2026-11-16T14:15:00Z'));

        DB::listen(function (QueryExecuted $query) {
            foreach ($query->bindings as $value) {
                $this->boundValues[] = (string) $value;
            }
        });
    }

    protected function tearDown(): void
    {
        @unlink($this->logFile);

        parent::tearDown();
    }

    public function test_the_stored_value_is_an_argon2id_hash_that_verifies(): void
    {
        $this->assistants()->create('camila', self::PASSWORD, 'assistant');

        $hash = DB::table('laptops.user')->where('username', 'camila')->value('password_hash');

        $this->assertStringStartsWith('$argon2id$', $hash);
        $this->assertNotSame(self::PASSWORD, $hash);
        $this->assertTrue(password_verify(self::PASSWORD, $hash));
        $this->assertNotContains(self::PASSWORD, $this->boundValues);
    }

    public function test_the_token_carries_only_the_user_id_the_role_and_the_times(): void
    {
        $user = $this->assistants()->create('camila', self::PASSWORD, 'assistant');

        $result = $this->login()->login('camila', self::PASSWORD);
        [, $payload] = explode('.', $result->token->accessToken);
        $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

        $this->assertSame(['sub', 'role', 'iat', 'exp'], array_keys($claims));
        $this->assertSame([$user->id, 'assistant', 8 * 3600], [$claims['sub'], $claims['role'], $claims['exp'] - $claims['iat']]);
        $this->assertSame(['sub' => $user->id, 'role' => 'assistant'], $this->app->make(JwtTokenIssuer::class)->claimsOf($result->token->accessToken));
        $this->assertSecretsAbsentFrom(serialize($result));
    }

    public function test_a_tampered_or_expired_token_is_rejected(): void
    {
        $this->assistants()->create('camila', self::PASSWORD, 'assistant');
        $token = $this->login()->login('camila', self::PASSWORD)->token->accessToken;
        $issuer = $this->app->make(JwtTokenIssuer::class);

        $this->assertNull($issuer->claimsOf($token.'x'));

        $this->app->instance(Clock::class, new FixedClock('2026-11-16T22:16:00Z'));
        $this->assertNull($this->app->make(JwtTokenIssuer::class)->claimsOf($token));
    }

    public function test_failures_reported_to_the_log_never_contain_the_password_or_its_hash(): void
    {
        $this->assistants()->create('camila', self::PASSWORD, 'assistant');
        $failures = [
            fn () => $this->assistants()->create('camila', self::PASSWORD, 'assistant'),
            fn () => $this->login()->login('camila', self::PASSWORD.'!'),
            fn () => $this->login()->login('nadie', self::PASSWORD),
        ];

        foreach ($failures as $failure) {
            try {
                $failure();
                $this->fail('Each attempt must fail.');
            } catch (UsernameTaken|InvalidCredentials $error) {
                $this->assertSecretsAbsentFrom($error->getMessage().json_encode($error->details()));
                // Business errors are not reported (bootstrap/app.php), so wrap it like an unexpected failure would be.
                report(new LogicException('Unexpected failure', 0, $error));
            }
        }

        $log = (string) file_get_contents($this->logFile);
        $this->assertStringContainsString('UsernameTaken', $log, 'The stack traces must really be in the log.');
        $this->assertSecretsAbsentFrom($log);
        $this->assertNotContains(self::PASSWORD, $this->boundValues);
    }

    public function test_no_http_response_carries_the_password_or_its_hash(): void
    {
        $this->startApi();
        $admin = $this->adminToken();
        $body = ['username' => 'camila', 'password' => self::PASSWORD, 'role' => 'assistant'];

        $responses = [
            $this->send($admin, 'POST', '/users', $body)->assertCreated(),
            $this->send($admin, 'POST', '/users', $body)->assertStatus(409),
            $this->send($admin, 'POST', '/users', ['role' => 'admin'] + $body)->assertStatus(422),
            $this->postJson('/api/v1/auth/login', ['username' => 'camila', 'password' => self::PASSWORD])->assertOk(),
            $this->postJson('/api/v1/auth/login', ['username' => 'camila', 'password' => self::PASSWORD.'!'])->assertStatus(401),
        ];

        foreach ($responses as $response) {
            $this->assertSecretsAbsentFrom($response->getContent());
            $this->assertStringNotContainsString('password', $response->getContent());
        }
        $this->assertSecretsAbsentFrom((string) file_get_contents($this->logFile));
    }

    private function assertSecretsAbsentFrom(string $text): void
    {
        $this->assertStringNotContainsString(self::PASSWORD, $text);

        foreach (DB::table('laptops.user')->pluck('password_hash') as $hash) {
            $this->assertStringNotContainsString($hash, $text);
        }
    }

    private function assistants(): CreateAssistantService
    {
        return $this->app->make(CreateAssistantService::class);
    }

    private function login(): LoginService
    {
        return $this->app->make(LoginService::class);
    }
}
