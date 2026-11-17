<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;

/** Constitution article II: app/Application knows the domain and its own ports, never Laravel or the adapters. */
final class ApplicationDependsOnPortsTest extends TestCase
{
    public const FORBIDDEN = '/^\s*use\s+\\\\?(Illuminate\\\\|App\\\\Infrastructure\\\\|App\\\\Providers\\\\)/';

    public function test_the_application_imports_no_framework_or_infrastructure_class(): void
    {
        $files = SourceScanner::files('app/Application');

        $this->assertNotEmpty($files);
        $this->assertSame([], SourceScanner::violations($files, self::FORBIDDEN));
        $this->assertSame([], SourceScanner::violations($files, '/\\\\?Illuminate\\\\/'));
    }

    public function test_every_port_is_an_interface_or_value_whose_signatures_have_no_laravel_type(): void
    {
        foreach (array_keys(SourceScanner::files('app/Application/Ports')) as $path) {
            $class = new ReflectionClass('App\\Application\\Ports\\'.basename($path, '.php'));
            foreach ($class->getMethods() as $method) {
                $types = [$method->getReturnType(), ...array_map(fn ($p) => $p->getType(), $method->getParameters())];
                foreach ($types as $type) {
                    foreach (self::names($type) as $name) {
                        $this->assertStringStartsNotWith('Illuminate\\', $name, "{$class->getName()}::{$method->getName()}");
                    }
                }
            }
        }
    }

    public function test_the_rule_catches_a_forbidden_import(): void
    {
        $planted = ['app/Application/Services/Planted.php' => "<?php\nuse App\\Infrastructure\\Persistence\\DbLaptopRepository;\n"];

        $this->assertCount(1, SourceScanner::violations($planted, self::FORBIDDEN));
    }

    /** @return list<string> */
    private static function names(mixed $type): array
    {
        return match (true) {
            $type instanceof ReflectionNamedType => [$type->getName()],
            $type instanceof ReflectionUnionType => array_merge(...array_map(self::names(...), $type->getTypes())),
            default => [],
        };
    }
}
