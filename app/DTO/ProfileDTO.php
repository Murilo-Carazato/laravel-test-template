<?php

namespace App\DTO;

class ProfileDTO
{
    public readonly int $id;
    public readonly int $user_id;
    public readonly ?string $bio;
    public readonly ?string $avatar;
    public readonly ?array $preferences;
    
    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->user_id = $data['user_id'] ?? null;
        $this->bio = $data['bio'] ?? null;
        $this->avatar = $data['avatar'] ?? null;
        $this->preferences = $data['preferences'] ?? null;
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'bio' => $this->bio,
            'avatar' => $this->avatar,
            'preferences' => $this->preferences,
        ];
    }
}
