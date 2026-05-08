<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterBlast extends Model
{
    protected $fillable = ['subject', 'body', 'audience', 'recipients_count', 'sent_at', 'created_by'];

    protected $casts = ['sent_at' => 'datetime'];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
