<?php

namespace App\Application\Services;

use App\Application\Ports\IdGenerator;
use App\Application\Ports\PasswordHasher;
use App\Application\Ports\UserRepository;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\User;
use SensitiveParameter;

/**
 * Plan D-10: the initial admin comes from the deployment environment, never from the API or the database seed.
 * Running it again with the same username changes nothing, so every start of the system can call it.
 */
final readonly class ProvisionAdminService
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private IdGenerator $ids,
    ) {
    }

    /** @return bool true when the admin was created, false when it already existed */
    public function provision(?string $username, #[SensitiveParameter] ?string $password): bool
    {
        $details = [];
        if ($username === null || trim($username) === '') {
            $details[] = ['field' => 'ADMIN_USERNAME', 'issue' => 'obligatoria'];
        }
        if ($password === null || mb_strlen($password) < CreateAssistantService::MIN_PASSWORD_LENGTH) {
            $details[] = ['field' => 'ADMIN_PASSWORD', 'issue' => 'mínimo '.CreateAssistantService::MIN_PASSWORD_LENGTH.' caracteres'];
        }
        if ($details !== []) {
            throw ValidationFailed::fields($details);
        }

        $normalized = User::normalizeUsername($username);
        if ($this->users->usernameExists($normalized)) {
            return false;
        }

        $this->users->add(User::provisionAdmin($this->ids->next(), $normalized, $this->hasher->hash($password)));

        return true;
    }
}
