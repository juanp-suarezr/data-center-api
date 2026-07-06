<?php

declare(strict_types=1);

namespace App\Http\Requests\API\v1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class BulkPersonUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt,sql', 'max:10240'],
            'skip_invalid' => ['nullable', 'boolean'],
            'update_existing' => ['nullable', 'boolean'],
            'source_project' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('skip_invalid')) {
            $this->merge(['skip_invalid' => filter_var($this->input('skip_invalid'), FILTER_VALIDATE_BOOLEAN)]);
        }
        if ($this->has('update_existing')) {
            $this->merge(['update_existing' => filter_var($this->input('update_existing'), FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed for file upload',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}