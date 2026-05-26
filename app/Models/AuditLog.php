<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'api_client_id',
        'action',
        'field_changed',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'request_id',
        'endpoint',
        'metadata',
    ];

    protected $casts = [
        'id' => 'string',
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    public function auditable()
    {
        return $this->morphTo();
    }

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    /**
     * Create a new audit entry (convenience).
     */
    public static function record(
        $auditable,
        string $action,
        ?ApiClient $client = null,
        ?array $old = null,
        ?array $new = null,
        ?string $requestId = null,
        ?string $ip = null,
        ?string $endpoint = null
    ): self {
        return static::create([
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->id,
            'api_client_id' => $client?->id,
            'action' => $action,
            'old_values' => $old,
            'new_values' => $new,
            'request_id' => $requestId,
            'ip_address' => $ip,
            'endpoint' => $endpoint,
        ]);
    }
}
