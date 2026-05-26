<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonContact extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'person_id',
        'type',
        'value',
        'is_primary',
        'is_verified',
        'verified_at',
        'verified_by_client_id',
        'country_code',
        'metadata',
    ];

    protected $casts = [
        'id' => 'string',
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class, 'verified_by_client_id');
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Normalize phone numbers or emails on save.
     */
    protected static function booted(): void
    {
        static::saving(function (PersonContact $contact) {
            if ($contact->type === 'phone' || $contact->type === 'mobile' || $contact->type === 'whatsapp') {
                $contact->value = preg_replace('/[^0-9+]/', '', $contact->value);
            }
            if ($contact->type === 'email') {
                $contact->value = strtolower(trim($contact->value));
            }
        });
    }
}
