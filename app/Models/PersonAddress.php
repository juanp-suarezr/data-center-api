<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonAddress extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'person_id',
        'type',
        'is_primary',
        'via_principal',
        'numero_via',
        'complemento',
        'barrio',
        'comuna',
        'municipio',
        'departamento',
        'pais',
        'codigo_postal',
        'latitud',
        'longitud',
        'metadata',
    ];

    protected $casts = [
        'id' => 'string',
        'is_primary' => 'boolean',
        'latitud' => 'float',
        'longitud' => 'float',
        'metadata' => 'array',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function getFullAddressAttribute(): string
    {
        $parts = [
            $this->via_principal.' '.$this->numero_via,
            $this->complemento,
            $this->barrio,
            $this->comuna,
            $this->municipio.', '.$this->departamento,
        ];

        return implode(', ', array_filter($parts));
    }
}
