<?php

namespace App\Http\Requests\Hospital\Role;

use App\Models\Role\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $roleId = $this->route('id');

        // System roles ka name update nahi hota — validation optional karo
        $role = Role::withoutTenantScope()->find((int) $roleId);
        $isSystem = $role?->is_system ?? false;

        return [
            'name' => array_filter([
                $isSystem ? 'nullable' : 'required',
                'string',
                'max:100',
                ! $isSystem
                    ? Rule::unique(Role::class)
                        ->where('tenant_id', app('tenant')->id)
                        ->ignore($roleId)
                    : null,
            ]),
            'description' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.unique' => 'A role with this name already exists.',
        ];
    }
}
