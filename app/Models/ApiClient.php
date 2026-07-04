<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

/**
 * ApiClient represents an authorized external project/client.
 * This is the primary authentication subject for the Central Data API.
 * Each client gets API tokens via Sanctum.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property bool $is_active
 * @property bool $is_trusted
 */
class ApiClient extends Model
{
    use HasApiTokens, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'is_trusted',
        'allowed_ips',
        'rate_limit_per_minute',
        'contact_email',
        'webhook_url',
        'metadata',
    ];

    protected $casts = [
        'id' => 'string',
        'is_active' => 'boolean',
        'is_trusted' => 'boolean',
        'allowed_ips' => 'array',
        'metadata' => 'array',
        'rate_limit_per_minute' => 'integer',
    ];

    protected $hidden = [
        // Never expose tokens in JSON
    ];

    // ==================== Relationships ====================

    public function persons(): HasManyThrough
    {
        return $this->hasManyThrough(
            Persona::class,
            PersonProjectRelation::class,
            'api_client_id',
            'id',
            'id',
            'person_id'
        );
    }

    public function projectRelations(): HasMany
    {
        return $this->hasMany(PersonProjectRelation::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true);
    }

    // ==================== Business Methods ====================

    public function canAccessIp(?string $ip): bool
    {
        if (empty($this->allowed_ips)) {
            return true; // No restriction
        }

        // Simple check (production: use proper CIDR matching library)
        return in_array($ip, $this->allowed_ips, true);
    }

    public function issueToken(string $name = 'default', array $abilities = ['*']): string
    {
        return $this->createToken($name, $abilities)->plainTextToken;
    }
}
