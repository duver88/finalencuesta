<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SurveyController as AdminSurveyController;
use App\Http\Controllers\Admin\SurveyGroupController;
use App\Http\Controllers\Admin\TokenController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\TokenRedirectController;
use Illuminate\Support\Facades\Route;

// Ruta principal - redirige al login
Route::get('/', function () {
    return redirect('/survey/encuesta-de-favorabilidad-alcaldia-de-bucaramanga-BU3aPT');
});

// Rutas de autenticación
Route::get('/HZlflogiis', [AuthController::class, 'showLogin'])->name('login');
Route::post('/HZlflogiis', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Ruta de generación automática de tokens (/t/)
Route::get('/t/{publicSlug}', [TokenRedirectController::class, 'redirect'])->name('token.redirect');
Route::get('/t/{groupSlug}/{publicSlug}', [TokenRedirectController::class, 'redirectWithGroup'])->name('token.redirect.group');

// API para asignar token después del delay (llamada desde JavaScript)
Route::post('/api/assign-token/{publicSlug}', [TokenRedirectController::class, 'assignToken'])->name('api.assign-token');

// Rutas públicas de encuestas (usando public_slug ofuscado)
Route::get('/surveys/unavailable', function () {
    return view('surveys.unavailable');
})->name('surveys.unavailable');

Route::get('/survey/{publicSlug}', [SurveyController::class, 'show'])->name('surveys.show');
Route::post('/survey/{publicSlug}/vote', [SurveyController::class, 'vote'])
    ->middleware('prevent.duplicate.vote')
    ->name('surveys.vote');
Route::get('/survey/{publicSlug}/thanks', [SurveyController::class, 'thanks'])->name('surveys.thanks');
Route::get('/survey/{publicSlug}/finished', [SurveyController::class, 'finished'])->name('surveys.finished');
Route::match(['get', 'post'], '/survey/{publicSlug}/check-vote', [SurveyController::class, 'checkVote'])->name('surveys.check-vote');

// Rutas del administrador (protegidas)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Gestión de encuestas
    Route::resource('surveys', AdminSurveyController::class);
    Route::post('/surveys/{survey}/publish', [AdminSurveyController::class, 'publish'])->name('surveys.publish');
    Route::post('/surveys/{survey}/unpublish', [AdminSurveyController::class, 'unpublish'])->name('surveys.unpublish');
    Route::post('/surveys/{survey}/finish', [AdminSurveyController::class, 'finish'])->name('surveys.finish');
    Route::post('/surveys/{survey}/unfinish', [AdminSurveyController::class, 'unfinish'])->name('surveys.unfinish');
    Route::delete('/surveys/{survey}/reset', [AdminSurveyController::class, 'reset'])->name('surveys.reset');
    Route::post('/surveys/{survey}/duplicate', [AdminSurveyController::class, 'duplicate'])->name('surveys.duplicate');
    Route::get('/surveys/{survey}/edit-votes', [AdminSurveyController::class, 'editVotes'])->name('surveys.votes.edit');
    Route::put('/surveys/{survey}/update-votes', [AdminSurveyController::class, 'updateVotes'])->name('surveys.votes.update');

    // Gestión de votos sospechosos
    Route::get('/surveys/{survey}/suspicious-votes', [AdminSurveyController::class, 'suspiciousVotes'])->name('surveys.suspicious-votes');
    Route::post('/surveys/{survey}/votes/{vote}/approve', [AdminSurveyController::class, 'approveVote'])->name('surveys.votes.approve');
    Route::post('/surveys/{survey}/votes/{vote}/reject', [AdminSurveyController::class, 'rejectVote'])->name('surveys.votes.reject');
    Route::post('/surveys/{survey}/votes/bulk-approve', [AdminSurveyController::class, 'bulkApproveVotes'])->name('surveys.votes.bulk-approve');
    Route::post('/surveys/{survey}/votes/bulk-reject', [AdminSurveyController::class, 'bulkRejectVotes'])->name('surveys.votes.bulk-reject');

    // Gestión de tokens
    Route::get('/surveys/{survey}/tokens', [TokenController::class, 'index'])->name('surveys.tokens.index');
    Route::post('/surveys/{survey}/tokens/generate', [TokenController::class, 'generate'])->name('surveys.tokens.generate');
    Route::get('/surveys/{survey}/tokens/export', [TokenController::class, 'export'])->name('surveys.tokens.export');
    Route::get('/surveys/{survey}/tokens/analytics', [TokenController::class, 'analytics'])->name('surveys.tokens.analytics');
    Route::get('/surveys/{survey}/tokens/{token}', [TokenController::class, 'show'])->name('surveys.tokens.show');
    Route::delete('/surveys/{survey}/tokens/{token}', [TokenController::class, 'destroy'])->name('surveys.tokens.destroy');
    Route::post('/surveys/{survey}/tokens/bulk-delete', [TokenController::class, 'bulkDelete'])->name('surveys.tokens.bulk-delete');

    // Gestión de grupos de encuestas
    Route::resource('survey-groups', SurveyGroupController::class);
    Route::post('/survey-groups/{group}/add-survey', [SurveyGroupController::class, 'addSurvey'])->name('survey-groups.add-survey');
    Route::delete('/survey-groups/{group}/surveys/{survey}', [SurveyGroupController::class, 'removeSurvey'])->name('survey-groups.remove-survey');
});

// Rutas públicas de encuestas con grupo (usando group_slug/public_slug)
// IMPORTANTE: Estas rutas DEBEN ir al final porque capturan cualquier patrón /{palabra}/{palabra}
Route::get('/{groupSlug}/{publicSlug}', [SurveyController::class, 'showWithGroup'])->name('surveys.show.group');
Route::post('/{groupSlug}/{publicSlug}/vote', [SurveyController::class, 'vote'])
    ->middleware('prevent.duplicate.vote')
    ->name('surveys.vote.group');
Route::get('/{groupSlug}/{publicSlug}/thanks', [SurveyController::class, 'thanks'])->name('surveys.thanks.group');
Route::get('/{groupSlug}/{publicSlug}/finished', [SurveyController::class, 'finished'])->name('surveys.finished.group');
Route::match(['get', 'post'], '/{groupSlug}/{publicSlug}/check-vote', [SurveyController::class, 'checkVote'])->name('surveys.check-vote.group');
