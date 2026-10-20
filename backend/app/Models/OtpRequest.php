<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpRequest extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'phone',
        'req_id',
        'otp_hash',
        'expires_at',
        'consumed_at',
        'failed_attempts',
        'locked_until',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at'   => 'datetime',
            'consumed_at'  => 'datetime',
            'locked_until' => 'datetime',
            'created_at'   => 'datetime',
        ];
    }
}
