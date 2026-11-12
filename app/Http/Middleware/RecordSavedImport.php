<?php

namespace App\Http\Middleware;

use App\Services\SavedImportRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Saves a SavedImport record after a successful /importExecute download,
 * without touching ImportFileController (frozen). It reads the already-
 * generated workbook straight off the BinaryFileResponse — before Laravel
 * deletes the temp file during the terminate phase — and hands it to
 * SavedImportRecorder, which counts rows per sheet itself. No matching
 * logic lives here or in that service.
 */
class RecordSavedImport
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof BinaryFileResponse && $response->getStatusCode() === 200) {
            app(SavedImportRecorder::class)->record(
                filePath: $response->getFile()->getPathname(),
                company: (string) $request->input('company'),
                status: $request->input('status'),
                userId: $request->user()?->id,
            );
        }

        return $response;
    }
}
