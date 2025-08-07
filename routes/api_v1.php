<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\FeatureFlagController;

Route::get('/test', function () {
    return ['message' => 'API route is working!'];
});


// Rotas públicas - aplicando apenas rate limit padrão da API
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'version' => 'v1']);
});

// Rotas de autenticação - com rate limit específico mais restrito
// Route::middleware(['throttle:auth'])->group(function () {
//     Route::post('/auth/register', [AuthController::class, 'register']);
//     Route::post('/auth/login', [AuthController::class, 'login']);
//     Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
//     Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
// });

Route::middleware(['api.rate.limit'])->group(function () {
    Route::apiResource('users', UserController::class);
});

Route::prefix('features')->group(function () {
    Route::get('/', [FeatureFlagController::class, 'index']);
    Route::post('/', [FeatureFlagController::class, 'store']);
    Route::get('/my', [FeatureFlagController::class, 'myFeatures']);
    Route::get('/check/{name}', [FeatureFlagController::class, 'check']);
    Route::post('/toggle', [FeatureFlagController::class, 'toggle']);
    Route::post('/bulk-toggle', [FeatureFlagController::class, 'bulkToggle']);
    Route::post('/enable-for-user', [FeatureFlagController::class, 'enableForUser']);
    Route::post('/disable-for-user', [FeatureFlagController::class, 'disableForUser']);
});



Route::middleware(['auth:sanctum', 'refresh.token'])->group(function () {
    // Auth - gerenciamento de sessão
    // Route::post('/auth/logout', [AuthController::class, 'logout']);
    // Route::get('/auth/user', [AuthController::class, 'user']);

    // // Profile - gerenciamento do perfil do usuário logado
    // Route::middleware(['cache.response:30'])->group(function () {
    //     Route::get('/profile', [ProfileController::class, 'show']);
    // });
    // Route::put('/profile', [ProfileController::class, 'update']);

    // // Notificações - personalizadas para cada usuário
    // Route::middleware(['cache.response:5'])->group(function () {
    //     Route::get('/notifications', [NotificationController::class, 'index']);
    //     Route::get('/notifications/{notification}', [NotificationController::class, 'show']);
    // });
    // Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    // Route::put('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);

    // // FeatureFlags - configurações de recursos
    // Route::middleware(['cache.response:60'])->group(function () {
    //     Route::get('/features', [FeatureFlagController::class, 'index']);
    // });



    // Rotas administrativas para usuários
    Route::middleware(['can:manage,App\Models\User'])->group(function () {
        // Route::apiResource('users', UserController::class);

        // Admin de feature flags
        // Route::post('/features', [FeatureFlagController::class, 'store']);
        // Route::put('/features/{feature}', [FeatureFlagController::class, 'update']);
        // Route::delete('/features/{feature}', [FeatureFlagController::class, 'destroy']);
    });
});
