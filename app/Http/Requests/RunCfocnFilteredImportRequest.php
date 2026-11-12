<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RunCfocnFilteredImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'builder' => ['required', 'file'],
            'cfo' => ['required', 'file'],
            'all_infrastructures' => ['sometimes', 'boolean'],
            'infrastructures' => ['array'],
            'infrastructures.*' => ['string'],
            'rule' => ['required', Rule::in(['exact', 'contains'])],
        ];
    }

    public function messages(): array
    {
        return [
            'rule.in' => 'The rule must be "exact" or "contains".',
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            if (! $this->boolean('all_infrastructures') && count($this->input('infrastructures', [])) === 0) {
                $validator->errors()->add(
                    'infrastructures',
                    'Select at least one infrastructure, or choose "All infrastructures".'
                );
            }
        });
    }
}
