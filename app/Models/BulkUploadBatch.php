<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkUploadBatch extends Model
{
    use HasUuids;

    protected $table = 'bulk_upload_batches';

    protected $fillable = [
        'id',
        'batch_id',
        'client_id',
        'total_records',
        'valid_records',
        'invalid_records',
        'status',
        'processed_count',
        'created_count',
        'updated_count',
        'error_count',
        'completed_at',
    ];

    protected $casts = [
        'id' => 'string',
        'batch_id' => 'string',
        'total_records' => 'integer',
        'valid_records' => 'integer',
        'invalid_records' => 'integer',
        'processed_count' => 'integer',
        'created_count' => 'integer',
        'updated_count' => 'integer',
        'error_count' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class, 'client_id');
    }
}
