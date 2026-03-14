<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2'],
            'email' => ['required', 'email', 'unique:users,email,' . $this->route('user')->id],
            'password' => ['required', 'min:6'],
            'role' => ['required', 'string']
        ];
    }
}
