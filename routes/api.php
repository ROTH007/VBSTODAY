<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CfocnFilteredImportController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\SavedImportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/imports', [ImportController::class, 'index']);
    Route::post('/imports', [ImportController::class, 'store']);
    Route::get('/imports/{import}', [ImportController::class, 'show']);
    Route::get('/imports/{import}/rows', [ImportController::class, 'rows']);
    Route::get('/imports/{import}/download', [ImportController::class, 'download']);

    // Saved CFOCN-import CRUD, backed by the saved_imports table.
    // Named /api/saved-imports, not /api/imports, to avoid colliding with
    // the routes above (a separate, unrelated feature for the /app SPA).
    Route::get('/saved-imports', [SavedImportController::class, 'index']);
    Route::get('/saved-imports/{savedImport}', [SavedImportController::class, 'show']);
    Route::patch('/saved-imports/{savedImport}', [SavedImportController::class, 'update']);
    Route::delete('/saved-imports/{savedImport}', [SavedImportController::class, 'destroy']);
    Route::get('/saved-imports/{savedImport}/download', [SavedImportController::class, 'download']);
    Route::get('/saved-imports/{savedImport}/rows', [SavedImportController::class, 'rows']);

    // New infrastructure-selector matching engine — separate from
    // ImportFileController::verifyData, never reads/writes `builders`.
    Route::post('/cfocn-filter/inspect-builder', [CfocnFilteredImportController::class, 'inspectBuilder']);
    Route::post('/cfocn-filter/run', [CfocnFilteredImportController::class, 'run']);
    Route::post('/cfocn-filter/verified-list', [CfocnFilteredImportController::class, 'importVerifiedList']);
    Route::get('/cfocn-filter/latest', [CfocnFilteredImportController::class, 'latest']);
});
