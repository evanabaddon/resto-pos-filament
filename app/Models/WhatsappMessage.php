<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    protected $fillable = [
        'wa_id',
        'remote_jid',
        'push_name',
        'conversation_name',
        'from_me',
        'message',
        'full_message',
        'status',
        'attachment_path',
        'attachment_type',
        'caption',
    ];

    protected $casts = [
        'from_me' => 'boolean',
        'full_message' => 'array',
    ];
}
