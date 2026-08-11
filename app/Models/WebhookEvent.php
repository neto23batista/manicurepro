<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    protected $fillable = [
        'provider',
        'event_id',
        'payload_hash',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];
}
