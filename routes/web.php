<?php

use App\Http\Controllers\ImportFileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('homePage');
})->name('home');
Route::post('/importExecute',[ImportFileController::class,'verifyData'])->name('import.file')
    ->middleware('record.saved.import'); // Observes the response only; does not change the controller or its output.
Route::get('/export',[ImportFileController::class,'export_excel'])->name('export.file');

// The React SPA. The catch-all lets react-router handle client-side routes
// like /app/upload or /app/imports/5 on a hard refresh.
Route::get('/app/{any?}', function () {
    return view('reactApp');
})->where('any', '.*')->name('app.react');

// A separate, new React front end for the existing CFOCN import. It calls
// POST /importExecute directly (no new API, no new matching logic) and is
// independent of the /app SPA above. The catch-all lets react-router handle
// client-side routes like /cfocn-wizard/imports/5 on a hard refresh.
Route::get('/cfocn-wizard/{any?}', function () {
    return view('cfocnWizard');
})->where('any', '.*')->name('cfocn.wizard');
