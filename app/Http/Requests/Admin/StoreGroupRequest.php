<?php

namespace App\Http\Requests\Admin;

use App\Models\Level;
use Illuminate\Foundation\Http\FormRequest;

class StoreGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool Returns true if the user is authorized, false otherwise.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Level::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'level_id' => ['required', 'integer', 'exists:levels,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'max_students' => ['required', 'integer', 'min:1', 'max:100'],
            'academic_year' => ['required', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }
}
