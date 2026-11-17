<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Constitution article III: an infrastructure adapter is only built in PortBindingsServiceProvider. Anywhere else,
 * including the tests, `new …Repository`, `new …Hasher`, `new …Storage`, `new …Issuer` (and the other adapters) fails.
 */
final class SingleCompositionRootTest extends TestCase
{
    private const COMPOSITION_ROOT = 'app/Providers/PortBindingsServiceProvider.php';

    public const FORBIDDEN = '/\bnew\s+\\\\?(App\\\\Infrastructure\\\\[\w\\\\]*\\\\)?(Db\w+|\w+Repository|\w+Hasher|\w+Storage|\w+Issuer|SystemClock|RandomUuidGenerator)\s*\(/';

    public function test_adapters_are_only_built_in_the_composition_root(): void
    {
        $files = SourceScanner::files('app') + SourceScanner::files('tests') + SourceScanner::files('routes') + SourceScanner::files('bootstrap');
        unset($files[self::COMPOSITION_ROOT]);
        $files = array_filter($files, fn (string $path) => ! str_starts_with($path, 'tests/Fakes/')
            && ! str_starts_with($path, 'tests/Architecture/')
            && ! str_starts_with($path, 'bootstrap/cache/'), ARRAY_FILTER_USE_KEY);

        $this->assertSame([], SourceScanner::violations($files, self::FORBIDDEN));
    }

    public function test_every_port_is_bound_in_the_composition_root(): void
    {
        $root = (string) file_get_contents(dirname(__DIR__, 2).'/'.self::COMPOSITION_ROOT);

        foreach (array_keys(SourceScanner::files('app/Application/Ports')) as $path) {
            $name = basename($path, '.php');
            if (interface_exists('App\\Application\\Ports\\'.$name)) {
                $this->assertStringContainsString("bind({$name}::class", str_replace('singleton(', 'bind(', $root), "{$name} has no adapter");
            }
        }
    }

    public function test_the_rule_catches_an_adapter_built_outside(): void
    {
        $planted = ['app/Infrastructure/Http/Planted.php' => "<?php\n\$repo = new DbLaptopRepository(\$db);\n\$hasher = new Argon2PasswordHasher();\n"];

        $this->assertCount(2, SourceScanner::violations($planted, self::FORBIDDEN));
    }
}
