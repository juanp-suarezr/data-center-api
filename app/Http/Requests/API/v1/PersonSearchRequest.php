<?php

declare(strict_types=1);

namespace App\Http\Requests\API\v1;

use App\Enums\DocumentType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PersonSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware
    }

    public function rules(): array
    {
        return [
            'tipo_documento' => ['sometimes', 'string', 'in:'.implode(',', DocumentType::values())],
            'numero_documento' => ['sometimes', 'string', 'min:4', 'max:20'],
            'document' => ['sometimes', 'string', 'min:4', 'max:30'],
            'nombres' => ['sometimes', 'string', 'min:2', 'max:100'],
            'apellidos' => ['sometimes', 'string', 'min:2', 'max:100'],
            'municipio' => ['sometimes', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_documento.in' => 'El tipo de documento debe ser uno de: '.implode(', ', DocumentType::values()),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Datos de búsqueda inválidos',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
