<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

        // ✅ Incluir profile se carregado
        if ($this->relationLoaded('profile') && $this->profile) {
            $data['profile'] = ProfileResource::make($this->profile);
        }

        // ✅ Incluir roles se carregado (feature flag)
        if ($this->relationLoaded('roles')) {
            $data['roles'] = $this->roles->pluck('name')->toArray();
        }

        // ✅ Incluir permissions se carregado (feature flag)
        if ($this->relationLoaded('permissions')) {
            $data['permissions'] = $this->permissions->pluck('name')->toArray();
        }

        return $data;
    }
}
