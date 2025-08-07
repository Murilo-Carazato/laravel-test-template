<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{

    public function rules()
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($this->route('user'))
            ],
            'password' => 'sometimes|min:8|confirmed',
            'profile' => 'sometimes|array',
            'profile.bio' => 'sometimes|string|max:1000',
            'profile.phone' => 'sometimes|string|max:20',
        ];
    }
}