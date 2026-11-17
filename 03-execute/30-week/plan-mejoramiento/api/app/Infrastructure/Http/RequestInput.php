<?php

namespace App\Infrastructure\Http;

use App\Application\Ports\PageRequest;
use App\Domain\Exceptions\ValidationFailed;
use Illuminate\Http\Request;

/**
 * Shape checks of a request (step 1 of every operation). Wrong or missing fields are collected and reported together
 * as one VALIDATION_ERROR; business rules stay in the domain and the services.
 */
final class RequestInput
{
    /** @var list<array{field: string, issue: string}> */
    private array $details = [];

    public function __construct(private readonly Request $request)
    {
    }

    public function string(string $field): string
    {
        $value = $this->request->json($field);
        if (! is_string($value)) {
            $this->details[] = ['field' => $field, 'issue' => 'obligatorio, texto'];

            return '';
        }

        return $value;
    }

    public function integer(string $field): int
    {
        $value = $this->request->json($field);
        if (! is_int($value)) {
            $this->details[] = ['field' => $field, 'issue' => 'obligatorio, número entero'];

            return 0;
        }

        return $value;
    }

    /** @return list<mixed> */
    public function list(string $field): array
    {
        $value = $this->request->json($field);
        if (! is_array($value) || ! array_is_list($value)) {
            $this->details[] = ['field' => $field, 'issue' => 'obligatorio, lista'];

            return [];
        }

        return $value;
    }

    public function fail(string $field, string $issue): void
    {
        $this->details[] = ['field' => $field, 'issue' => $issue];
    }

    public function validate(): void
    {
        if ($this->details !== []) {
            throw ValidationFailed::fields($this->details);
        }
    }

    public static function query(Request $request, string $field): ?string
    {
        $value = $request->query($field);

        return is_string($value) ? $value : null;
    }

    /** Contract §1: `?page=1&size=20`, size at most 50. */
    public static function page(Request $request): PageRequest
    {
        $values = [];
        foreach (['page' => 1, 'size' => PageRequest::DEFAULT_SIZE] as $field => $default) {
            $raw = self::query($request, $field);
            if ($raw === null || $raw === '') {
                $values[$field] = $default;
            } elseif (preg_match('/^\d{1,6}$/', $raw) === 1) {
                $values[$field] = (int) $raw;
            } else {
                throw ValidationFailed::field($field, 'número entero');
            }
        }

        return PageRequest::of($values['page'], $values['size']);
    }
}
