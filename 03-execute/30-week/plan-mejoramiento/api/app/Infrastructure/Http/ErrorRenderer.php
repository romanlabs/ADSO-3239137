<?php

namespace App\Infrastructure\Http;

use App\Domain\Exceptions\DomainError;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * The single error body of the contract (§1): { code, message, details }. Every exception that leaves a controller,
 * from the domain or from the framework, is turned into it here, so no trace, SQL, server path or password reaches a client.
 */
final class ErrorRenderer
{
    /** Contract §2: business code => HTTP status. */
    public const STATUS_BY_CODE = [
        'INVALID_CREDENTIALS' => 401,
        'FORBIDDEN_ROLE' => 403,
        'NOT_FOUND' => 404,
        'USERNAME_TAKEN' => 409,
        'MODEL_CODE_TAKEN' => 409,
        'DOCUMENT_TAKEN' => 409,
        'LAPTOP_HAS_OPEN_LOANS' => 409,
        'LAPTOP_NOT_AVAILABLE' => 409,
        'STUDENT_HAS_OVERDUE' => 409,
        'ITEM_ALREADY_RETURNED' => 409,
        'STUDENT_LIMIT_EXCEEDED' => 422,
        'DUPLICATE_LAPTOP_IN_LOAN' => 422,
        'VALIDATION_ERROR' => 422,
        'INVALID_DATE_RANGE' => 422,
    ];

    public const INTERNAL_ERROR = 'INTERNAL_ERROR';

    public function render(Throwable $error): JsonResponse
    {
        if ($error instanceof DomainError) {
            $code = $error->errorCode();

            return self::body($code, $error->getMessage(), $error->details(), self::STATUS_BY_CODE[$code] ?? 500);
        }

        if ($error instanceof HttpExceptionInterface) {
            return self::fromHttpStatus($error->getStatusCode());
        }

        return self::body(self::INTERNAL_ERROR, 'Ocurrió un error inesperado. Intenta de nuevo más tarde.', [], 500);
    }

    /** Framework errors that carry only a status: unknown route, wrong method, body too large. */
    private static function fromHttpStatus(int $status): JsonResponse
    {
        return match (true) {
            $status === 404, $status === 405 => self::body('NOT_FOUND', 'No se encontró el recurso.', [], 404),
            $status === 413 => self::body('VALIDATION_ERROR', 'La petición es demasiado grande.', [], 422),
            $status < 500 => self::body('VALIDATION_ERROR', 'La petición no se pudo procesar.', [], 422),
            default => self::body(self::INTERNAL_ERROR, 'Ocurrió un error inesperado. Intenta de nuevo más tarde.', [], 500),
        };
    }

    /** @param list<array{field: string, issue: string}> $details */
    private static function body(string $code, string $message, array $details, int $status): JsonResponse
    {
        return new JsonResponse(['code' => $code, 'message' => $message, 'details' => $details], $status, [], JSON_UNESCAPED_UNICODE);
    }
}
