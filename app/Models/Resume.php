<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resume extends Model
{
    protected $fillable = [
        'user_id',
        'filename',
        'original_filename',
        'parsed_data',
        'json_resume_version',
        'uploaded_at',
    ];

    protected $casts = [
        'parsed_data' => 'array',
        'uploaded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getBasicsAttribute(): array
    {
        return $this->parsed_data['basics'] ?? [];
    }

    public function getNameAttribute(): string
    {
        return $this->basics['name'] ?? 'Unknown';
    }

    public function getEmailAttribute(): string
    {
        return $this->basics['email'] ?? '';
    }
}