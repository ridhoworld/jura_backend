<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'entry_id',
        'file_type',
        'file_url',
        'thumbnail_url',
        'duration_seconds',
        'caption',
        'order_index',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'order_index' => 'integer',
    ];

    public function entry()
    {
        return $this->belongsTo(Entry::class);
    }
}

