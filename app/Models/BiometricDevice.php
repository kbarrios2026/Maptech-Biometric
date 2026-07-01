<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BiometricDevice extends Model
{
    protected $fillable = [
        'name',
        'serial_number',
        'ip_address',
        'port',
        'comm_key',
        'device_token',
        'sync_mode',
        'is_active',
        'last_synced_at',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $device): void {
            if (empty($device->device_token)) {
                $device->device_token = Str::random(48);
            }
        });
    }
}
