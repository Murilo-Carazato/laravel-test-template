<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // Certifique-se que o modelo User existe

//Controller para armazenar dados secundários do usuário (bio, bairro...)
class ProfileController extends ApiController
{
    /**
     * Display the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Usar a policy para verificar se o usuário pode ver seu próprio perfil
        // $this->authorize('view', $user); // Implicitamente já é o usuário autenticado

        return response()->json(['data' => $user]);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // Usar a policy para verificar se o usuário pode atualizar seu próprio perfil
        $this->authorize('update', $user);

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'bio' => 'nullable|string|max:1000',
            // Adicionar outros campos de perfil conforme necessário
            // 'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id, // Se permitir edição de email
        ]);

        $user->update($validatedData);

        return response()->json(['message' => 'Profile updated successfully', 'data' => $user]);
    }
}
