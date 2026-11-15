<?php

namespace Tests\Unit\Application;

use App\Application\Services\CreateAssistantService;
use App\Application\Services\LoginService;
use App\Domain\Exceptions\InvalidCredentials;
use App\Domain\Exceptions\UsernameTaken;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\Role;
use App\Domain\User;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakePasswordHasher;
use Tests\Fakes\FakeTokenIssuer;
use Tests\Fakes\InMemoryUserRepository;
use Tests\Fakes\SequentialIdGenerator;

final class AuthServicesTest extends TestCase
{
    private InMemoryUserRepository $users;

    private FakePasswordHasher $hasher;

    private LoginService $login;

    private CreateAssistantService $createAssistant;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository;
        $this->hasher = new FakePasswordHasher;
        $this->login = new LoginService($this->users, $this->hasher, new FakeTokenIssuer);
        $this->createAssistant = new CreateAssistantService($this->users, $this->hasher, new SequentialIdGenerator);

        $this->users->add(User::provisionAdmin('admin-id', 'admin', $this->hasher->hash('admin-secret-1')));
    }

    public function test_valid_credentials_return_an_eight_hour_token_and_the_role(): void
    {
        $result = $this->login->login(' Admin ', 'admin-secret-1');

        $this->assertSame('token-for:admin-id:admin', $result->token->accessToken);
        $this->assertSame(28800, $result->token->expiresIn);
        $this->assertSame(Role::Admin, $result->role);
    }

    public function test_wrong_password_and_unknown_user_fail_with_the_same_error(): void
    {
        $wrongPassword = $this->captureLoginError('admin', 'not-the-password');
        $unknownUser = $this->captureLoginError('nobody', 'admin-secret-1');
        $malformedUser = $this->captureLoginError('a!', 'admin-secret-1');

        $this->assertInstanceOf(InvalidCredentials::class, $wrongPassword);
        $this->assertInstanceOf(InvalidCredentials::class, $unknownUser);
        $this->assertInstanceOf(InvalidCredentials::class, $malformedUser);
        $this->assertSame($wrongPassword->getMessage(), $unknownUser->getMessage());
        $this->assertSame($wrongPassword->details(), $unknownUser->details());
    }

    public function test_admin_creates_an_assistant_with_normalized_username_and_hashed_password(): void
    {
        $user = $this->createAssistant->create('Camila ', 'camila-secret', 'assistant');

        $this->assertSame('camila', $user->username);
        $this->assertSame(Role::Assistant, $user->role);
        $this->assertNotSame('camila-secret', $user->passwordHash);
        $this->assertTrue($this->hasher->verify('camila-secret', $user->passwordHash));
        $this->assertSame(Role::Assistant, $this->login->login('camila', 'camila-secret')->role);
    }

    public function test_repeated_username_is_rejected_after_normalization(): void
    {
        $this->createAssistant->create('camila', 'camila-secret', 'assistant');

        $this->expectException(UsernameTaken::class);
        $this->createAssistant->create('  CAMILA', 'another-secret', 'assistant');
    }

    public function test_asking_for_the_admin_role_is_a_validation_error_and_stores_nothing(): void
    {
        try {
            $this->createAssistant->create('intruder', 'intruder-secret', 'admin');
            $this->fail('The admin role must never be granted through the API.');
        } catch (ValidationFailed $error) {
            $this->assertSame('role', $error->details()[0]['field']);
        }

        $this->assertNull($this->users->findByUsername('intruder'));
    }

    public function test_short_password_is_rejected_without_echoing_it(): void
    {
        try {
            $this->createAssistant->create('pedro', 'short7!', 'assistant');
            $this->fail('A password shorter than 8 characters must be rejected.');
        } catch (ValidationFailed $error) {
            $this->assertSame('password', $error->details()[0]['field']);
            $this->assertStringNotContainsString('short7!', $error->getMessage().json_encode($error->details()));
        }

        $this->assertCount(1, $this->users->all());
    }

    private function captureLoginError(string $username, string $password): InvalidCredentials
    {
        try {
            $this->login->login($username, $password);
        } catch (InvalidCredentials $error) {
            return $error;
        }

        $this->fail('Login should have failed.');
    }
}
