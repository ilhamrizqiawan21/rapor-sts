<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOperator() ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('subject')?->id ?? $this->subject;
        $curriculum = $this->input('curriculum', 'merdeka');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('subjects', 'code')
                    ->where('curriculum', $curriculum)
                    ->ignore($id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'parent_id' => ['nullable', 'exists:subjects,id'],
            'group' => ['required', 'in:umum,mulok'],
            'curriculum' => ['required', 'in:merdeka,k13'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
