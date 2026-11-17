<?php

namespace App\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * PHP only fills $_FILES for POST, but the contract uploads the photo with PUT (§3.4). PHP 8.4 parses any method's
 * multipart body with request_parse_body(); the parsed file then reaches the controller like a normal upload.
 */
final class ParseMultipartPut
{
    public function handle(Request $request, Closure $next): Response
    {
        $isMultipart = str_starts_with(strtolower((string) $request->headers->get('Content-Type')), 'multipart/form-data');

        if ($request->isMethod('PUT') && $isMultipart && $request->files->count() === 0) {
            try {
                [$fields, $files] = request_parse_body();
                $request->request->add($fields);
                $request->files->add($files);
            } catch (Throwable) {
                // An unreadable body leaves the request without a file, and the controller answers 422.
            }
        }

        return $next($request);
    }
}
