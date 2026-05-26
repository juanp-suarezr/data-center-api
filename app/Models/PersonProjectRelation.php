<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonProjectRelation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'person_project_relations';

    protected $fillable = [
        'person_id',
        'api_client_id',
        'first_seen_at',
        'last_synced_at',
        'last_verified_at',
        'data_quality_score',
        'trust_level',
        'contributed_fields',
        'last_action',
        'metadata',
    ];

    protected $casts = [
        'id' => 'string',
        'first_seen_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'data_quality_score' => 'integer',
        'contributed_fields' => 'array',
        'metadata' => 'array',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $relation) {
            if (empty($relation->first_seen_at)) {
                $relation->first_seen_at = now();
            }
        });
    }
}
