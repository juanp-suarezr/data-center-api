<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Central Master Person Record (Single Source of Truth).
 * Designed for high-volume government use.
 * All external systems must go through this entity for person identity.
 */
class Person extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    // Table name forced to 'persons' to match migrations (default pluralization would be 'people')
    protected $table = 'persons';

    // UUID primary keys are non-incrementing strings
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'genero',
        'estado_civil',
        'ocupacion',
        'nacionalidad',
        'created_by_client_id',
        'updated_by_client_id',
        'source_project',
        'last_verified_at',
        'data_quality_score',
        'metadata',
    ];

    protected $casts = [
        'id' => 'string',
        'fecha_nacimiento' => 'date',
        'last_verified_at' => 'datetime',
        'data_quality_score' => 'integer',
        'metadata' => 'array',
    ];

    // ==================== Relationships ====================

    public function contacts(): HasMany
    {
        return $this->hasMany(PersonContact::class)->orderBy('is_primary', 'desc');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(PersonAddress::class)->orderBy('is_primary', 'desc');
    }

    public function projectRelations(): HasMany
    {
        return $this->hasMany(PersonProjectRelation::class);
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(PersonContact::class)->where('is_primary', true);
    }

    public function primaryAddress(): HasOne
    {
        return $this->hasOne(PersonAddress::class)->where('is_primary', true);
    }

    // ==================== Scopes ====================

    public function scopeByDocument($query, string $tipo, string $numero)
    {
        return $query->where('tipo_documento', strtoupper($tipo))
            ->where('numero_documento', $numero);
    }

    public function scopeHighQuality($query, int $minScore = 70)
    {
        return $query->where('data_quality_score', '>=', $minScore);
    }

    public function scopeFromProject($query, string $project)
    {
        return $query->where('source_project', $project);
    }

    // ==================== Accessors & Mutators ====================

    public function getFullNameAttribute(): string
    {
        return trim($this->nombres.' '.$this->apellidos);
    }

    public function getDocumentAttribute(): string
    {
        return $this->tipo_documento.'-'.$this->numero_documento;
    }

    // ==================== Business Logic ====================

    /**
     * Update quality score intelligently based on new data sources.
     */
    public function recalculateQualityScore(): void
    {
        $baseScore = 40;

        $contactCount = $this->contacts()->count();
        $addressCount = $this->addresses()->count();
        $verifiedContacts = $this->contacts()->where('is_verified', true)->count();

        $score = $baseScore
            + min($contactCount * 5, 20)
            + min($addressCount * 4, 16)
            + min($verifiedContacts * 6, 24);

        $this->data_quality_score = min(100, $score);
        $this->saveQuietly();
    }

    /**
     * Mark this record as verified by a trusted source.
     */
    public function markAsVerified(ApiClient $client): void
    {
        $this->last_verified_at = now();
        $this->updated_by_client_id = $client->id;

        if ($client->is_trusted) {
            $this->data_quality_score = max($this->data_quality_score, 85);
        }

        $this->save();
    }
}
