<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;


class User extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'email',
        'image_url',
        'profile_image_url',
        'email_verification_status',
        'created_at',
        'updated_at'
    ];

}
