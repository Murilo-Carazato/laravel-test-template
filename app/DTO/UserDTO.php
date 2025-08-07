<?php

namespace App\DTO;

class UserDTO
{
    /**
     * @var string
     */
    private string $name;
    
    /**
     * @var string
     */
    private string $email;
    
    /**
     * @var string|null
     */
    private ?string $password;
    
    /**
     * @var array
     */
    private array $profileData;
    
    /**
     * Create a new UserDTO instance.
     *
     * @param string $name
     * @param string $email
     * @param string|null $password
     * @param array $profileData
     */
    public function __construct(string $name, string $email, ?string $password = null, array $profileData = [])
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->profileData = $profileData;
    }
    
    /**
     * Create UserDTO from array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $profileData = [];
        if (isset($data['profile'])) {
            $profileData = $data['profile'];
            // ✅ Remove profile do array principal
            unset($data['profile']);
        }
        
        return new self(
            $data['name'] ?? '',
            $data['email'] ?? '',
            $data['password'] ?? null,
            $profileData
        );
    }
    
    /**
     * Get user name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Get user email.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }
    
    /**
     * Get user password.
     *
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }
    
    /**
     * Get profile data.
     *
     * @return array
     */
    public function getProfileData(): array
    {
        return $this->profileData;
    }
    
    /**
     * ✅ NOVO: Check if has profile data
     *
     * @return bool
     */
    public function hasProfile(): bool
    {
        return !empty($this->profileData);
    }
    
    /**
     * ✅ CORRIGIDO: Convert to array for database - ONLY USER DATA
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];
        
        if ($this->password) {
            $data['password'] = $this->password;
        }

        // ❌ REMOVIDO: Profile não deve estar no array do User
        // Profile é tratado separadamente no Repository/Service
        // if (!empty($this->profileData)) {
        //     $data['profile'] = $this->profileData;
        // }
        
        return $data;
    }
}