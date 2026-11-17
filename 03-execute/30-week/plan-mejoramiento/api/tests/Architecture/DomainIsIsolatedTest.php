<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

/** Constitution article I: app/Domain imports only standard PHP and the domain itself. */
final class DomainIsIsolatedTest extends TestCase
{
    public const FORBIDDEN = '/^\s*use\s+\\\\?(Illuminate\\\\|App\\\\Application\\\\|App\\\\Infrastructure\\\\|App\\\\Providers\\\\)/';

    public function test_the_domain_imports_no_framework_application_or_infrastructure_class(): void
    {
        $files = SourceScanner::files('app/Domain');

        $this->assertNotEmpty($files);
        $this->assertSame([], SourceScanner::violations($files, self::FORBIDDEN));
        $this->assertSame([], SourceScanner::violations($files, '/\\\\?Illuminate\\\\|\b(app|config|env|now|collect)\(/'));
    }

    public function test_the_rule_catches_a_forbidden_import(): void
    {
        $planted = ['app/Domain/Planted.php' => "<?php\nnamespace App\\Domain;\nuse Illuminate\\Support\\Str;\n"];

        $this->assertSame(['app/Domain/Planted.php:3: use Illuminate\\Support\\Str;'], SourceScanner::violations($planted, self::FORBIDDEN));
    }
}
