<?php

namespace App\Infrastructure\Console;

use App\Application\Services\ProvisionAdminService;
use App\Domain\Exceptions\DomainError;
use Illuminate\Console\Command;

/**
 * T-17: `php artisan app:bootstrap-admin` creates the initial admin from ADMIN_USERNAME and ADMIN_PASSWORD.
 * The container entrypoint runs it right after the migrations, so an empty database ends with one admin.
 */
final class BootstrapAdminCommand extends Command
{
    protected $signature = 'app:bootstrap-admin';

    protected $description = 'Create the initial admin from ADMIN_USERNAME and ADMIN_PASSWORD (does nothing if it exists)';

    public function handle(ProvisionAdminService $admins): int
    {
        try {
            $created = $admins->provision(config('laptops.admin_username'), config('laptops.admin_password'));
        } catch (DomainError $error) {
            foreach ($error->details() as $detail) {
                $this->error("{$detail['field']}: {$detail['issue']}");
            }

            return self::FAILURE;
        }

        $this->info($created ? 'Initial admin created.' : 'Initial admin already exists; nothing to do.');

        return self::SUCCESS;
    }
}
