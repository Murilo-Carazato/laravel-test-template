<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Auth\LoginRequest;
use App\Domains\Auth\Commands\LoginCommand;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for User Authentication"
 * )
 */
//Controller para armazenar autenticação do usuário (login, logout...)
class AuthController extends ApiController
{
    protected $loginCommand;
    
    public function __construct(LoginCommand $loginCommand)
    {
        $this->loginCommand = $loginCommand;
    }
    
    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Login a user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User logged in successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login realizado com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="token", type="string", example="1|abcdef123456")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function login(LoginRequest $request)
    {
        $result = $this->loginCommand->handle(
            $request->email,
            $request->password
        );
        
        if (!$result['success']) {
            return $this->responseError($result['message']);
        }
        
        return $this->responseSuccess($result['data']);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Logout the current user",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logout realizado com sucesso"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());
            
            return $this->successResponse(
                null,
                'Logout realizado com sucesso'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/user",
     *     summary="Get the authenticated user's details",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dados do usuário recuperados com sucesso"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function user(Request $request): JsonResponse
    {
        try {
            $user = $this->authService->getUserDetails($request->user());
            
            return $this->successResponse(
                $user,
                'Dados do usuário recuperados com sucesso'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}

/**
 * @OA\Schema(
 *     schema="User",
 *     title="User",
 *     description="User model",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", example="2025-04-15T10:00:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-15T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-15T10:00:00Z"),
 *     @OA\Property(property="profile", ref="#/components/schemas/Profile")
 * )
 *
 * @OA\Schema(
 *     schema="Profile",
 *     title="Profile",
 *     description="User profile model",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="user_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="phone", type="string", example="123-456-7890", nullable=true),
 *     @OA\Property(property="address", type="string", example="123 Main St", nullable=true),
 *     @OA\Property(property="city", type="string", example="Anytown", nullable=true),
 *     @OA\Property(property="state", type="string", example="CA", nullable=true),
 *     @OA\Property(property="zip_code", type="string", example="90210", nullable=true),
 *     @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg", nullable=true),
 *     @OA\Property(property="bio", type="string", example="Software developer.", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-15T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-15T10:00:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     title="Error Response",
 *     description="Standard error response format",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Error message description")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationError",
 *     title="Validation Error Response",
 *     description="Validation error response format",
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         example={"email": {"The email field is required."}, "password": {"The password field is required."}}
 *     )
 * )
 */