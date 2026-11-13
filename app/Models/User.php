<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'full_name',
        'email',
        'password',
        'pin_lock_hash',
        'encryption_salt',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'pin_lock_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function journals()
    {
        return $this->hasMany(Journal::class);
    }

    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }
}
