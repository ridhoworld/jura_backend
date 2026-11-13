<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entry extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'journal_id',
        'title',
        'content',
        'entry_date',
        'location_name',
        'latitude',
        'longitude',
        'weather_info',
        'is_favorite',
        'is_pinned',
    ];

    protected $casts = [
        'entry_date' => 'datetime',
        'weather_info' => 'array',
        'is_favorite' => 'boolean',
        'is_pinned' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function moods()
    {
        return $this->belongsToMany(Mood::class, 'entry_moods', 'entry_id', 'mood_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'entry_tags', 'entry_id', 'tag_id');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class)->orderBy('order_index');
    }
}

