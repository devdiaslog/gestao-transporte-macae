<?php

namespace App\Models;

use App\Enums\UserPermission as UserPermissionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPermission extends Model
{
    protected $fillable = ['user_id', 'permission'];

    protected function casts(): array
    {
        return [
            'permission' => UserPermissionEnum::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
