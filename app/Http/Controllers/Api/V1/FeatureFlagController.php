<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Domains\Core\Services\FeatureFlagService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class FeatureFlagController extends ApiController
{
    public function __construct(
        protected FeatureFlagService $featureFlagService
    ) {}

    /**
     * Lista todas as feature flags
     */
    public function index(): JsonResponse
    {
        $flags = $this->featureFlagService->listAll();
        return $this->successResponse($flags, 'Feature flags retrieved successfully');
    }

    /**
     * ✅ ADICIONADO: Lista features do usuário autenticado
     */
    public function myFeatures(): JsonResponse
    {
        $userId = Auth::id();
        $features = $this->featureFlagService->getUserFeatures($userId);
        
        return $this->successResponse($features, 'User features retrieved successfully');
    }

    /**
     * Verifica status de uma feature flag
     */
    public function check(string $name): JsonResponse
    {
        $userId = Auth::check() ? Auth::id() : null;
        $isActive = $this->featureFlagService->isActive($name, $userId);
        
        return $this->successResponse([
            'name' => $name,
            'is_active' => $isActive,
            'user_id' => $userId
        ], 'Feature status retrieved successfully');
    }

    /**
     * ✅ CORRIGIDO: Cria ou atualiza uma feature flag (usando 'enabled' em vez de 'is_active')
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'enabled' => 'required|boolean', // ✅ CORRIGIDO: era 'is_active'
            'percentage' => 'nullable|integer|min:0|max:100',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Invalid input data', 422, $validator->errors());
        }

        $result = $this->featureFlagService->createOrUpdate(
            $request->input('name'),
            $request->input('enabled'), // ✅ CORRIGIDO: era 'is_active'
            $request->input('percentage', 0),
            $request->has('expires_at') ? new \DateTime($request->input('expires_at')) : null
        );

        if (!$result) {
            return $this->errorResponse('Could not create/update feature flag', 500, null);
        }

        return $this->successResponse([
            'name' => $request->input('name'),
            'enabled' => $request->input('enabled')
        ], 'Feature flag updated successfully');
    }

    /**
     * Ativa uma feature flag para um usuário específico
     */
    public function enableForUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Invalid input data', 422, $validator->errors());
        }

        $result = $this->featureFlagService->enableForUser(
            $request->input('name'),
            $request->input('user_id')
        );

        if (!$result) {
            return $this->errorResponse('Could not enable feature for user', 500, null);
        }

        return $this->successResponse([
            'name' => $request->input('name'),
            'user_id' => $request->input('user_id'),
            'enabled' => true
        ], 'Feature enabled for user successfully');
    }

    /**
     * Desativa uma feature flag para um usuário específico
     */
    public function disableForUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Invalid input data', 422, $validator->errors());
        }

        $result = $this->featureFlagService->disableForUser(
            $request->input('name'),
            $request->input('user_id')
        );

        if (!$result) {
            return $this->errorResponse('Could not disable feature for user', 500, null);
        }

        return $this->successResponse([
            'name' => $request->input('name'),
            'user_id' => $request->input('user_id'),
            'enabled' => false
        ], 'Feature disabled for user successfully');
    }

    /**
     * ✅ ADICIONADO: Toggle feature para usuário autenticado
     */
    public function toggle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Invalid input data', 422, $validator->errors());
        }

        $userId = Auth::id();
        $featureName = $request->input('name');
        $enabled = $request->input('enabled');

        $result = $enabled 
            ? $this->featureFlagService->enableForUser($featureName, $userId)
            : $this->featureFlagService->disableForUser($featureName, $userId);

        if (!$result) {
            return $this->errorResponse('Could not toggle feature', 500, null);
        }

        $status = $enabled ? 'enabled' : 'disabled';

        return $this->successResponse([
            'name' => $featureName,
            'enabled' => $enabled,
            'user_id' => $userId
        ], "Feature '{$featureName}' {$status} successfully");
    }

    /**
     * ✅ ADICIONADO: Bulk toggle features para usuário autenticado
     */
    public function bulkToggle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'features' => 'required|array',
            'features.*.name' => 'required|string|max:100',
            'features.*.enabled' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Invalid input data', 422, $validator->errors());
        }

        $userId = Auth::id();
        $features = collect($request->input('features'));
        $results = [];

        foreach ($features as $feature) {
            $featureName = $feature['name'];
            $enabled = $feature['enabled'];

            $result = $enabled 
                ? $this->featureFlagService->enableForUser($featureName, $userId)
                : $this->featureFlagService->disableForUser($featureName, $userId);

            $results[] = [
                'name' => $featureName,
                'enabled' => $enabled,
                'success' => $result
            ];
        }

        return $this->successResponse($results, 'Features updated successfully');
    }
}