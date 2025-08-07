<?php

namespace App\DTO;

class AuditDTO
{
    public readonly ?int $id;
    public readonly string $event_type;
    public readonly ?array $data;
    public readonly ?int $user_id;
    public readonly ?string $ip_address;
    public readonly ?string $user_agent;
    
    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->event_type = $data['event_type'];
        $this->data = is_string($data['data'] ?? null) ? json_decode($data['data'], true) : ($data['data'] ?? null);
        $this->user_id = $data['user_id'] ?? null;
        $this->ip_address = $data['ip_address'] ?? null;
        $this->user_agent = $data['user_agent'] ?? null;
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type,
            'data' => is_array($this->data) ? json_encode($this->data) : $this->data,
            'user_id' => $this->user_id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
        ];
    }
}
