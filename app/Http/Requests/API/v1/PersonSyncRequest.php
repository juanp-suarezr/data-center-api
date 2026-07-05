<?php

declare(strict_types=1);

namespace App\Http\Requests\API\v1;

use App\Enums\DocumentType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PersonSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_documento' => ['required', 'string', 'in:'.implode(',', DocumentType::values())],
            'numero_documento' => ['required', 'string', 'min:4', 'max:20', 'regex:/^[A-Za-z0-9\-]+$/'],
            'nombres' => ['required', 'string', 'min:2', 'max:150'],
            'apellidos' => ['required', 'string', 'min:2', 'max:150'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'genero' => ['nullable', 'string', 'in:M,F,O,N'],
            'correo' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'array'],
            'direccion.via_principal' => ['required_with:direccion', 'string', 'max:100'],
            'direccion.municipio' => ['required_with:direccion', 'string', 'max:100'],
            'direccion.departamento' => ['required_with:direccion', 'string', 'max:100'],
            'source_project' => ['nullable', 'string', 'max:100'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors();
        $errorMessages = $errors->all();
        
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . implode('; ', $errorMessages),
                'errors' => $errors,
            ], 422)
        );
    }
}
