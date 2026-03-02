<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuid, Notifiable, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'username',
        'email',
        'password',
        'phone',
        'full_name',
        'full_name_ar',
        'avatar',
        'user_type',
        'employee_id',
        'preferred_language',
        'is_active',
        'receive_notifications',
        'receive_email_notifications',
        'receive_sms_notifications',
        'fcm_token',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'fcm_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'receive_notifications' => 'boolean',
            'receive_email_notifications' => 'boolean',
            'receive_sms_notifications' => 'boolean',
        ];
    }

    // ─── Relationships ───

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // ─── Scopes ───

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Helpers ───

    public function isAdmin(): bool
    {
        return in_array($this->user_type, ['admin', 'super_admin']);
    }

    public function isHrManager(): bool
    {
        return in_array($this->user_type, ['hr_manager', 'admin', 'super_admin']);
    }

    public function isGeneralManager(): bool
    {
        return in_array($this->user_type, ['general_manager', 'super_admin']);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->preferred_language === 'ar' && $this->full_name_ar) {
            return $this->full_name_ar;
        }

        return $this->full_name;
    }
}
