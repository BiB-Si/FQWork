<?php

namespace App\Models\User;

use App\Models\Build\Build;
use App\Traits\HasAvatar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    use HasRoles;
    use HasFactory, Notifiable;
    use HasApiTokens {
        createToken as originalCreateToken;
    }
    use HasAvatar;
    use InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getPhotoUrlAttribute()
    {
        return $this->getAvatarUrl();
    }

    public function createToken(string $name, array $abilities = ['*'])
    {
        $this->tokens()->delete();
        return $this->originalCreateToken($name, $abilities);
    }

    public function builds()
    {
        return $this->hasMany(Build::class);
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection($this->avatarCollectionName)
            ->singleFile();
    }
}
