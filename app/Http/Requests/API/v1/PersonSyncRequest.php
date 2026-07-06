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
            'edad' => ['nullable', 'integer', 'min:0', 'max:150'],
            'genero' => ['nullable', 'string', 'in:M,F,O,N'],
            'correo' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'array'],
            'direccion.via_principal' => ['nullable', 'string', 'max:100'],
            'direccion.via principal' => ['nullable', 'string', 'max:100'],
            'direccion.municipio' => ['nullable', 'string', 'max:100'],
            'direccion.departamento' => ['nullable', 'string', 'max:100'],
            'direccion.complemento' => ['nullable', 'string', 'max:100'],
            'sector' => ['nullable', 'string', 'max:100'],
            'barrio' => ['nullable', 'string', 'max:100'],
            'comuna' => ['nullable', 'string', 'max:100'],
            'condicion' => ['nullable', 'string', 'max:100'],
            'etnia' => ['nullable', 'string', 'max:100'],
            'nivel_estudio' => ['nullable', 'string', 'max:100'],
            'dignatario' => ['nullable', 'boolean'],
            'source_project' => ['nullable', 'string', 'max:100'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email') && !$this->has('correo')) {
            $this->merge(['correo' => $this->input('email')]);
        }
        if ($this->input('direccion') && isset($this->input('direccion')['via principal']) && !isset($this->input('direccion')['via_principal'])) {
            $direccion = $this->input('direccion');
            $direccion['via_principal'] = $direccion['via principal'];
            $this->merge(['direccion' => $direccion]);
        }
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
