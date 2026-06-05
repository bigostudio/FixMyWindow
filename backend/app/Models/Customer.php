<?php

namespace App\Models;

use App\Support\Enums\CustomerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Customer extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'name',
        'email',
        'type',
        'gst_number',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return [
            'type' => CustomerType::class,
        ];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return ['guard' => 'api'];
    }
}
